<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(
        Model $subject,
        string $action,
        string $description,
        ?string $from = null,
        ?string $to = null,
        ?User $user = null,
    ): void {
        $user ??= auth()->user();

        ActivityLog::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'user_id' => $user?->id,
            'actor_name' => $user?->name ?? 'Pemohon (publik)',
            'action' => $action,
            'description' => $description,
            'from_status' => $from,
            'to_status' => $to,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
