import sys
import json
import os
import warnings
from datetime import datetime

import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestRegressor
from sklearn.linear_model import LinearRegression, Ridge

warnings.filterwarnings("ignore")


def parse_period(value):
    if value is None or (isinstance(value, float) and np.isnan(value)):
        return None
    text = str(value).strip()
    if not text:
        return None
    for fmt in ("%Y-%m", "%Y-%m-%d", "%Y/%m", "%Y/%m/%d", "%Y%m"):
        try:
            return datetime.strptime(text, fmt)
        except ValueError:
            continue
    return None


def next_period_string(period_text):
    dt = parse_period(period_text)
    if not dt:
        return None
    year = dt.year + (1 if dt.month == 12 else 0)
    month = 1 if dt.month == 12 else dt.month + 1
    return f"{year:04d}-{month:02d}"


def get_holiday_score(period_text):
    dt = parse_period(period_text)
    if not dt:
        return 0
    return {3: 6, 4: 8, 6: 4, 7: 4, 12: 7}.get(dt.month, 0)


FEATURES = [
    "month_index",
    "holiday_score",
    "promo_score",
    "stockout_score",
    "cat_momentum",
    "volatility",
    "lag1",
    "month_sin",
    "month_cos",
]


def create_model(name):
    if name == "linear":
        return LinearRegression()
    if name == "ridge":
        return Ridge(alpha=1.0, random_state=42)
    if name == "rf":
        return RandomForestRegressor(
            n_estimators=120,
            min_samples_leaf=2,
            random_state=42,
            n_jobs=1,
        )
    raise ValueError(f"Unknown model: {name}")


def compute_metrics(actuals, preds):
    actuals_np = np.array(actuals, dtype=float)
    preds_np = np.array(preds, dtype=float)
    abs_err = np.abs(actuals_np - preds_np)
    mae = float(abs_err.mean())
    rmse = float(np.sqrt(np.mean((actuals_np - preds_np) ** 2)))
    mape = float((abs_err / np.maximum(np.abs(actuals_np), 1e-9)).mean() * 100)
    wape = float(abs_err.sum() / (np.abs(actuals_np).sum() + 1e-9) * 100)
    return {"mae": mae, "rmse": rmse, "mape": mape, "wape": wape}


def prep_group(group):
    g = group.sort_values("month_index").copy()
    g["month_index"] = pd.to_numeric(g["month_index"], errors="coerce").fillna(0).astype(int)
    g["qty"] = pd.to_numeric(g["qty"], errors="coerce").fillna(0.0)
    g["promo_score"] = pd.to_numeric(g.get("promo", 0), errors="coerce").fillna(0.0)
    g["stockout_score"] = pd.to_numeric(g.get("stockout", 0), errors="coerce").fillna(0.0)
    g["cat_momentum"] = pd.to_numeric(g.get("cat_momentum", 1), errors="coerce").fillna(1.0)
    g["volatility"] = pd.to_numeric(g.get("volatility", 0), errors="coerce").fillna(0.0)
    g["date"] = g.get("date", "").astype(str)

    mean_qty = float(g["qty"].mean())
    cap = mean_qty * 3 if mean_qty > 0 else 0
    g["qty_clipped"] = g["qty"].clip(upper=cap if cap > 0 else None)
    g["holiday_score"] = g["date"].apply(get_holiday_score)

    parsed = g["date"].apply(parse_period)
    month_num = parsed.apply(lambda x: x.month if x else 1)
    g["month_sin"] = np.sin(2 * np.pi * month_num / 12.0)
    g["month_cos"] = np.cos(2 * np.pi * month_num / 12.0)
    g["lag1"] = g["qty_clipped"].shift(1).fillna(g["qty_clipped"].median() if len(g) else 0.0)
    return g


def walk_forward_eval(g, model_name, min_train=5):
    if len(g) <= min_train:
        return None

    preds = []
    actuals = []
    residuals = []

    for i in range(min_train, len(g)):
        train = g.iloc[:i]
        test = g.iloc[i]

        X_train = train[FEATURES].values
        y_train = train["qty_clipped"].values
        X_test = test[FEATURES].to_frame().T.values

        model = create_model(model_name)
        if model_name in ("linear", "ridge"):
            weights = np.power(1.15, np.arange(i, dtype=float))
            model.fit(X_train, y_train, sample_weight=weights)
        else:
            model.fit(X_train, y_train)

        pred = float(model.predict(X_test)[0])
        act = float(test["qty_clipped"])
        preds.append(pred)
        actuals.append(act)
        residuals.append(act - pred)

    return {"metrics": compute_metrics(actuals, preds), "residuals": residuals}


def choose_model(g):
    best = None
    for name in ("linear", "ridge", "rf"):
        res = walk_forward_eval(g, name)
        if res is None:
            continue
        score = res["metrics"]["wape"]
        if best is None or score < best["score"]:
            best = {"name": name, "score": score, "eval": res}
    return best


