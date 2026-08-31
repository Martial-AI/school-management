<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; color: #182033; font-family: DejaVu Sans, Arial, sans-serif; }
        .card { width: 242.65pt; height: 138pt; position: relative; overflow: hidden; background: #fff; page-break-after: always; }
        .card:last-child { page-break-after: auto; }
        .logo { position: absolute; top: 8pt; width: 23pt; height: 23pt; object-fit: contain; }
        .logo.left { left: 10pt; }
        .logo.right { right: 10pt; }
        .title { padding-top: 16pt; text-align: center; font-size: 13pt; font-weight: 800; }
        .front-left { position: absolute; top: 40pt; left: 10pt; width: 88pt; height: 91pt; border-radius: 6pt; background: #1557b0; color: #fff; text-align: center; }
        .photo { width: 39pt; height: 39pt; margin: 7pt auto 3pt; border: 2pt solid #fff; border-radius: 50%; object-fit: cover; }
        .fallback { width: 39pt; height: 39pt; margin: 7pt auto 3pt; border: 2pt solid #fff; border-radius: 50%; background: #fff; color: #1557b0; font-size: 20pt; font-weight: bold; line-height: 39pt; }
        .name { font-size: 8pt; font-weight: bold; text-transform: uppercase; }
        .role { margin-top: 2pt; font-size: 7pt; }
        .id { margin-top: 3pt; font-size: 8.5pt; font-weight: bold; }
        .class { margin-top: 2pt; font-size: 6.5pt; font-weight: bold; }
        .info { position: absolute; top: 47pt; left: 108pt; width: 120pt; font-size: 7.2pt; line-height: 1.55; }
        .info strong { font-size: 7.5pt; }
        .info-title { margin: 2pt 0 3pt; padding: 2pt; border-radius: 3pt; background: #dc2626; color: #fff; text-align: center; font-weight: bold; }
        .back { text-align: center; }
        .back .title { padding-top: 17pt; }
        .qr { display: block; width: 66pt; height: 66pt; margin: 8pt auto 3pt; }
        .contact { position: absolute; left: 18pt; bottom: 25pt; font-size: 6.3pt; line-height: 1.35; text-align: left; }
        .contact strong { font-size: 6.7pt; }
        .email { position: absolute; right: 18pt; bottom: 31pt; padding: 3pt 7pt; border-radius: 5pt; background: #dc2626; color: #fff; font-size: 6.5pt; font-weight: bold; }
        .footer { position: absolute; right: 0; bottom: 0; left: 0; padding: 5pt 0; background: #315fca; color: #fff; text-align: center; font-size: 8pt; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <img class="logo right" src="data:image/png;base64,{{ $logo }}">
        <div class="title">GUT CENTER AMBILOBE</div>
        <div class="front-left">
            @if($photo)
                <img class="photo" src="{{ $photo }}">
            @else
                <div class="fallback">{{ mb_strtoupper(mb_substr($student->first_name, 0, 1)) }}</div>
            @endif
            <div class="name">{{ $student->first_name }} {{ $student->last_name }}</div>
            <div class="role">Étudiant</div>
            <div class="id">{{ $student->student_number }}</div>
            <div class="class">{{ $class?->name ?? '—' }}</div>
        </div>
        <div class="info">
            <div>Date de naissance : <strong>{{ $student->birth_date?->format('d/m/Y') ?? '—' }}</strong></div>
            <div class="info-title">Informations personnelles</div>
            <div>Adresse : <strong>{{ $student->address ?: '—' }}</strong></div>
            <div>Fonction : <strong>Étudiant</strong></div>
            <div>Téléphone tuteur : <strong>{{ $student->guardians->first()?->phone ?: '—' }}</strong></div>
        </div>
    </div>
    <div class="card back">
        <img class="logo left" src="data:image/png;base64,{{ $logo }}">
        <img class="logo right" src="data:image/png;base64,{{ $logo }}">
        <div class="title">GUT CENTER AMBILOBE</div>
        <img class="qr" src="data:image/svg+xml;base64,{{ $qr }}">
        <div class="contact">Siège :<br><strong>AMBILOBE<br>Lot III, 6 Antanamariazy<br>Tel : +261 32 77 366 80</strong></div>
        <div class="email">Email : gutcenter204@gmail.com</div>
        <div class="footer">GO UP TOGETHER - LANGUAGES TRAINING</div>
    </div>
</body>
</html>
