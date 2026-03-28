import sys
import json
import pandas as pd
from sklearn.linear_model import LinearRegression
import numpy as np
import os
import warnings
warnings.filterwarnings("ignore")

def get_holiday_score(date_str):
    """
    Returns a score representing the expected sales impact of holidays/seasons.
    Based on Indonesian calendar patterns (Lebaran, Christmas, etc.)
    """
    if not date_str or not isinstance(date_str, str):
        return 0
    try:
        parts = date_str.split('-')
        year, month = int(parts[0]), int(parts[1])
        # 1. Lebaran (Idul Fitri)
        if (year == 2024 and month == 4): return 10
        if (year == 2025 and (month == 3 or month == 4)): return 10
        if (year == 2026 and (month == 3 or month == 4)): return 10
        # 2. Christmas & Year End
        if month == 12: return 7
        # 3. School holidays
        if month == 6 or month == 7: return 4
        return 0
    except: return 0

def forecast():
    if len(sys.argv) < 2:
        print(json.dumps({'status': 'error', 'message': 'No input CSV provided'}))
        return

    csv_path = sys.argv[1]
    if not os.path.exists(csv_path):
        print(json.dumps({'status': 'error', 'message': 'CSV file not found'}))
        return

    try:
        # Load data
        df = pd.read_csv(csv_path)
        if df.empty or len(df) < 2:
            print(json.dumps({'status': 'error', 'message': 'Insufficient data for forecasting (min 2 months)'}))
            return

        # Map holidays, promos, and stockouts
        df['holiday_score'] = df['month'].apply(get_holiday_score)
        df['promo_score'] = df['promo'].fillna(0)
        df['stockout_score'] = df['stockout'].fillna(0) if 'stockout' in df.columns else 0

        # 🎓 S1 UPGRADE: Advanced Features
        df['cat_momentum'] = df['cat_momentum'].fillna(1.0) if 'cat_momentum' in df.columns else 1.0
        df['volatility'] = df['volatility'].fillna(0.0) if 'volatility' in df.columns else 0.0

        # Prepare features (X = [index, holiday, promo, stockout, cat_mo, vol])
        df['idx'] = range(len(df))
        X = df[['idx', 'holiday_score', 'promo_score', 'stockout_score', 'cat_momentum', 'volatility']].values
        y = df['total'].values
        
        # Weighted Regression: Recent months matter more (1.5x decay)
        weights = np.power(1.5, range(len(df)))

        # Fit Model
        model = LinearRegression()
        model.fit(X, y, sample_weight=weights)

        # Predict next month
        next_idx = len(df)
        target_holiday_score = get_holiday_score(df['month'].iloc[-1]) # Simplified
        target_promo_score = 0
        target_stockout_score = 0 
        target_cat_mo = 1.0
        target_vol = df['volatility'].iloc[-1]
        
        X_target = np.array([[next_idx, target_holiday_score, target_promo_score, target_stockout_score, target_cat_mo, target_vol]])
        prediction = model.predict(X_target)[0]
        
        # Calculate Trend (based on index coefficient)
        coef = model.coef_[0]
        trend = "growing" if coef > 0.5 else ("declining" if coef < -0.5 else "stable")
        
        # Simple confidence: R-squared
        r2 = model.score(X, y, sample_weight=weights)
        
        result = {
            'status': 'success',
            'prediction': float(max(0, prediction)),
            'trend': trend,
            'confidence': round(float(r2) * 100, 1),
            'coef': float(coef),
            'historical_avg': float(y.mean())
        }
        print(json.dumps(result))

    except Exception as e:
        print(json.dumps({'status': 'error', 'message': str(e)}))

if __name__ == "__main__":
    forecast()
