<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'upload_history_id',
        'row_number',
        'level',
        'message',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function uploadHistory(): BelongsTo
    {
        return $this->belongsTo(UploadHistory::class);
    }
}
