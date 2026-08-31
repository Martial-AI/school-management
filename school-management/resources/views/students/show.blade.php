<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl">{{ __('Student record') }}</h2></x-slot>
<style>
    .student-record-card { position:relative; }
    .student-record-avatar { position:absolute; top:22px; left:24px; width:54px; height:54px; display:flex; align-items:center; justify-content:center; overflow:hidden; border-radius:9999px; background:#047857; color:#fff; font-size:1.35rem; font-weight:700; box-shadow:0 2px 8px rgba(15,23,42,.18); }
    .student-record-avatar img { width:100%; height:100%; object-fit:cover; }
    .student-record-avatar + .flex.flex-wrap.justify-between.gap-4 > div:first-child { padding-left:70px; }
</style>
<div class="mx-auto max-w-5xl py-8 sm:px-6 lg:px-8"><div class="student-record-card rounded-xl bg-white p-6 shadow">
<div class="student-record-avatar">@if($studentPhotoUrl)<img src="{{ $studentPhotoUrl }}" alt="{{ __('Student photo') }}">@else{{ mb_strtoupper(mb_substr(trim($student->first_name ?: '?'), 0, 1)) }}@endif</div>
<div class="flex flex-wrap justify-between gap-4"><div><p class="text-2xl font-bold">{{ $student->first_name }} {{ $student->last_name }}</p><p class="text-gray-500">{{ $student->student_number }}</p></div><div class="flex flex-wrap items-center gap-2">@can('update', $student)<a href="{{ route('students.edit', $student) }}" class="inline-flex h-8 items-center rounded-lg border border-emerald-600 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">{{ __('Edit') }}</a>@endcan<a href="{{ route('students.card', $student) }}" class="inline-flex h-8 items-center rounded-lg bg-emerald-700 px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800">{{ __('Student card') }}</a><a href="{{ route('students.index') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-300 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">{{ __('Back') }}</a></div></div>
<div class="mt-6 grid gap-5 md:grid-cols-2"><div><h3 class="font-semibold">{{ __('Enrollment') }}</h3>@foreach($student->enrollments as $enrollment)<p>{{ $enrollment->schoolClass->name }} — {{ $enrollment->schoolClass->academicYear->name }}</p>@endforeach</div><div><h3 class="font-semibold">{{ __('Guardian') }}</h3>@foreach($student->guardians as $guardian)<p>{{ $guardian->first_name }} {{ $guardian->last_name }} · {{ $guardian->phone }}</p>@endforeach</div></div>
@if($canViewFees && $trainingClass?->fee_mode !== 'registration_only')<section class="mt-8 border-t pt-6"><div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold">{{ __('Monthly school fees') }}</h3><p class="text-sm text-gray-500">{{ __('Monthly amount') }}: {{ $student->monthly_fee_amount !== null ? number_format((float) $student->monthly_fee_amount, 0, ',', ' ').' Ar' : '—' }}</p></div>@can('update', $student)<a href="{{ route('students.edit', $student) }}" class="text-sm text-emerald-700">{{ __('Edit monthly fee') }}</a>@endcan</div><div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-2">{{ __('Month') }}</th><th class="p-2">{{ __('Amount') }}</th><th class="p-2">{{ __('Status') }}</th><th class="p-2">{{ __('Action') }}</th></tr></thead><tbody>@foreach($months as $item)@php($paid = (bool) $item['fee']?->paid_at)<tr class="border-b {{ $paid ? 'bg-emerald-50' : '' }}"><td class="p-2">{{ $item['date']->translatedFormat('F Y') }}</td><td class="p-2">{{ $item['amount'] !== null ? number_format((float) $item['amount'], 0, ',', ' ').' Ar' : '—' }}</td><td class="p-2">{{ $paid ? __('Paid') : __('Unpaid') }}</td><td class="p-2">@if($paid && auth()->user()->hasRole('Admin'))<form method="POST" action="{{ route('students.monthly-fees.toggle', $student) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="month" value="{{ $item['date']->format('Y-m') }}"><button type="button" onclick="askFeeConfirmation(this.closest('form'), '{{ __('Cancel payment') }}')" class="text-sm text-gray-400 hover:text-red-700">{{ __('Uncheck') }}</button></form>@elseif(!$paid && auth()->user()->can('payments.manage'))<form method="POST" action="{{ route('students.monthly-fees.toggle', $student) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="month" value="{{ $item['date']->format('Y-m') }}"><label class="inline-flex items-center gap-2"><input type="checkbox" onchange="askFeeConfirmation(this.closest('form'), '{{ __('Confirm payment') }}')"><span>{{ __('Mark as paid') }}</span></label></form>@elseif($paid)<span class="text-gray-400">{{ __('Paid') }}</span>@else<span class="text-gray-400">—</span>@endif</td></tr>@endforeach</tbody></table></div></section>@endif
@if($registrationInvoice)<section class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4"><h3 class="font-semibold text-amber-900">{{ __('Registration fee') }}</h3><p class="mt-1 text-sm">{{ __('Total') }} : {{ number_format((float)$registrationInvoice->amount_due,0,',',' ') }} Ar · {{ __('Paid') }} : {{ number_format((float)$registrationInvoice->amount_paid,0,',',' ') }} Ar · {{ __('Remaining') }} : {{ number_format(max(0,(float)$registrationInvoice->amount_due-(float)$registrationInvoice->amount_paid),0,',',' ') }} Ar</p><a class="registration-receipt-link mt-2 inline-block text-sm font-medium text-emerald-700" href="{{ route('school-fees.registration.receipt', [$trainingClass, $student]) }}">{{ __('View receipt / invoice') }}</a></section>@endif
@if($canViewFees)
<div id="receipt-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="receipt-modal-title">
    <div class="flex w-full max-w-xs flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" style="width: 310px; height: min(78vh, 680px);">
        <div class="flex items-center justify-between border-b px-4 py-3">
            <h3 id="receipt-modal-title" class="font-semibold text-slate-800">{{ __('receipt.button') }}</h3>
            <button type="button" onclick="closeReceiptModal()" class="rounded-lg px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100">{{ __('Close') }}</button>
        </div>
        <iframe id="receipt-preview" title="{{ __('Receipt preview') }}" class="min-h-0 flex-1 bg-slate-100" src="about:blank"></iframe>
        <div class="flex flex-wrap justify-end gap-3 border-t bg-slate-50 px-4 py-3">
            <button type="button" onclick="printReceipt()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-white">{{ __('Print') }}</button>
            <a id="receipt-download" href="#" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">{{ __('Download') }}</a>
        </div>
    </div>
</div>
<iframe id="receipt-print-frame" class="hidden" title="{{ __('Receipt print') }}" src="about:blank"></iframe>
<script>
    function openReceiptModal(previewUrl, pdfUrl) {
        const modal = document.getElementById('receipt-modal');
        document.getElementById('receipt-preview').src = previewUrl;
        document.getElementById('receipt-download').href = pdfUrl + (pdfUrl.includes('?') ? '&' : '?') + 'download=1';
        modal.dataset.pdfUrl = pdfUrl;
        modal.classList.remove('hidden'); modal.classList.add('flex');
    }
    function closeReceiptModal() {
        const modal = document.getElementById('receipt-modal');
        modal.classList.add('hidden'); modal.classList.remove('flex');
        document.getElementById('receipt-preview').src = 'about:blank';
    }
    function printReceipt() {
        const frame = document.getElementById('receipt-print-frame');
        frame.onload = () => setTimeout(() => { frame.contentWindow?.focus(); frame.contentWindow?.print(); }, 350);
        frame.src = document.getElementById('receipt-modal').dataset.pdfUrl;
    }
    document.addEventListener('DOMContentLoaded', () => {
        const links = @json($receiptUrls);
        document.querySelectorAll('section.mt-8 table tbody tr').forEach((row, index) => {
            if (!links[index]) return;
            const cell = row.querySelector('td:last-child');
            const receipt = document.createElement('button');
            receipt.type = 'button';
            receipt.className = 'ml-3 inline-block rounded bg-slate-800 px-3 py-1.5 text-xs text-white hover:bg-slate-700';
            receipt.textContent = '{{ __('receipt.button') }}';
            receipt.addEventListener('click', () => openReceiptModal(links[index].preview, links[index].pdf));
            cell.appendChild(receipt);
        });
        document.querySelectorAll('.registration-receipt-link').forEach(link => link.addEventListener('click', event => {
            event.preventDefault();
            openReceiptModal(link.href + '-preview', link.href);
        }));
    });
</script>
@endif
</div></div>
<div id="fee-confirmation" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"><div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl"><h3 id="fee-title" class="text-lg font-semibold"></h3><p class="mt-2 text-sm text-gray-600">{{ __('Please confirm this monthly school fee action.') }}</p><div class="mt-5 flex justify-end gap-3"><button type="button" onclick="closeFeeConfirmation()" class="rounded px-4 py-2">{{ __('Cancel') }}</button><button type="button" onclick="submitFeeConfirmation()" class="rounded bg-emerald-700 px-4 py-2 text-white">{{ __('Confirm') }}</button></div></div></div>
<script>let feeForm=null,feeBox=null,feePreviousState=false;function openFeeConfirmation(form,title,box){feeForm=form;feeBox=box;feePreviousState=box?!box.checked:false;document.getElementById('fee-title').textContent=title;document.getElementById('fee-confirmation').classList.remove('hidden');document.getElementById('fee-confirmation').classList.add('flex')}function closeFeeConfirmation(){if(feeBox)feeBox.checked=feePreviousState;document.getElementById('fee-confirmation').classList.add('hidden');document.getElementById('fee-confirmation').classList.remove('flex');feeForm=null;feeBox=null}function submitFeeConfirmation(){if(feeForm)feeForm.submit()}</script>
<div id="fee-amount-required" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40 p-4"><div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl"><h3 class="text-lg font-semibold">{{ __('Monthly fee not defined') }}</h3><p class="mt-2 text-sm text-gray-600">{{ __('Define the monthly school fee amount before marking a month as paid.') }}</p><div class="mt-5 flex justify-end gap-3"><button type="button" onclick="closeAmountRequired()" class="rounded px-4 py-2">{{ __('Cancel') }}</button>@can('update', $student)<a href="{{ route('students.edit', $student) }}" class="rounded bg-emerald-700 px-4 py-2 text-white">{{ __('Set amount') }}</a>@endcan</div></div></div>
<script>const monthlyFeeAmount={{ (float) ($student->monthly_fee_amount ?? 0) }};function askFeeConfirmation(form,title,box=null){box=box||form.querySelector('input[type=checkbox]');if(box&&box.checked&&monthlyFeeAmount<=0){box.checked=false;document.getElementById('fee-amount-required').classList.remove('hidden');document.getElementById('fee-amount-required').classList.add('flex');return}openFeeConfirmation(form,title,box)}function closeAmountRequired(){document.getElementById('fee-amount-required').classList.add('hidden');document.getElementById('fee-amount-required').classList.remove('flex')}</script>
@if(auth()->user()->hasRole('Admin'))
<div class="mx-auto mb-8 max-w-5xl px-4 text-right sm:px-6 lg:px-8"><button id="open-student-delete" type="button" class="rounded border border-red-300 px-4 py-2 text-red-700">{{ __('Delete student permanently') }}</button><form id="student-delete-form" method="POST" action="{{ route('students.destroy-permanently', $student) }}">@csrf @method('DELETE')</form></div>
<div id="student-delete-modal" class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/50 p-4"><div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl"><h3 class="text-lg font-semibold text-red-700">{{ __('Delete student permanently') }}</h3><p class="mt-2 text-sm text-gray-600">{{ __('This permanently deletes the student record and cannot be undone.') }}</p><label class="mt-4 block text-sm">{{ __('Administrator password') }}<input id="student-delete-password" type="password" class="mt-1 w-full rounded border-gray-300"></label><div class="mt-5 flex justify-end gap-3"><button id="close-student-delete" type="button" class="rounded px-4 py-2">{{ __('Cancel') }}</button><button id="confirm-student-delete" type="button" class="rounded bg-red-700 px-4 py-2 text-white">{{ __('Delete permanently') }}</button></div></div></div>
<script>document.addEventListener('DOMContentLoaded',()=>{const modal=document.getElementById('student-delete-modal'),password=document.getElementById('student-delete-password');document.getElementById('open-student-delete')?.addEventListener('click',()=>{modal.classList.remove('hidden');modal.classList.add('flex');password.focus()});document.getElementById('close-student-delete')?.addEventListener('click',()=>{modal.classList.add('hidden');modal.classList.remove('flex');password.value=''});document.getElementById('confirm-student-delete')?.addEventListener('click',()=>{const form=document.getElementById('student-delete-form');let input=form.querySelector('[name=password_confirmation_action]');if(!input){input=document.createElement('input');input.type='hidden';input.name='password_confirmation_action';form.appendChild(input)}input.value=password.value;form.submit()})})</script>
@endif
<section id="student-card-preview" class="hidden" aria-hidden="true">
    @php($primaryGuardian = $student->guardians->first())
    @php($cardClass = $trainingClass?->name ?? '—')
    <div class="mx-auto flex w-full max-w-3xl flex-col overflow-y-auto rounded-2xl bg-white p-4 shadow-2xl sm:p-6" style="position:relative;z-index:1;min-height:0;height:min(90vh,900px);max-height:90vh;overflow-y:auto;-webkit-overflow-scrolling:touch">
        <div class="sticky top-0 z-10 mb-5 flex items-center justify-between rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur">
            <h3 id="student-card-title" class="text-lg font-bold text-slate-900">{{ __('Student card') }}</h3>
            <button type="button" onclick="closeStudentCardPreview()" class="rounded-lg px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100">{{ __('Close') }}</button>
        </div>
        <div class="grid justify-items-center gap-7">
            <div class="w-full">
                <p class="mb-2 text-sm font-semibold text-slate-600">{{ __('Front') }}</p>
                <div id="student-card-front" class="student-card-canvas relative mx-auto overflow-hidden rounded-md bg-white shadow-lg" style="display:block!important;width:min(100%,640px)!important;height:auto!important;aspect-ratio:1050/630;min-height:330px">
                    <div class="absolute inset-y-0 left-0 w-[39%] bg-[#1557b0]"></div>
                    <div class="absolute right-[4%] top-[3%] w-[5%]"><img src="{{ asset('images/gut-logo.png') }}" alt="GUT Center" class="block h-auto w-full object-contain"></div>
                    <div class="absolute top-[5%] left-[27%] w-[48%] text-center text-[clamp(12px,2.6vw,28px)] font-extrabold tracking-tight text-slate-900">GUT CENTER AMBILOBE</div>
                    @if($studentPhotoUrl)<img src="{{ $studentPhotoUrl }}" alt="{{ __('Student photo') }}" class="absolute rounded-full border-4 border-white object-cover" style="left:11%;top:26.8%;width:21.6%;height:36%;object-position:center">@else<div class="absolute flex items-center justify-center rounded-full border-4 border-white bg-white text-3xl font-bold text-[#007e3a]" style="left:11%;top:26.8%;width:21.6%;height:36%">{{ mb_strtoupper(mb_substr($student->first_name,0,1)) }}</div>@endif
                    <div class="absolute text-center font-bold text-white" style="left:8%;top:66.8%;width:28%;font-size:clamp(10px,1.6vw,22px)">{{ mb_strtoupper($student->first_name.' '.$student->last_name) }}</div>
                    <div class="absolute text-center text-slate-100" style="left:8%;top:74%;width:28%;font-size:clamp(10px,1.6vw,21px)">{{ __('Student') }}</div>
                    <div class="absolute text-center font-extrabold text-white" style="left:8%;top:80%;width:28%;font-size:clamp(10px,1.5vw,20px)">{{ $student->student_number }}</div>
                    <div class="absolute text-center font-bold text-white" style="left:8%;top:87%;width:28%;font-size:clamp(9px,1.35vw,18px)">{{ mb_strtoupper($cardClass) }}</div>
                    <div class="absolute text-slate-700" style="left:47.5%;top:35.5%;width:47%;font-size:clamp(9px,1.25vw,17px)"><div class="mb-1">{{ __('Birth date') }}: <strong class="text-slate-900">{{ $student->birth_date?->translatedFormat('d F Y') ?? '—' }}</strong></div><div class="mb-2 rounded-md bg-red-600 px-2 py-1 text-center font-bold text-white">{{ __('Personal information') }}</div><div class="mb-1">{{ __('Address') }}: <strong class="text-slate-900">{{ $student->address ?: '—' }}</strong></div><div class="mb-1">{{ __('Function') }}: <strong class="text-slate-900">{{ __('Student') }}</strong></div><div>{{ __('Guardian phone') }}: <strong class="text-slate-900">{{ $primaryGuardian?->phone ?: '—' }}</strong></div></div>
                </div>
            </div>
            <div class="w-full">
                <p class="mb-2 text-sm font-semibold text-slate-600">{{ __('Back') }}</p>
                <div id="student-card-back" class="student-card-canvas relative mx-auto overflow-hidden rounded-md bg-white shadow-lg" style="display:block!important;width:min(100%,640px)!important;height:auto!important;aspect-ratio:1050/600;min-height:300px">
                    <div class="absolute left-[4%] top-[3%] w-[5%]"><img src="{{ asset('images/gut-logo.png') }}" alt="GUT Center" class="block h-auto w-full object-contain"></div>
                    <div class="absolute right-[4%] top-[3%] w-[5%]"><img src="{{ asset('images/gut-logo.png') }}" alt="GUT Center" class="block h-auto w-full object-contain"></div>
                    <div class="absolute top-[5%] left-[25%] w-[50%] text-center text-[clamp(12px,2.8vw,30px)] font-extrabold text-slate-900">GUT CENTER AMBILOBE</div>
                    <img src="data:image/svg+xml;base64,{{ $studentQr }}" alt="QR" class="absolute rounded bg-white p-1" style="left:39.7%;top:19%;width:21%;height:43%">
                    <div class="absolute bottom-[1%] left-0 right-0 bg-gradient-to-r from-blue-700 to-violet-500 py-[1.2%] text-center text-[clamp(10px,2vw,22px)] font-extrabold text-slate-900">GO UP TOGETHER - LANGUAGES TRAINING</div>
                </div>
            </div>
        </div>
        <a href="{{ route('students.card', $student) }}" class="mt-6 block w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-emerald-800">{{ __('Download student card PDF') }}</a>
    </div>
</section>
<script>
function openStudentCardPreview(){const modal=document.getElementById('student-card-preview');if(!modal)return;modal.classList.remove('hidden');modal.classList.add('flex');modal.style.setProperty('display','flex','important');document.body.classList.add('overflow-hidden')}
function closeStudentCardPreview(){const modal=document.getElementById('student-card-preview');if(!modal)return;modal.classList.add('hidden');modal.classList.remove('flex');modal.style.setProperty('display','none','important');document.body.classList.remove('overflow-hidden')}
document.getElementById('student-card-preview')?.addEventListener('click',event=>{if(event.target.id==='student-card-preview')closeStudentCardPreview()});
</script>
</x-app-layout>
