<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ __('Classes and training duration') }}</h2></x-slot>
    <div class="mx-auto w-full py-8 sm:px-6 lg:px-8" style="max-width: 88rem;">
        <div class="overflow-x-auto rounded-xl bg-white p-6 shadow">
            <div class="mb-5 flex items-center justify-between gap-4"><p class="text-sm text-gray-500">{{ __('Define the duration of each training independently from the academic year.') }}</p><button type="button" onclick="openClassModal()" class="shrink-0 rounded bg-emerald-700 px-4 py-2 text-white">{{ __('Add a class') }}</button></div>
            <table class="w-full text-left text-xs"><thead><tr class="border-b"><th class="p-1.5">{{ __('Class') }}</th><th class="p-1.5">{{ __('Academic year') }}</th><th class="p-1.5">{{ __('Training duration') }}</th><th class="p-1.5">{{ __('Capacity') }}</th><th class="p-1.5">{{ __('Action') }}</th></tr></thead>
                <tbody>@foreach($classes as $class)@php($formId = 'class-'.$class->id)<tr class="border-b"><td class="p-2"><form id="{{ $formId }}" method="POST" action="{{ route('classes.update', $class) }}">@csrf @method('PUT')</form><input form="{{ $formId }}" name="name" value="{{ $class->name }}" class="w-full rounded border-gray-300"><input form="{{ $formId }}" type="hidden" name="level" value="{{ $class->level }}"></td><td class="p-2"><select form="{{ $formId }}" name="academic_year_id" class="w-full rounded border-gray-300">@foreach($years as $year)<option value="{{ $year->id }}" @selected($class->academic_year_id === $year->id)>{{ $year->name }}</option>@endforeach</select></td><td class="p-2"><input form="{{ $formId }}" type="number" name="duration_months" min="1" max="120" value="{{ $class->duration_months }}" required class="w-24 rounded border-gray-300"> {{ __('months') }}</td><td class="p-2"><input form="{{ $formId }}" type="number" name="capacity" min="1" value="{{ $class->capacity }}" class="w-24 rounded border-gray-300"></td><td class="p-2 whitespace-nowrap"><button form="{{ $formId }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800 hover:shadow">✓ {{ __('Save') }}</button></td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>
    <div id="class-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"><div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl"><div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ __('Add a class') }}</h3><button type="button" onclick="closeClassModal()" class="text-xl text-gray-500" aria-label="{{ __('Close') }}">×</button></div><form method="POST" action="{{ route('classes.store') }}" class="mt-5">@csrf<div class="grid gap-4 md:grid-cols-2"><label>{{ __('Academic year') }}<select name="academic_year_id" required class="mt-1 block w-full rounded border-gray-300"><option value="">—</option>@foreach($years as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select></label><label>{{ __('Class') }}<input name="name" required class="mt-1 block w-full rounded border-gray-300"></label><label>{{ __('Level') }}<input name="level" class="mt-1 block w-full rounded border-gray-300"></label><label>{{ __('Training duration (months)') }}<input type="number" name="duration_months" min="1" max="120" required class="mt-1 block w-full rounded border-gray-300"></label><label>{{ __('Capacity') }}<input type="number" name="capacity" min="1" class="mt-1 block w-full rounded border-gray-300"></label></div><div class="mt-6 flex justify-end gap-3"><button type="button" onclick="closeClassModal()" class="rounded border px-4 py-2">{{ __('Cancel') }}</button><button class="rounded bg-emerald-700 px-4 py-2 text-white">{{ __('Save') }}</button></div></form></div></div>
    <script>function openClassModal(){document.getElementById('class-modal').classList.remove('hidden');document.getElementById('class-modal').classList.add('flex')}function closeClassModal(){document.getElementById('class-modal').classList.add('hidden');document.getElementById('class-modal').classList.remove('flex')}</script>
