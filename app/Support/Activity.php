<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Notice;

class Activity
{
    public static function log(string $action, string $subject, array $meta = []): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'subject' => $subject, 'meta' => $meta]);
    }

    public static function notify(int $user, string $message, string $key): void
    {
        Notice::firstOrCreate(['dedupe' => $key], ['user_id' => $user, 'message' => $message]);
    }
}
