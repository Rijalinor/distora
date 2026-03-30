<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlForecastRun extends Model
{
    protected $fillable = [
        'context',
        'period_id',
        'branch',
        'scope_key',
        'entity_type',
        'entity_id',
        'entity_name',
        'model',
        'is_ml',
        'prediction',
        'prediction_low',
        'prediction_high',
        'actual_value',
        'error_abs',
        'error_pct',
        'confidence',
        'wape',
        'mape',
        'mae',
        'rmse',
        'forecasted_at',
        'evaluated_at',
        'meta',
    ];

    protected $casts = [
        'is_ml' => 'boolean',
        'prediction' => 'float',
        'prediction_low' => 'float',
        'prediction_high' => 'float',
        'actual_value' => 'float',
        'error_abs' => 'float',
        'error_pct' => 'float',
        'confidence' => 'float',
        'wape' => 'float',
        'mape' => 'float',
        'mae' => 'float',
        'rmse' => 'float',
        'forecasted_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'meta' => 'array',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }
}