<script>const classStudentLinks=@json($classes->map(fn($class)=>['url'=>route('classes.show',$class),'count'=>$class->active_students_count])->values()),studentsLabel=@json(__('Students'));document.querySelectorAll('table tbody tr').forEach((row,index)=>{const info=classStudentLinks[index];if(!info)return;const link=document.createElement('a');link.href=info.url;link.className='mt-2 block w-fit px-0 py-0 text-xs font-semibold text-sky-700 transition hover:text-sky-900';link.textContent=`${studentsLabel} (${info.count})`;row.querySelector('td:last-child').appendChild(link)});</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dueDays = @json($classes->pluck('monthly_fee_due_day')->values());
    const feeModes = @json($classes->pluck('fee_mode')->values());
    const registrationAmounts = @json($classes->pluck('registration_fee_amount')->values());
    const monthlyAmounts = @json($classes->pluck('monthly_fee_amount')->values());
    const trainingStarts = @json($classes->pluck('training_starts_on')->map(fn ($date) => $date?->format('Y-m-d'))->values());
    const trainingEnds = @json($classes->pluck('training_ends_on')->map(fn ($date) => $date?->format('Y-m-d'))->values());
    const table = document.querySelector('.overflow-x-auto table');
    if (!table) return;
    const actionHeader = table.querySelector('thead tr th:last-child');
    const startHeader = document.createElement('th'); startHeader.className = 'p-2'; startHeader.textContent = '{{ __('Start') }}'; actionHeader.before(startHeader);
    const endHeader = document.createElement('th'); endHeader.className = 'p-2'; endHeader.textContent = '{{ __('End') }}'; actionHeader.before(endHeader);
    const dueHeader = document.createElement('th');
    dueHeader.className = 'p-2'; dueHeader.textContent = '{{ __('schoolfees.payment_deadline') }}';
    actionHeader.before(dueHeader);
    const feeHeader = document.createElement('th'); feeHeader.className = 'p-2'; feeHeader.textContent = @json(__('Payment mode')); actionHeader.before(feeHeader);
    table.querySelectorAll('tbody tr').forEach((row, index) => {
        const form = row.querySelector('form[id^="class-"]');
        if (!form) return;
        const startCell = document.createElement('td'); startCell.className = 'p-1.5'; startCell.innerHTML = `<input form="${form.id}" type="date" name="training_starts_on" value="${trainingStarts[index] || ''}" required class="w-28 rounded border-gray-300 text-xs">`; row.querySelector('td:last-child').before(startCell);
        const endCell = document.createElement('td'); endCell.className = 'p-1.5'; endCell.innerHTML = `<input form="${form.id}" type="date" name="training_ends_on" value="${trainingEnds[index] || ''}" required class="w-28 rounded border-gray-300 text-xs">`; row.querySelector('td:last-child').before(endCell);
        const dueCell = document.createElement('td'); dueCell.className = 'p-1.5';
        dueCell.innerHTML = `<label class="inline-flex items-center gap-1 text-xs"><input form="${form.id}" type="number" min="1" max="31" name="monthly_fee_due_day" value="${dueDays[index] || 5}" class="w-14 rounded border-gray-300"> / {{ __('Month') }}</label>`;
        row.querySelector('td:last-child').before(dueCell);
        const feeCell = document.createElement('td'); feeCell.className = 'p-1.5';
        feeCell.innerHTML = `<select form="${form.id}" name="fee_mode" class="w-32 rounded border-gray-300 text-xs"><option value="monthly">{{ __('Monthly school fees option') }}</option><option value="registration_only">{{ __('Registration fee only') }}</option></select><input form="${form.id}" type="number" min="0" step="0.01" name="monthly_fee_amount" value="${monthlyAmounts[index] || ''}" placeholder="{{ __('School fee/month') }}" class="mt-1 w-24 rounded border-gray-300 text-xs"><input form="${form.id}" type="number" min="0" step="0.01" name="registration_fee_amount" value="${registrationAmounts[index] || ''}" placeholder="{{ __('Registration fee amount') }}" class="mt-1 w-24 rounded border-gray-300 text-xs"><input form="${form.id}" type="hidden" name="registration_fee_due_date" value="">`;
        feeCell.querySelector('select').value = feeModes[index] || 'monthly';
        row.querySelector('td:last-child').before(feeCell);
        bindTrainingDates(form);
    });
    const createForm = document.querySelector('#class-modal form');
    if (createForm) {
        const label = document.createElement('label');
        label.innerHTML = `{{ __('schoolfees.payment_deadline') }}<input type="number" name="monthly_fee_due_day" min="1" max="31" value="5" required class="mt-1 block w-full rounded border-gray-300"><span class="text-xs text-gray-500">{{ __('schoolfees.day_of_month') }}</span>`;
        createForm.querySelector('.grid').appendChild(label);
        const startLabel = document.createElement('label'); startLabel.innerHTML = `{{ __('Start') }}<input type="date" name="training_starts_on" required class="mt-1 block w-full rounded border-gray-300">`; createForm.querySelector('.grid').appendChild(startLabel);
        const endLabel = document.createElement('label'); endLabel.innerHTML = `{{ __('End') }}<input type="date" name="training_ends_on" required class="mt-1 block w-full rounded border-gray-300">`; createForm.querySelector('.grid').appendChild(endLabel);
        const feeModeLabel = document.createElement('label'); feeModeLabel.innerHTML = `{{ __('Payment mode') }}<select name="fee_mode" class="mt-1 block w-full rounded border-gray-300"><option value="monthly">{{ __('Monthly school fees option') }}</option><option value="registration_only">{{ __('Registration fee only') }}</option></select>`; createForm.querySelector('.grid').appendChild(feeModeLabel);
        const monthlyAmountLabel = document.createElement('label'); monthlyAmountLabel.innerHTML = `{{ __('Monthly school fee') }}<input type="number" min="0" step="0.01" name="monthly_fee_amount" class="mt-1 block w-full rounded border-gray-300">`; createForm.querySelector('.grid').appendChild(monthlyAmountLabel);
        const amountLabel = document.createElement('label'); amountLabel.innerHTML = `{{ __('Registration fee amount') }}<input type="number" min="0" step="0.01" name="registration_fee_amount" class="mt-1 block w-full rounded border-gray-300">`; createForm.querySelector('.grid').appendChild(amountLabel);
        const registrationDueField = document.createElement('input'); registrationDueField.type = 'hidden'; registrationDueField.name = 'registration_fee_due_date'; registrationDueField.value = ''; createForm.appendChild(registrationDueField);
        bindTrainingDates(createForm);
    }

    function bindTrainingDates(form) {
        const field = (name) => form.id ? document.querySelector(`[form="${form.id}"][name="${name}"]`) : form.querySelector(`[name="${name}"]`);
        const start = field('training_starts_on');
        const end = field('training_ends_on');
        const duration = field('duration_months');
        if (!start || !end || !duration) return;
        const monthsBetween = () => {
            if (!start.value || !end.value) return;
            const a = new Date(`${start.value}T00:00:00`), b = new Date(`${end.value}T00:00:00`);
            duration.value = Math.max(1, (b.getFullYear() - a.getFullYear()) * 12 + b.getMonth() - a.getMonth());
        };
        const moveEnd = () => {
            if (!start.value || !duration.value) return;
            const date = new Date(`${start.value}T00:00:00`);
            date.setMonth(date.getMonth() + Number(duration.value));
            end.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        };
        start.addEventListener('change', monthsBetween);
        end.addEventListener('change', monthsBetween);
        duration.addEventListener('change', moveEnd);
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const syncFeeMode = (select) => {
        const form = select.closest('form');
        const row = select.closest('tr');
        const monthly = form?.querySelector('[name="monthly_fee_amount"]') || row?.querySelector('[name="monthly_fee_amount"]');
        const registration = form?.querySelector('[name="registration_fee_amount"]') || row?.querySelector('[name="registration_fee_amount"]');
        const due = form?.querySelector('[name="monthly_fee_due_day"]') || row?.querySelector('[name="monthly_fee_due_day"]');
        const monthlyLabel = monthly?.closest('label');
        const registrationLabel = registration?.closest('label');
        const dueLabel = due?.closest('label');
        const dueCell = due?.closest('td');
        if (dueCell) dueCell.dataset.feeDueContainer = '1';
        if (dueLabel) dueLabel.dataset.feeDueContainer = '1';
        const isMonthly = select.value === 'monthly';
        const dueContainer = dueCell || dueLabel || row?.querySelector('[data-fee-due-container]') || form?.querySelector('[data-fee-due-container]');
        if (dueContainer && !dueContainer.dataset.originalFeeDueMarkup) dueContainer.dataset.originalFeeDueMarkup = dueContainer.innerHTML;
        if (monthlyLabel) monthlyLabel.classList.toggle('hidden', !isMonthly);
        else if (monthly) monthly.classList.toggle('hidden', !isMonthly);
        if (registrationLabel) registrationLabel.classList.toggle('hidden', isMonthly);
        else if (registration) registration.classList.toggle('hidden', isMonthly);
        if (dueContainer) {
            dueContainer.classList.remove('hidden');
            dueContainer.innerHTML = isMonthly ? dueContainer.dataset.originalFeeDueMarkup : '<span class="text-gray-500">—</span>';
        }
        if (monthly) monthly.disabled = !isMonthly;
        if (registration) registration.disabled = isMonthly;
        if (due) due.disabled = !isMonthly;
        if (!isMonthly) { if (monthly) monthly.value = ''; if (due) due.value = ''; }
        else if (registration) registration.value = '';
    };
    document.querySelectorAll('select[name="fee_mode"]').forEach(select => {
        syncFeeMode(select);
        select.addEventListener('change', () => syncFeeMode(select));
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('button[form^="class-"]').forEach(button=>{button.setAttribute('aria-label',@json(__('Save')));button.title=@json(__('Save'));button.className='inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800 hover:shadow';button.innerHTML='<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3.75h11.2L19.25 6.8v13.45H5V3.75Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 3.75v5h7v-5M8 20.25v-5.5h8v5.5"/></svg>'})});
</script>
<style>
    .overflow-x-auto table th,
    .overflow-x-auto table td,
    .overflow-x-auto table input,
    .overflow-x-auto table select,
    .overflow-x-auto table button,
    .overflow-x-auto table a { font-size: 0.75rem; line-height: 1rem; }
    .overflow-x-auto table th, .overflow-x-auto table td { text-align: center; vertical-align: middle; }
</style>
<script>
document.addEventListener('DOMContentLoaded',()=>{const deadlineFormat=@json(__('On day :day of the month'));document.querySelectorAll('table tbody tr').forEach(row=>{const due=row.querySelector('[name="monthly_fee_due_day"]');if(due){const value=row.querySelector(`[data-fee-due-container] .class-value`);if(value)value.textContent=deadlineFormat.replace(':day',String(due.value||'').padStart(2,'0'))}const mode=row.querySelector('[name="fee_mode"]');if(!mode)return;const feeCell=mode.closest('td');const display=feeCell?.querySelector('.class-value');if(!display)return;const amount=row.querySelector('[name="monthly_fee_amount"]:not(:disabled), [name="registration_fee_amount"]:not(:disabled)');const modeText=mode.options[mode.selectedIndex]?.text||'';const amountValue=amount?.value||'';const formatted=amountValue?`${String(Math.round(Number(amountValue))).replace(/\B(?=(\d{3})+(?!\d))/g,' ')} Ar`:'';display.innerHTML='';const modeSpan=document.createElement('span');modeSpan.className='block';modeSpan.textContent=modeText;display.appendChild(modeSpan);if(formatted){const amountSpan=document.createElement('span');amountSpan.className='mt-1 block font-bold text-gray-500';amountSpan.textContent=formatted;display.appendChild(amountSpan)}})});
</script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const labels={name:@json(__('Class')),academic_year_id:@json(__('Academic year')),duration_months:@json(__('Training duration (months)')),capacity:@json(__('Capacity')),training_starts_on:@json(__('Start')),training_ends_on:@json(__('End')),monthly_fee_due_day:@json(__('schoolfees.payment_deadline')),fee_mode:@json(__('Payment mode')),monthly_fee_amount:@json(__('Monthly school fee')),registration_fee_amount:@json(__('Registration fee amount'))};const monthsLabel=@json(__('months')),deadlineFormat=@json(__('On day :day of the month'));
    const modal=document.createElement('div');modal.id='class-edit-modal';modal.className='fixed inset-0 z-[9999] hidden items-center justify-center p-4';modal.style.zIndex='9999';modal.style.backgroundColor='rgba(0,0,0,0.6)';modal.innerHTML=`<div class="relative z-[10000] w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl"><div class="flex items-center justify-between"><h3 class="text-lg font-semibold">${@json(__('Edit class'))}</h3><button type="button" class="class-edit-close rounded-lg px-3 py-1 text-xl text-gray-500">×</button></div><form class="class-edit-modal-form mt-5"><div class="class-edit-fields grid gap-4 md:grid-cols-2"></div><div class="mt-6 flex justify-end gap-3"><button type="button" class="class-edit-cancel rounded-lg border px-4 py-2">${@json(__('Cancel'))}</button><button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-white">${@json(__('Save'))}</button></div></form></div>`;document.body.appendChild(modal);
    let active=null;const close=()=>{modal.classList.add('hidden');modal.classList.remove('flex');active=null};modal.querySelectorAll('.class-edit-close,.class-edit-cancel').forEach(button=>button.addEventListener('click',close));
    document.querySelectorAll('table tbody tr').forEach(row=>{
        const form=row.querySelector('form[id^="class-"]');const edit=row.querySelector('button[form^="class-"]');if(!form||!edit)return;
        const controls=[...document.querySelectorAll(`[form="${form.id}"]`)].filter(control=>control.name&&!['_token','_method','registration_fee_due_date'].includes(control.name));
        const visibleCells=[...row.querySelectorAll('td')].slice(0,-1);visibleCells.forEach(cell=>{const cellControls=controls.filter(control=>control.closest('td')===cell);if(!cellControls.length)return;const values=cellControls.filter(control=>!control.disabled).map(control=>{const value=control.tagName==='SELECT'?control.options[control.selectedIndex]?.text:control.value;if(control.name==='duration_months')return `${value} ${monthsLabel}`;if(control.name==='monthly_fee_due_day')return deadlineFormat.replace(':day',String(value).padStart(2,'0'));return value}).filter(Boolean);cellControls.forEach(control=>control.classList.add('hidden'));[...cell.childNodes].filter(node=>node.nodeType===3).forEach(node=>node.remove());const display=document.createElement('span');display.className='class-value';display.textContent=values.join(' · ')||'—';cell.appendChild(display)});
        form.classList.add('hidden');edit.removeAttribute('form');edit.type='button';edit.setAttribute('aria-label',@json(__('Edit')));edit.title=@json(__('Edit'));edit.className='inline-flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-600 ring-1 ring-sky-200 shadow-sm transition hover:bg-sky-100 hover:text-sky-800';edit.innerHTML='<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 3.487 3.65 3.65M4 20l4.25-.9L19.7 7.65a2.58 2.58 0 0 0-3.65-3.65L4.6 15.45 4 20Z"/></svg>';
        edit.addEventListener('click',()=>{active={form,controls};const fields=modal.querySelector('.class-edit-fields');fields.innerHTML='';controls.forEach(original=>{const label=document.createElement('label');label.className='block text-sm font-medium text-gray-700';label.textContent=labels[original.name]||original.name;const clone=original.cloneNode(true);clone.removeAttribute('form');clone.disabled=false;clone.className='mt-1 block w-full rounded-lg border-gray-300 text-sm';clone.dataset.originalName=original.name;label.appendChild(clone);fields.appendChild(label)});modal.classList.remove('hidden');modal.classList.add('flex')});
    });
    modal.querySelector('form').addEventListener('submit',event=>{event.preventDefault();if(!active)return;modal.querySelectorAll('[data-original-name]').forEach(clone=>{const original=active.controls.find(control=>control.name===clone.dataset.originalName);if(original){original.disabled=false;original.value=clone.value}});active.form.submit();close()});
});
</script>
<script>
document.addEventListener('DOMContentLoaded',()=>setTimeout(()=>{const deadlineFormat=@json(__('On day :day of the month'));document.querySelectorAll('table tbody tr').forEach(row=>{const due=row.querySelector('[name="monthly_fee_due_day"]');due?.closest('label')?.classList.add('hidden');const dueDisplay=row.querySelector('[data-fee-due-container] .class-value');if(due&&dueDisplay)dueDisplay.textContent=deadlineFormat.replace(':day',String(due.value||'').padStart(2,'0'));const mode=row.querySelector('[name="fee_mode"]');const display=mode?.closest('td')?.querySelector('.class-value');if(!mode||!display)return;const amount=row.querySelector('[name="monthly_fee_amount"]:not(:disabled), [name="registration_fee_amount"]:not(:disabled)');const formatted=amount?.value?`${String(Math.round(Number(amount.value))).replace(/\B(?=(\d{3})+(?!\d))/g,' ')} Ar`:'';display.innerHTML='';const modeSpan=document.createElement('span');modeSpan.className='block';modeSpan.textContent=mode.options[mode.selectedIndex]?.text||'';display.appendChild(modeSpan);if(formatted){const amountSpan=document.createElement('span');amountSpan.className='mt-1 block font-bold text-gray-500';amountSpan.textContent=formatted;display.appendChild(amountSpan)}})},0));
</script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const syncModalFeeFields=()=>{const modal=document.getElementById('class-edit-modal');if(!modal)return;const mode=modal.querySelector('[data-original-name="fee_mode"]');if(!mode)return;const monthly=modal.querySelector('[data-original-name="monthly_fee_amount"]');const registration=modal.querySelector('[data-original-name="registration_fee_amount"]');const due=modal.querySelector('[data-original-name="monthly_fee_due_day"]');const monthlyLabel=monthly?.closest('label');const registrationLabel=registration?.closest('label');const dueLabel=due?.closest('label');const isMonthly=mode.value==='monthly';if(monthlyLabel)monthlyLabel.classList.toggle('hidden',!isMonthly);if(registrationLabel)registrationLabel.classList.toggle('hidden',isMonthly);if(dueLabel)dueLabel.classList.toggle('hidden',!isMonthly);if(monthly)monthly.disabled=!isMonthly;if(registration)registration.disabled=isMonthly;if(due)due.disabled=!isMonthly};
    document.addEventListener('change',event=>{if(event.target.matches('#class-edit-modal [data-original-name="fee_mode"]'))syncModalFeeFields()});
    const observer=new MutationObserver(syncModalFeeFields);const modal=document.getElementById('class-edit-modal');if(modal)observer.observe(modal,{childList:true,subtree:true});
});
</script>
</x-app-layout>
