<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    protected $fillable = [
        'name',
        'year',
        'month',
        'status',
        'summary',
        'closed_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'closed_at' => 'datetime',
    ];

    public function uploadHistories(): HasMany
    {
        return $this->hasMany(UploadHistory::class);
    }

    /**
     * Get or create the active period for the current month.
     */
    public static function getActive(): self
    {
        $now = now();

        return self::firstOrCreate(
            ['status' => 'active'],
            [
                'name' => $now->translatedFormat('F Y'),
                'year' => $now->year,
                'month' => $now->month,
            ]
        );
    }

    /**
     * Format: "Maret 2026"
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }
}
