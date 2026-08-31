<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProfessionalCardService
{
    public function pdf(User $user): \Barryvdh\DomPDF\PDF
    {
        $user->loadMissing(['teacherAssignments.schoolClass', 'teacherAssignments.subject']);
        $number = $user->professional_number ?: 'PA'.now()->format('y').'-'.str_pad((string) $user->id, 3, '0', STR_PAD_LEFT);
        $qr = base64_encode(QrCode::format('svg')->size(180)->generate($number));
        $logo = base64_encode((string) file_get_contents(public_path('images/gut-logo.png')));
        $photo = null;
        if ($user->photo_path) {
            $path = storage_path('app/private/'.$user->photo_path);
            if (is_file($path)) {
                $extension = strtolower(pathinfo($user->photo_path, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', default => 'image/jpeg',
                };
                $photo = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
            }
        }
        $role = $user->localizedRoleLabel();
        $classes = $user->teacherAssignments->pluck('schoolClass.name')->filter()->unique()->implode(', ');
        $cardName = trim($user->first_name.' '.$user->last_name) ?: $user->name;
        $cardRole = $role;
        $cardId = $number;
        $cardClass = $classes.($user->teaching_subjects ? ' · '.$user->teaching_subjects : '');
        $cardBirthDate = $user->birth_date?->translatedFormat('d F Y');
        $cardAddress = $user->address;
        $cardFunction = $role;
        $cardPhone = $user->phone;
        $cardIsProfessional = true;
        $student = null; $class = null;

        return Pdf::loadView('students.card-exact', compact('student', 'class', 'qr', 'logo', 'photo', 'cardName', 'cardRole', 'cardId', 'cardClass', 'cardBirthDate', 'cardAddress', 'cardFunction', 'cardPhone', 'cardIsProfessional'))
            ->setPaper([0, 0, 420, 220]);
    }
}
