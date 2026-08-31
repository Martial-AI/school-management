<x-app-layout>
<x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">{{ __('schoolfees.title') }}</h2></x-slot>

<div class="mx-auto max-w-7xl py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-start gap-3">
        <div class="rounded-xl bg-red-50 px-4 py-3 shadow-sm ring-1 ring-red-100"><p class="text-xs font-medium text-red-700">{{ __('Overdue school fees') }}</p><p class="mt-1 whitespace-nowrap text-xl font-bold text-red-700">{{ number_format($totalOverdue, 0, ',', ' ') }} Ar</p></div>
        <div class="rounded-xl bg-emerald-50 px-4 py-3 shadow-sm ring-1 ring-emerald-100"><p class="text-xs font-medium text-emerald-700">{{ __('Amount collected') }}</p><p class="mt-1 whitespace-nowrap text-xl font-bold text-emerald-700">{{ number_format($collectedAmount, 0, ',', ' ') }} Ar</p></div>
        <form method="GET" class="ml-auto flex items-end gap-3 rounded-xl bg-white px-4 py-3 shadow-sm" style="width:380px">
            <input type="hidden" name="class" value="{{ $selectedClass?->id }}">
            <label class="flex-1 text-sm font-medium text-slate-700">{{ __('Search') }}<input name="search" value="{{ $search }}" placeholder="{{ __('Name or student number') }}" class="mt-1 block w-full rounded-lg border-slate-300"></label>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white">{{ __('Search') }}</button>
        </form>
    </div>

    <div class="mb-6 rounded-2xl bg-white p-5 shadow-sm">
        <p class="mb-3 text-sm font-medium text-slate-700">{{ __('Classes') }}</p>
        <div class="fee-class-buttons flex flex-wrap gap-2">
            @forelse($classes as $class)
                <a href="{{ route('school-fees.index', ['class' => $class->id, 'search' => $search]) }}" class="rounded-full px-4 py-2 text-sm font-medium {{ $selectedClass?->id === $class->id && !$overdueOnly ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-emerald-50' }}">{{ $class->name }}</a>
            @empty
                <span class="text-sm text-slate-500">{{ __('No class.') }}</span>
            @endforelse
        </div>
    </div>

    @if($overdueOnly)
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-red-100">
            <div class="border-b border-red-100 px-5 py-4"><h3 class="font-semibold text-slate-900">{{ __('schoolfees.overdue') }}</h3><p class="text-sm text-slate-500">{{ $overdueEnrollments->count() }} {{ __('student(s)') }}</p></div>
            <div class="divide-y divide-slate-100">
                @forelse($overdueEnrollments as $enrollment)
                    @php($student = $enrollment->student)
                    @php($fees = $student->monthlyFees->keyBy(fn ($item) => $item->fee_month->format('Y-m')))
                    <article class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div><p class="font-semibold text-slate-800">{{ $student->first_name }} {{ $student->last_name }}</p><p class="text-sm text-slate-500">{{ $student->student_number }} <span class="ml-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $enrollment->schoolClass?->name }}</span></p></div>
                        </div>
                        <div class="mt-4 flex gap-3 overflow-x-auto pb-2">
                            @foreach($overdueMonths[$student->id] ?? [] as $overdueMonth)
                                @php($feeDate = \Illuminate\Support\Carbon::createFromFormat('Y-m', $overdueMonth))
                                <div class="fee-overdue-month min-w-[88px] rounded-lg border border-red-300 bg-red-50 px-2 py-1.5 text-red-800"><div class="flex items-center justify-between gap-1.5"><p class="text-xs font-medium">{{ $feeDate->locale(app()->getLocale())->translatedFormat('M Y') }}</p><form method="POST" action="{{ route('school-fees.toggle', $student) }}">@csrf @method('PATCH')<input type="hidden" name="month" value="{{ $overdueMonth }}"><input type="checkbox" class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-700" @disabled(!auth()->user()->can('payments.manage'))></form></div></div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="p-10 text-center text-slate-500">{{ __('No student.') }}</div>
                @endforelse
            </div>
        </section>
    @elseif($selectedClass)
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
            <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-semibold text-slate-900">{{ $selectedClass->name }}</h3><p class="text-sm text-slate-500">{{ __('schoolfees.payment_deadline') }} : {{ $selectedClass->monthly_fee_due_day ?: 5 }} {{ __('schoolfees.day_of_month') }}</p></div>
            <div class="divide-y divide-slate-100">
                @forelse($selectedClass->enrollments as $enrollment)
                    @php($student = $enrollment->student)
                    @php($fees = $student->monthlyFees->keyBy(fn ($item) => $item->fee_month->format('Y-m')))
                    <article class="p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold text-slate-800">{{ $student->first_name }} {{ $student->last_name }}</p><p class="text-sm text-slate-500">{{ $student->student_number }} &middot; {{ number_format((float) $student->monthly_fee_amount, 0, ',', ' ') }} Ar / {{ __('Month') }}</p></div></div><div class="mt-4 flex gap-3 overflow-x-auto pb-2">
                        @foreach($feeMonths as $feeMonth)
                            @php($fee = $fees->get($feeMonth->format('Y-m')))
                            @php($paid = (bool) $fee?->paid_at)
                            <div @if($paid && $fee) data-receipt-url="{{ route('students.monthly-fees.receipt', [$student, $fee]) }}" @endif class="min-w-[88px] rounded-lg border px-2 py-1.5 {{ $paid ? 'cursor-pointer border-slate-200 bg-slate-100 text-slate-400 hover:bg-slate-200' : 'border-emerald-100 bg-white' }}"><div class="flex items-center justify-between gap-1.5"><p class="text-xs font-medium">{{ $feeMonth->locale(app()->getLocale())->translatedFormat('M Y') }}</p><form method="POST" action="{{ route('school-fees.toggle', $student) }}">@csrf @method('PATCH')<input type="hidden" name="month" value="{{ $feeMonth->format('Y-m') }}"><input type="checkbox" class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-700" @checked($paid) @disabled($paid ? !auth()->user()->hasRole('Admin') : !auth()->user()->can('payments.manage'))></form></div></div>
                        @endforeach
                    </div></article>
                @empty
                    <div class="p-10 text-center text-slate-500">{{ __('No student.') }}</div>
                @endforelse
            </div>
        </section>
    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center text-slate-500">{{ __('schoolfees.choose_class') }}</div>
    @endif
