<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><style>
    @page { margin: 8mm 5mm; } body { width: 302px; margin: 0 auto; font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; } .center{text-align:center}.line{border-top:1px dashed #555;margin:10px 0}.row{margin:6px 0}.amount{font-size:17px;font-weight:bold;text-align:center;margin:12px 0}.small{font-size:9px;color:#444}
</style></head><body>
<div class="center"><strong style="font-size:16px">GUT Center</strong><br><span class="small">{{ __('receipt.title') }}</span></div>
<div class="line"></div>
<div class="row"><strong>{{ __('receipt.number') }}:</strong> {{ $fee->receipt_number }}</div>
<div class="row"><strong>{{ __('receipt.date') }}:</strong> {{ $fee->paid_at?->format('d/m/Y H:i') }}</div>
<div class="row"><strong>{{ __('Student') }}:</strong> {{ $fee->student->first_name }} {{ $fee->student->last_name }}</div>
<div class="row"><strong>{{ __('Student number') }}:</strong> {{ $fee->student->student_number }}</div>
<div class="row"><strong>{{ __('Class') }}:</strong> {{ $fee->student->enrollments->firstWhere('status', 'active')?->schoolClass?->name ?? '—' }}</div>
<div class="row"><strong>{{ __('Month') }}:</strong> {{ $fee->fee_month->locale(app()->getLocale())->translatedFormat('F Y') }}</div>
<div class="line"></div>
<div class="amount">{{ number_format((float) $fee->amount, 0, ',', ' ') }} Ar</div>
<div class="center"><strong>{{ __('Paid') }}</strong><br><span class="small">{{ __('receipt.collected_by') }}: {{ $fee->paidBy?->name ?? '—' }}</span></div>
<div class="line"></div><div class="center small">{{ __('receipt.thank_you') }}</div>
</body></html>
