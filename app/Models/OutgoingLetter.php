<?php

namespace App\Models;

use App\Enums\OutgoingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OutgoingLetter extends Model
{
    protected $fillable = [
        'registration_no', 'letter_type_id', 'requester_name', 'requester_identifier',
        'requester_type', 'email', 'phone', 'subject', 'details', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OutgoingStatus::class,
            'letter_date' => 'date',
            'approved_at' => 'datetime',
            'letter_data' => 'array',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LetterType::class, 'letter_type_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->orderByDesc('id');
    }
}
