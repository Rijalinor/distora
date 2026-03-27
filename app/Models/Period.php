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
    /**
     * Get or create the active period for the current month.
     */
    public static function getActive(): self
    {
        $now = now();

        return self::firstOrCreate(
            ['status' => 'active', 'year' => $now->year, 'month' => $now->month],
            [
                'name' => $now->translatedFormat('F Y'),
            ]
        );
    }

    /**
     * Scope a query to only include active periods.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get all periods ordered by chronology (latest first).
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('year')->orderByDesc('month');
    }

    /**
     * Get the period to view. Defaults to latest active.
     */
    public static function resolveFromRequest($request): self
    {
        $periodId = $request->query('period_id');
        
        if ($periodId) {
            return self::find($periodId) ?? self::getActive();
        }

        // Default to the "latest" active period
        return self::where('status', 'active')->orderByDesc('year')->orderByDesc('month')->first() ?? self::getActive();
    }

    /**
     * Get Carbon date range for this period.
     */
    public function getRange(): array
    {
        $start = \Carbon\Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * Get IDs of N preceding periods (chronologically).
     */
    public function getPrecedingIds(int $count = 3): array
    {
        return self::where(function($q) {
                $q->where('year', '<', $this->year)
                  ->orWhere(function($q2) {
                      $q2->where('year', $this->year)
                         ->where('month', '<', $this->month);
                  });
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit($count)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Format: "Maret 2026"
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }
}
