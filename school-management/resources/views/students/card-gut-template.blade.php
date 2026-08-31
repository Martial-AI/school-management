<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #1c1c28; }
        .card { position: relative; width: 242.65pt; height: 147.4pt; overflow: hidden; background: #fff; page-break-after: always; }
        .card:last-child { page-break-after: auto; }

        .header { position: absolute; top: 5pt; right: 7pt; left: 7pt; height: 22pt; }
        .brand { position: absolute; top: 5pt; right: 26pt; left: 26pt; text-align: center; font-size: 8.5pt; font-weight: 800; letter-spacing: .2pt; }
        .logo { position: absolute; top: 0; width: 19pt; height: 19pt; object-fit: contain; }
        .logo.left { left: 0; }
        .logo.right { right: 0; }

        .photo-panel { position: absolute; top: 29pt; left: 7pt; width: 64pt; height: 112pt; padding: 5pt; border-radius: 6pt; background: #3a2fc4; color: #fff; text-align: center; }
        .photo-frame { width: 54pt; height: 54pt; overflow: hidden; border-radius: 4pt; background: #dceeff; box-shadow: 0 2pt 5pt rgba(0,0,0,.25); }
        .photo-frame img { width: 100%; height: 100%; object-fit: cover; }
        .photo-placeholder { width: 54pt; height: 54pt; background: linear-gradient(180deg,#cfe8ff 0%,#e8f4ff 55%,#9bd06a 55%,#6fae3e 100%); color: #16209b; font-size: 24pt; font-weight: 800; line-height: 54pt; }
        .student-name { margin-top: 4pt; font-size: 6.4pt; font-weight: 800; line-height: 1.18; text-transform: uppercase; }
        .student-role { margin-top: 2pt; font-size: 5.6pt; font-weight: 400; }
        .student-id { margin-top: 2pt; font-size: 5.2pt; font-weight: 800; letter-spacing: .2pt; }
        .student-class { margin-top: 3pt; font-size: 5.7pt; font-weight: 800; letter-spacing: .15pt; }

        .info-panel { position: absolute; top: 31pt; right: 9pt; left: 88pt; height: 108pt; }
        .info-pill { display: inline-block; margin-bottom: 5pt; padding: 3pt 8pt; border-radius: 9pt; background: #3a2fc4; color: #fff; font-size: 5.5pt; font-weight: 700; }
        .field { margin-bottom: 3pt; }
        .label { color: #6b7280; font-size: 5.2pt; font-weight: 500; }
        .value { margin-top: 0; color: #1c1c28; font-size: 6.2pt; font-weight: 700; word-break: break-word; }
        .signature { position: absolute; right: 0; bottom: 0; color: #1c1c28; text-align: right; font-size: 5pt; line-height: 1.35; }
        .signature .role { color: #6b7280; }
        .signature .name { font-weight: 800; }

        .back-header { position: absolute; top: 5pt; right: 7pt; left: 7pt; height: 22pt; }
        .back-brand { position: absolute; top: 5pt; right: 26pt; left: 26pt; text-align: center; font-size: 8.5pt; font-weight: 800; letter-spacing: .2pt; }
        .back-body { position: absolute; top: 28pt; right: 11pt; left: 11pt; height: 93pt; }
        .qr { position: absolute; top: 1pt; left: 34pt; width: 66pt; height: 66pt; padding: 4pt; border-radius: 5pt; background: #fff; }
        .contact { position: absolute; top: 7pt; right: 2pt; width: 91pt; text-align: center; font-size: 5.7pt; }
        .contact .contact-label { margin-bottom: 2pt; color: #6b7280; font-size: 5.3pt; }
        .contact .contact-lines { font-weight: 700; line-height: 1.45; }
        .email { position: absolute; top: 73pt; right: 38pt; left: 38pt; padding: 4pt 5pt; border-radius: 5pt; background: #e6392b; color: #fff; text-align: center; font-size: 5.5pt; font-weight: 800; }
        .footer { position: absolute; top: 128pt; right: 0; left: 0; padding: 5pt 5pt; background: #16209b; color: #fff; text-align: center; font-size: 6.2pt; font-weight: 800; letter-spacing: .45pt; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <img class="logo left" src="data:image/png;base64,{{ $logo }}" alt="GUT Center">
            <div class="brand">GUT CENTER AMBILOBE</div>
            <img class="logo right" src="data:image/png;base64,{{ $logo }}" alt="GUT Center">
        </div>
        <div class="photo-panel">
            <div class="photo-frame">
                @if($photo)
                    <img src="{{ $photo }}" alt="{{ $student->first_name }} {{ $student->last_name }}">
                @else
                    <div class="photo-placeholder">{{ mb_strtoupper(mb_substr($student->first_name, 0, 1)) }}</div>
                @endif
            </div>
            <div class="student-name">{{ $student->first_name }} {{ $student->last_name }}</div>
            <div class="student-role">Étudiant</div>
            <div class="student-id">{{ $student->student_number }}</div>
            <div class="student-class">{{ $class?->name ?? '—' }}</div>
        </div>
        <div class="info-panel">
            <div class="info-pill">Informations personnelles</div>
            <div class="field"><div class="label">Date de naissance</div><div class="value">{{ $student->birth_date?->format('d/m/Y') ?? '—' }}</div></div>
            <div class="field"><div class="label">Adresse</div><div class="value">{{ $student->address ?: '—' }}</div></div>
            <div class="field"><div class="label">Fonction</div><div class="value">Étudiant</div></div>
            <div class="field"><div class="label">Téléphone tuteur</div><div class="value">{{ $student->guardians->first()?->phone ?: '—' }}</div></div>
            <div class="signature"><div class="role">Directeur Général</div><div class="name">Gautier BETIANA</div></div>
        </div>
    </div>

    <div class="card back-card">
        <div class="back-header">
            <img class="logo left" src="data:image/png;base64,{{ $logo }}" alt="GUT Center">
            <div class="back-brand">GUT CENTER AMBILOBE</div>
            <img class="logo right" src="data:image/png;base64,{{ $logo }}" alt="GUT Center">
        </div>
        <div class="back-body">
            <img class="qr" src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Code">
            <div class="contact">
                <div class="contact-label">Siège :</div>
                <div class="contact-lines">AMBILOBE<br>Lot III, 6 Antanamariazy<br>Tel : +261 32 77 366 80</div>
            </div>
            <div class="email">Email : gutcenter204@gmail.com</div>
        </div>
        <div class="footer">GO UP TOGETHER - LANGUAGES TRAINING</div>
    </div>
</body>
</html>
