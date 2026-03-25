<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'outlet_id',
        'upload_history_id',
        'invoice_number',
        'transaction_date',
        'total',
        'meta',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'meta' => 'array',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function uploadHistory(): BelongsTo
    {
        return $this->belongsTo(UploadHistory::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
