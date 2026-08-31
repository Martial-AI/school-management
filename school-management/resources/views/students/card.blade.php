<!doctype html>
<html lang="{{ app()->getLocale() }}"><body style="font-family: sans-serif; padding: 12px">
<strong>{{ mb_strtoupper(__('SCHOOL CARD')) }}</strong><br>
<span>{{ config('app.name') }}</span><hr>
<img src="data:image/svg+xml;base64,{{ $qr }}" width="92" style="float:right">
<p><strong>{{ $student->first_name }} {{ $student->last_name }}</strong><br>
{{ __('Student number') }} : {{ $student->student_number }}</p>
</body></html>
