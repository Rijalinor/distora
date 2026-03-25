<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'transaction_id',
        'product_id',
        'seq_no',
        'qty',
        'price',
        'gross_price',
        'disc_item',
        'disc_internal',
        'disc_external',
        'disc_invoice',
        'total',
        'vat',
        'sold_at',
        'raw_data',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
