<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'upload_history_id',
        'product_id',
        'branch',
        'principle_code',
        'principle_name',
        'warehouse_code',
        'warehouse_name',
        'location_code',
        'location_name',
        'on_hand',
        'on_sales',
        'on_hand_base',
        'on_sales_base',
        'stock_value_on_hand',
        'stock_value_on_sales',
        'tonnage',
        'was',
        'swc',
        'age_of_goods',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function uploadHistory(): BelongsTo
    {
        return $this->belongsTo(UploadHistory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
