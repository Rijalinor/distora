<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyProductSalesStat extends Model
{
    protected $fillable = [
        'period_id',
        'branch_dist_id',
        'principle_name',
        'product_id',
        'qty_sold',
        'total_net',
        'total_disc_item',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

