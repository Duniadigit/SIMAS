<?php

namespace App\Models;

use App\Enums\IncomingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class IncomingLetter extends Model
{
    protected $fillable = [
        'agenda_no', 'letter_number', 'origin', 'subject', 'category',
        'letter_date', 'received_date', 'scan_path', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => IncomingStatus::class,
            'letter_date' => 'date',
            'received_date' => 'date',
        ];
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(Disposition::class)->orderByDesc('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->orderByDesc('id');
    }
}
