<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TeachingProgram;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

final class ActivityDestinationResolver
{
    public static function url(Activity $activity): ?string
    {
        if ($activity->log_name === 'profil') return route('profile.edit');
        if ($activity->log_name === 'connexion') return route('admin.history.index');
        $subject = $activity->subject;

        if ($subject instanceof Student) return route('students.show', $subject);
        if ($subject instanceof User) return route('admin.users.show', $subject);
        if ($subject instanceof SchoolClass) return route('classes.show', $subject);
        if ($subject instanceof TeachingProgram) return route('programs.edit', $subject);

        return match ($activity->log_name) {
            'eleves' => route('students.index'),
            'ecolages' => route('school-fees.index'),
            'classes' => route('classes.index'),
            'programmes' => route('programs.index'),
            'comptes', 'salaires' => route('admin.users.index'),
            'dépenses' => route('expenses.index'),
            'profil' => route('profile.edit'),
            'corbeille' => route('admin.trash.index'),
            default => route('admin.history.index'),
        };
    }
}
