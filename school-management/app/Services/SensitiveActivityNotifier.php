<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SensitiveActivityNotification;

class SensitiveActivityNotifier
{
    public static function send(string $title, string $message): void
    {
        User::role('Admin')->get()->each(fn (User $admin) => $admin->notify(new SensitiveActivityNotification($title, $message)));
    }
}
