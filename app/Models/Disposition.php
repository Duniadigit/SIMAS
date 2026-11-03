<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disposition extends Model
{
    protected $fillable = [
        'incoming_letter_id', 'assignee', 'instruction', 'deadline',
        'completion_note', 'completed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['deadline' => 'date', 'completed_at' => 'datetime'];
    }

    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