</div>

<div id="fees-receipt-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/50 p-4"><div class="flex w-full max-w-xs flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" style="width:310px;height:min(78vh,680px)"><div class="flex items-center justify-between border-b px-4 py-3"><h3 class="font-semibold">{{ __('receipt.button') }}</h3><button type="button" onclick="closeFeesReceipt()" class="text-sm">{{ __('Close') }}</button></div><iframe id="fees-receipt-preview" class="min-h-0 flex-1" src="about:blank"></iframe><div class="flex justify-end gap-3 border-t p-3"><button type="button" onclick="printFeesReceipt()" class="rounded border px-3 py-2 text-sm">{{ __('Print') }}</button><a id="fees-receipt-download" class="rounded bg-emerald-700 px-3 py-2 text-sm text-white">{{ __('Download') }}</a></div></div></div><iframe id="fees-receipt-print" class="hidden"></iframe>

<div id="school-fee-confirmation" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 p-4"><div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"><h3 id="school-fee-confirmation-title" class="text-lg font-semibold text-slate-900"></h3><p id="school-fee-confirmation-message" class="mt-2 text-sm text-slate-600">{{ __('Please confirm this monthly school fee action.') }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" onclick="closeSchoolFeeConfirmation()" class="rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-100">{{ __('Cancel') }}</button><button type="button" onclick="submitSchoolFeeConfirmation()" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white">{{ __('Confirm') }}</button></div></div></div>
<div id="registration-fee-details" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/50 p-4"><div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"><div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ __('Registration fee balance') }}</h3><button type="button" onclick="closeRegistrationFeeDetails()" class="text-slate-500" aria-label="{{ __('Close') }}">×</button></div><p class="mt-2 text-sm text-slate-600">{{ __('Set the remaining amount and, if necessary, its personal deadline.') }}</p><form id="registration-fee-details-form" method="POST" class="mt-4"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><label class="block text-sm">{{ __('Remaining amount') }}<input id="registration-fee-remaining" name="remaining_amount" type="number" min="0" step="0.01" required class="mt-1 block w-full rounded-lg border-slate-300"></label><label class="mt-3 block text-sm">{{ __('Deadline (optional)') }}<input id="registration-fee-due-date" name="due_date" type="date" class="mt-1 block w-full rounded-lg border-slate-300"></label><div class="mt-6 flex justify-end gap-3"><button type="button" onclick="closeRegistrationFeeDetails()" class="rounded-lg px-4 py-2 text-sm text-slate-600">{{ __('Cancel') }}</button><button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white">{{ __('Save') }}</button></div></form></div></div>

