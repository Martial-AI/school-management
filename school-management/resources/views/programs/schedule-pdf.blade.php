<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10px; }
        h1 { margin: 0; color: #047857; font-size: 22px; }
        h2 { margin: 7px 0 18px; color: #334155; font-size: 15px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #047857; color: #fff; font-size: 10px; text-align: left; }
        th, td { padding: 9px 8px; border: 1px solid #dbe3ea; }
        tr:nth-child(even) td { background: #f8fafc; }
        .empty { color: #64748b; padding: 28px; text-align: center; }
        .footer { margin-top: 18px; color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <h1>GUT Center</h1>
    <h2>{{ __('Schedule for :class', ['class' => $schoolClass->name]) }}</h2>

    <table>
        <thead>
            <tr>
                <th>{{ __('Monday') }}–{{ __('Saturday') }}</th>
                <th>{{ __('Subject') }}</th>
                <th>{{ __('Time') }}</th>
                <th>{{ $showTeacher ? __('Teacher') : __('Class') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($slots as $slot)
                <tr>
                    <td>{{ $dayLabels[$slot->day_of_week] ?? '—' }}</td>
                    <td>{{ $slot->subject?->localizedName() ?? '—' }}</td>
                    <td>{{ substr($slot->starts_at, 0, 5) }} – {{ substr($slot->ends_at, 0, 5) }}</td>
                    <td>{{ $showTeacher ? ($slot->teacher?->first_name ?: $slot->teacher?->name ?: '—') : $schoolClass->name }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">{{ __('No scheduled time slot.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">GUT Center · {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
