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

def batch_forecast():
    if len(sys.argv) < 2:
        print(json.dumps({'status': 'error', 'message': 'No input CSV provided'}))
        return

    csv_path = sys.argv[1]
    if not os.path.exists(csv_path):
        print(json.dumps({'status': 'error', 'message': 'CSV file not found'}))
        return

    try:
        df = pd.read_csv(csv_path)
        if df.empty:
            print(json.dumps({'status': 'error', 'message': 'Empty data'}))
            return

        results = {}
        for product_id, group in df.groupby('product_id'):
            # Basic sanity check
            if group['qty'].sum() == 0:
                results[str(product_id)] = {'prediction': 0.0, 'trend': 'stable', 'confidence': 0.0, 'is_ml': False}
                continue

            # Sort by index and map holidays/promos/stockouts
            group = group.sort_values('month_index')
            group['holiday_score'] = group['date'].apply(get_holiday_score)
            group['promo_score'] = group['promo'].fillna(0)
            group['stockout_score'] = group['stockout'].fillna(0)

            # Pre-processing
            mean_qty = group['qty'].mean()
            group['qty_clipped'] = group['qty'].clip(upper=mean_qty * 3)

            # 🎓 S1 UPGRADE: Advanced Features
            group['cat_momentum'] = group['cat_momentum'].fillna(1.0)
            group['volatility'] = group['volatility'].fillna(0.0)

            # Features: [month_index, holiday_score, promo_score, stockout_score, cat_momentum, volatility]
            X = group[['month_index', 'holiday_score', 'promo_score', 'stockout_score', 'cat_momentum', 'volatility']].values
            y = group['qty_clipped'].values
            
            # Weighted Regression
            weights = np.power(1.5, group['month_index'])
            
            model = LinearRegression()
            model.fit(X, y, sample_weight=weights)
            
            # Predict for the NEXT month
            last_idx = group['month_index'].max()
            next_idx = last_idx + 1
            target_holiday_score = get_holiday_score(group['date'].iloc[-1]) # Simplified: could be improved with real next month date
            target_promo_score = 0 
            target_stockout_score = 0
            target_cat_momentum = 1.0 # Default neutral for future
            target_volatility = group['volatility'].iloc[-1] # Carry over the product stability
            
            X_target = np.array([[next_idx, target_holiday_score, target_promo_score, target_stockout_score, target_cat_momentum, target_volatility]])
            prediction = model.predict(X_target)[0]
            
            # Calculate Trend Sensitivity
            r2 = model.score(X, y, sample_weight=weights)
            coef = model.coef_[0]
            
            # Conservative Cap: Prediction shouldn't exceed 2x the recent average or be negative
            recent_avg = group['qty_clipped'].tail(3).mean()
            final_prediction = max(0, prediction)
            if final_prediction > recent_avg * 2:
                final_prediction = recent_avg * 2 # Safety cap for aggressive trends
            
            # Hybrid approach: If confidence is low, mix with simple average
            if r2 < 0.3:
                final_prediction = (final_prediction * 0.4) + (recent_avg * 0.6)

            results[str(product_id)] = {
                'prediction': float(round(final_prediction, 1)),
                'trend': 'growing' if coef > 0.5 else ('declining' if coef < -0.5 else 'stable'),
                'confidence': round(float(max(0, r2)) * 100, 1),
                'is_ml': True
            }

        print(json.dumps({'status': 'success', 'data': results}))

    except Exception as e:
        print(json.dumps({'status': 'error', 'message': str(e)}))

if __name__ == "__main__":
    batch_forecast()