<style>@keyframes feeOverduePulse{0%,100%{background-color:#fef2f2;opacity:1}50%{background-color:#fecaca;opacity:.62}}.fee-overdue-month{border-color:#ef4444!important;color:#b91c1c!important;animation:feeOverduePulse 1.5s ease-in-out infinite}</style>
<script>
let pendingSchoolFeeForm = null, pendingSchoolFeeBox = null, pendingSchoolFeeState = false;
function openFeesReceipt(url){const modal=document.getElementById('fees-receipt-modal');document.getElementById('fees-receipt-preview').src=url+'-preview';document.getElementById('fees-receipt-download').href=url+'?download=1';modal.dataset.pdfUrl=url;modal.classList.remove('hidden');modal.classList.add('flex')}
function closeFeesReceipt(){const modal=document.getElementById('fees-receipt-modal');modal.classList.add('hidden');modal.classList.remove('flex')}
function printFeesReceipt(){const frame=document.getElementById('fees-receipt-print');frame.onload=()=>setTimeout(()=>frame.contentWindow?.print(),350);frame.src=document.getElementById('fees-receipt-modal').dataset.pdfUrl}
function openSchoolFeeConfirmation(box){pendingSchoolFeeBox=box;pendingSchoolFeeForm=box.form;pendingSchoolFeeState=!box.checked;const registration=box.dataset.paymentKind==='registration';document.getElementById('school-fee-confirmation-title').textContent=box.checked?(registration?@json(__('Confirm registration fee payment')):@json(__('Confirm payment'))):(registration?@json(__('Cancel registration fee payment')):@json(__('Cancel payment')));document.getElementById('school-fee-confirmation-message').textContent=registration?@json(__('Please confirm this registration fee action.')):@json(__('Please confirm this monthly school fee action.'));const modal=document.getElementById('school-fee-confirmation');modal.classList.remove('hidden');modal.classList.add('flex')}
function closeSchoolFeeConfirmation(){if(pendingSchoolFeeBox)pendingSchoolFeeBox.checked=pendingSchoolFeeState;const modal=document.getElementById('school-fee-confirmation');modal.classList.add('hidden');modal.classList.remove('flex');pendingSchoolFeeForm=null;pendingSchoolFeeBox=null}
function submitSchoolFeeConfirmation(){if(pendingSchoolFeeForm)pendingSchoolFeeForm.submit()}
function openRegistrationFeeDetails(row){const form=document.getElementById('registration-fee-details-form');form.action=row.details_url;document.getElementById('registration-fee-remaining').value=row.remaining;document.getElementById('registration-fee-due-date').value=row.due_date_value||'';const modal=document.getElementById('registration-fee-details');modal.classList.remove('hidden');modal.classList.add('flex')}
function closeRegistrationFeeDetails(){const modal=document.getElementById('registration-fee-details');modal.classList.add('hidden');modal.classList.remove('flex')}
document.addEventListener('DOMContentLoaded',()=>{
    const filter=document.createElement('a');filter.href=@json($overdueFilterUrl);filter.className='rounded-xl px-4 py-3 text-sm font-semibold '+(@json($overdueOnly)?'bg-red-700 text-white':'bg-red-50 text-red-700 ring-1 ring-red-100');filter.textContent='{{ __('schoolfees.overdue') }} ('+@json(count($overdueStudentIds))+')';filter.classList.add('ml-auto');document.querySelector('.fee-class-buttons')?.appendChild(filter);
    const registrationOnly=@json($selectedClass?->fee_mode === 'registration_only');
    const registrationRows=@json($registrationFeeRowsJson);
    const canManageRegistration=@json(auth()->user()->can('payments.manage'));
    if(registrationOnly){const locale=@json(app()->getLocale()==='fr'?'fr-FR':'en-US'),paidInFull=@json(__('Registration fee paid in full')),overdueBalance=@json(__('Registration fee balance overdue')),balanceDue=@json(__('Registration fee balance due')),remainingLabel=@json(__('Remaining')),deadlineLabel=@json(__('Deadline')),toBeDefined=@json(__('To be defined')),viewReceipt=@json(__('View receipt'));document.querySelectorAll('article').forEach((article,index)=>{const row=registrationRows[index];const area=article.querySelector('div.mt-4');if(!row||!area)return;const state=row.paid?paidInFull:(row.overdue?overdueBalance:balanceDue);const color=row.paid?'border-emerald-200 bg-emerald-50 text-emerald-800':(row.overdue?'border-red-300 bg-red-50 text-red-800':'border-amber-200 bg-amber-50 text-amber-800');const checked=row.paid?'checked':'';const disabled=row.paid?(@json(auth()->user()->hasRole('Admin'))?'':'disabled'):(canManageRegistration?'':'disabled');const details=canManageRegistration?`<button type="button" class="rounded border border-slate-300 px-2 py-1 text-[10px] text-slate-700" data-registration-details>${remainingLabel}</button>`:'';const receipt=row.receipt_url?`data-registration-receipt="${row.receipt_url}" title="${viewReceipt}"`:'';area.innerHTML=`<div ${receipt} class="min-w-[170px] rounded-lg border px-3 py-2 ${color} ${row.receipt_url?'cursor-pointer':''}"><div class="flex items-center gap-2"><span class="text-xs font-semibold">${state}</span><form method="POST" action="${row.toggle_url}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><input type="checkbox" data-payment-kind="registration" class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-700" ${checked} ${disabled}></form>${details}</div><p class="mt-1 text-xs">${remainingLabel} : ${Number(row.remaining).toLocaleString(locale)} Ar</p><p class="text-xs">${deadlineLabel} : ${row.due_date||toBeDefined}</p></div>`;const box=area.querySelector('input[type="checkbox"]');if(box)box.onchange=()=>openSchoolFeeConfirmation(box);area.querySelector('[data-registration-details]')?.addEventListener('click',()=>openRegistrationFeeDetails(row));area.querySelector('[data-registration-receipt]')?.addEventListener('click',event=>{if(!event.target.closest('input,form,button'))openFeesReceipt(row.receipt_url)});});}
    document.querySelectorAll('[data-receipt-url]').forEach(card=>card.addEventListener('click',event=>{if(!event.target.closest('input,form,button,a'))openFeesReceipt(card.dataset.receiptUrl)}));
    document.querySelectorAll('article input[type="checkbox"]').forEach(box=>box.onchange=()=>openSchoolFeeConfirmation(box));
    document.querySelectorAll('section > .divide-y > article').forEach(article=>{article.classList.remove('p-5','mx-auto','max-w-4xl');article.classList.add('px-4','py-2')});
    // Keep the fee months on the same row as the student identity, as in the original layout.
    document.querySelectorAll('article').forEach(article=>{const identity=article.firstElementChild;const months=identity?.nextElementSibling;if(!identity||!months?.classList.contains('mt-4'))return;months.classList.remove('mt-4','pb-2');months.classList.add('ml-auto','max-w-full');identity.classList.add('items-center');identity.appendChild(months)});
    const overdueMonths=@json($overdueMonths);document.querySelectorAll('article').forEach(article=>{const form=article.querySelector('form[action]');const id=Number(form?.action.split('/').filter(Boolean).pop());const months=overdueMonths[id]||[];article.querySelectorAll('form[action]').forEach(item=>{const month=item.querySelector('input[name="month"]')?.value;if(months.includes(month))item.closest('[class*="min-w-"]')?.classList.add('fee-overdue-month')})});
});
</script>
</x-app-layout>
