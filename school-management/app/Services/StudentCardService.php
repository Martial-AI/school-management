<?php

namespace App\Services;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class StudentCardService
{
    public function pdf(Student $student): \Barryvdh\DomPDF\PDF
    {
        $qr = base64_encode(QrCode::format('svg')->size(180)->generate($student->qr_token));
        $student->loadMissing(['guardians', 'enrollments.schoolClass']);
        $class = $student->enrollments->firstWhere('status', 'active')?->schoolClass;
        $logo = base64_encode((string) file_get_contents(public_path('images/gut-logo.png')));
        $photo = null;
        $photoPath = $student->photo_path ? storage_path('app/private/'.$student->photo_path) : null;
        if ($photoPath && is_file($photoPath)) {
            $extension = strtolower(pathinfo($student->photo_path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };
            $photo = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($photoPath));
        }
        $cardName = trim($student->first_name.' '.$student->last_name);
        $cardRole = __('Student');
        $cardId = $student->student_number;
        $cardClass = $class?->name;
        $cardBirthDate = $student->birth_date?->translatedFormat('d F Y');
        $cardAddress = $student->address;
        $cardFunction = __('Student');
        $cardPhone = $student->guardians->first()?->phone;
        $cardIsProfessional = false;
        return Pdf::loadView('students.card-exact', compact('student', 'qr', 'class', 'logo', 'photo', 'cardName', 'cardRole', 'cardId', 'cardClass', 'cardBirthDate', 'cardAddress', 'cardFunction', 'cardPhone', 'cardIsProfessional'))
            ->setPaper([0, 0, 420, 220]);
    }

    public function photoUrl(Student $student): ?string
    {
        return $student->photo_path ? Storage::disk('local')->url($student->photo_path) : null;
    }
}