def fit_predict(g, model_name):
    model = create_model(model_name)
    X = g[FEATURES].values
    y = g["qty_clipped"].values

    if model_name in ("linear", "ridge"):
        weights = np.power(1.15, np.arange(len(g), dtype=float))
        model.fit(X, y, sample_weight=weights)
    else:
        model.fit(X, y)

    last_idx = int(g["month_index"].max())
    next_idx = last_idx + 1
    last_date = str(g["date"].iloc[-1]) if len(g) else ""
    next_date = next_period_string(last_date) or last_date
    target_dt = parse_period(next_date)
    target_month_num = target_dt.month if target_dt else 1

    recent_avg = float(g["qty_clipped"].tail(3).mean())
    lag1 = float(g["qty_clipped"].iloc[-1])
    x_target = np.array([[
        next_idx,
        get_holiday_score(next_date),
        0.0,
        0.0,
        1.0,
        float(g["volatility"].iloc[-1]),
        lag1,
        np.sin(2 * np.pi * target_month_num / 12.0),
        np.cos(2 * np.pi * target_month_num / 12.0),
    ]])
    raw = float(model.predict(x_target)[0])
    cap_up = recent_avg * 2.8 if recent_avg > 0 else max(0.0, raw)
    pred = max(0.0, min(raw, cap_up))

    if model_name in ("linear", "ridge"):
        coef = float(model.coef_[0])
        trend = "growing" if coef > 0.5 else ("declining" if coef < -0.5 else "stable")
    else:
        slope = float(g["qty_clipped"].tail(2).iloc[-1] - g["qty_clipped"].tail(2).iloc[0]) if len(g) >= 2 else 0.0
        trend = "growing" if slope > 0 else ("declining" if slope < 0 else "stable")

    return pred, trend


def forecast_group(group):
    g = prep_group(group)

    if g["qty"].sum() == 0:
        return {
            "prediction": 0.0,
            "trend": "stable",
            "confidence": 0.0,
            "is_ml": False,
            "validation": None,
            "prediction_interval": {"low": 0.0, "high": 0.0},
            "model": "no_demand",
        }

    if len(g) < 4:
        fallback = float(max(0.0, g["qty_clipped"].tail(3).mean()))
        return {
            "prediction": round(fallback, 1),
            "trend": "stable",
            "confidence": 35.0,
            "is_ml": False,
            "validation": None,
            "prediction_interval": {"low": round(max(0.0, fallback * 0.8), 2), "high": round(fallback * 1.2, 2)},
            "model": "fallback_avg",
        }

    selected = choose_model(g)
    if not selected:
        fallback = float(max(0.0, g["qty_clipped"].tail(3).mean()))
        return {
            "prediction": round(fallback, 1),
            "trend": "stable",
            "confidence": 30.0,
            "is_ml": False,
            "validation": None,
            "prediction_interval": {"low": round(max(0.0, fallback * 0.75), 2), "high": round(fallback * 1.25, 2)},
            "model": "fallback_avg",
        }

    pred, trend = fit_predict(g, selected["name"])
    metrics = selected["eval"]["metrics"]
    residuals = np.array(selected["eval"]["residuals"], dtype=float)
    q10 = float(np.quantile(residuals, 0.1)) if residuals.size else -abs(pred * 0.15)
    q90 = float(np.quantile(residuals, 0.9)) if residuals.size else abs(pred * 0.15)
    low = max(0.0, pred + q10)
    high = max(low, pred + q90)
    confidence = max(0.0, min(99.0, 100.0 - metrics["wape"]))

    if confidence < 30:
        recent_avg = float(g["qty_clipped"].tail(3).mean())
        pred = (pred * 0.4) + (recent_avg * 0.6)

    return {
        "prediction": float(round(pred, 1)),
        "trend": trend,
        "confidence": round(confidence, 1),
        "is_ml": True,
        "validation": {
            "mae": round(metrics["mae"], 4),
            "rmse": round(metrics["rmse"], 4),
            "mape": round(metrics["mape"], 2),
            "wape": round(metrics["wape"], 2),
        },
        "prediction_interval": {"low": round(low, 2), "high": round(high, 2)},
        "model": selected["name"],
    }


def batch_forecast():
    if len(sys.argv) < 2:
        print(json.dumps({"status": "error", "message": "No input CSV provided"}))
        return

    csv_path = sys.argv[1]
    if not os.path.exists(csv_path):
        print(json.dumps({"status": "error", "message": "CSV file not found"}))
        return

    try:
        df = pd.read_csv(csv_path)
        if df.empty:
            print(json.dumps({"status": "error", "message": "Empty data"}))
            return

        for col, default in [("date", ""), ("promo", 0), ("stockout", 0), ("cat_momentum", 1), ("volatility", 0)]:
            if col not in df.columns:
                df[col] = default

        results = {}
        for product_id, group in df.groupby("product_id"):
            results[str(product_id)] = forecast_group(group)

        print(json.dumps({"status": "success", "data": results}))
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))


if __name__ == "__main__":
    batch_forecast()
