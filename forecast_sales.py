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


def build_features(df):
    out = df.copy()
    out["period_dt"] = out["month"].apply(parse_period)
    if out["period_dt"].notna().any():
        out = out.sort_values("period_dt").reset_index(drop=True)
        out["month"] = out["period_dt"].dt.strftime("%Y-%m")
        out["month_num"] = out["period_dt"].dt.month
    else:
        out = out.reset_index(drop=True)
        out["month_num"] = 1

    out["idx"] = np.arange(len(out))
    out["holiday_score"] = out["month"].apply(get_holiday_score)
    out["promo_score"] = pd.to_numeric(out.get("promo", 0), errors="coerce").fillna(0.0)
    out["stockout_score"] = pd.to_numeric(out.get("stockout", 0), errors="coerce").fillna(0.0)
    out["cat_momentum"] = pd.to_numeric(out.get("cat_momentum", 1.0), errors="coerce").fillna(1.0)
    out["volatility"] = pd.to_numeric(out.get("volatility", 0.0), errors="coerce").fillna(0.0)
    out["total"] = pd.to_numeric(out["total"], errors="coerce").fillna(0.0)
    out["lag1"] = out["total"].shift(1).fillna(out["total"].median() if len(out) else 0.0)
    out["month_sin"] = np.sin(2 * np.pi * out["month_num"] / 12.0)
    out["month_cos"] = np.cos(2 * np.pi * out["month_num"] / 12.0)
    return out


FEATURES = [
    "idx",
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
            n_estimators=150,
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


def walk_forward_eval(df, model_name, min_train=5):
    if len(df) <= min_train:
        return None

    preds = []
    actuals = []
    residuals = []
    recency = np.arange(len(df), dtype=float)

    for i in range(min_train, len(df)):
        train = df.iloc[:i]
        test = df.iloc[i]
        X_train = train[FEATURES].values
        y_train = train["total"].values
        X_test = test[FEATURES].to_frame().T.values

        model = create_model(model_name)
        if model_name in ("linear", "ridge"):
            weights = np.power(1.15, recency[:i])
            model.fit(X_train, y_train, sample_weight=weights)
        else:
            model.fit(X_train, y_train)

        pred = float(model.predict(X_test)[0])
        act = float(test["total"])
        preds.append(pred)
        actuals.append(act)
        residuals.append(act - pred)

    metrics = compute_metrics(actuals, preds)
    return {"metrics": metrics, "residuals": residuals, "preds": preds, "actuals": actuals}


def choose_model(df):
    candidates = ["linear", "ridge", "rf"]
    best = None
    for name in candidates:
        res = walk_forward_eval(df, name)
        if res is None:
            continue
        score = res["metrics"]["wape"]
        if best is None or score < best["score"]:
            best = {"name": name, "score": score, "eval": res}
    return best


def fit_and_predict(df, model_name):
    model = create_model(model_name)
    X = df[FEATURES].values
    y = df["total"].values
    if model_name in ("linear", "ridge"):
        weights = np.power(1.15, np.arange(len(df), dtype=float))
        model.fit(X, y, sample_weight=weights)
    else:
        model.fit(X, y)

    last_month = str(df["month"].iloc[-1])
    target_month = next_period_string(last_month) or last_month
    target_dt = parse_period(target_month)
    target_month_num = target_dt.month if target_dt else 1

    recent_vol = float(df["volatility"].iloc[-1])
    recent_avg = float(df["total"].tail(3).mean())
    lag1 = float(df["total"].iloc[-1])

    x_target = np.array([[
        len(df),
        get_holiday_score(target_month),
        0.0,
        0.0,
        1.0,
        recent_vol,
        lag1,
        np.sin(2 * np.pi * target_month_num / 12.0),
        np.cos(2 * np.pi * target_month_num / 12.0),
    ]])
    raw_pred = float(model.predict(x_target)[0])

    # Keep prediction within a pragmatic range when behavior is extreme.
    cap_up = recent_avg * 2.8 if recent_avg > 0 else max(0.0, raw_pred)
    pred = max(0.0, min(raw_pred, cap_up))

    if model_name in ("linear", "ridge"):
        coef = float(model.coef_[0])
        trend = "growing" if coef > 0.5 else ("declining" if coef < -0.5 else "stable")
    else:
        slope = float(df["total"].tail(2).iloc[-1] - df["total"].tail(2).iloc[0]) if len(df) >= 2 else 0.0
        trend = "growing" if slope > 0 else ("declining" if slope < 0 else "stable")

    return pred, trend, model


def forecast():
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

        if "total" not in df.columns:
            if "value" in df.columns:
                df["total"] = df["value"]
            else:
                print(json.dumps({"status": "error", "message": "Missing target column: total"}))
                return
        if "month" not in df.columns:
            df["month"] = ""

        df = build_features(df)

        if len(df) < 4:
            fallback = float(max(0.0, df["total"].tail(3).mean()))
            print(json.dumps({
                "status": "success",
                "prediction": fallback,
                "trend": "stable",
                "confidence": 35.0,
                "historical_avg": float(df["total"].mean()),
                "validation": None,
                "prediction_interval": {"low": round(max(0.0, fallback * 0.8), 2), "high": round(fallback * 1.2, 2)},
                "model": "fallback_avg",
                "is_ml": False,
            }))
            return

        selected = choose_model(df)
        if not selected:
            pred = float(max(0.0, df["total"].tail(3).mean()))
            print(json.dumps({
                "status": "success",
                "prediction": pred,
                "trend": "stable",
                "confidence": 30.0,
                "historical_avg": float(df["total"].mean()),
                "validation": None,
                "prediction_interval": {"low": round(max(0.0, pred * 0.75), 2), "high": round(pred * 1.25, 2)},
                "model": "fallback_avg",
                "is_ml": False,
            }))
            return

        model_name = selected["name"]
        validation = selected["eval"]["metrics"]
        residuals = np.array(selected["eval"]["residuals"], dtype=float)

        pred, trend, _ = fit_and_predict(df, model_name)

        q10 = float(np.quantile(residuals, 0.1)) if residuals.size else -abs(pred * 0.15)
        q90 = float(np.quantile(residuals, 0.9)) if residuals.size else abs(pred * 0.15)
        low = max(0.0, pred + q10)
        high = max(low, pred + q90)

        confidence = max(0.0, min(99.0, 100.0 - validation["wape"]))

        print(json.dumps({
            "status": "success",
            "prediction": float(round(pred, 2)),
            "trend": trend,
            "confidence": round(confidence, 1),
            "historical_avg": float(round(df["total"].mean(), 2)),
            "validation": {
                "mae": round(validation["mae"], 4),
                "rmse": round(validation["rmse"], 4),
                "mape": round(validation["mape"], 2),
                "wape": round(validation["wape"], 2),
            },
            "prediction_interval": {
                "low": round(low, 2),
                "high": round(high, 2),
            },
            "model": model_name,
            "is_ml": True,
        }))
    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))


if __name__ == "__main__":
    forecast()
