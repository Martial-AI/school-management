<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl">{{ __('Student enrollment') }}</h2></x-slot>
@if(request()->boolean('embedded'))<style>nav{display:none!important}html,body{overflow:auto!important;background:#f8fafc!important}main{padding-top:0!important}.mx-auto.max-w-4xl{padding-top:1rem!important;padding-bottom:1rem!important}.mx-auto.max-w-4xl form{padding:1rem!important;display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem!important}.mx-auto.max-w-4xl form.space-y-5>*+*{margin-top:0!important}.mx-auto.max-w-4xl form>h3,.mx-auto.max-w-4xl form>section,.mx-auto.max-w-4xl form>button{grid-column:1/-1}.mx-auto.max-w-4xl form>div.grid{display:contents!important}.mx-auto.max-w-4xl .gap-4{gap:.65rem!important}.mx-auto.max-w-4xl h3{font-size:1rem!important}@media(max-width:640px){.mx-auto.max-w-4xl form{grid-template-columns:1fr!important}}</style>@endif
<style>
    form[action*="students"] input:not([type="checkbox"]):not([type="file"]),
    form[action*="students"] select,
    form[action*="students"] textarea { box-sizing:border-box; min-height:42px; height:42px; }
    form[action*="students"] textarea { resize:none; }
</style>
<div class="mx-auto max-w-4xl py-8 sm:px-6 lg:px-8">
<form method="POST" enctype="multipart/form-data" action="{{ route('students.store') }}" class="space-y-5 rounded-xl bg-white p-6 shadow">@csrf
    <h3 class="font-semibold">{{ __('Student information') }}</h3>
    <div class="grid gap-4 md:grid-cols-2"><input name="first_name" placeholder="{{ __('First name') }}" required class="rounded border-gray-300"><input name="last_name" placeholder="{{ __('Last name') }}" required class="rounded border-gray-300"><select name="gender" class="rounded border-gray-300"><option value="">{{ __('Gender') }}</option><option value="male">{{ __('Male') }}</option><option value="female">{{ __('Female') }}</option></select><label class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">{{ __('Birth date') }}</span><input type="text" name="birth_date" aria-label="{{ __('Birth date') }}" onfocus="this.previousElementSibling.style.display='none';this.type='date'" onblur="if(!this.value){this.type='text';this.previousElementSibling.style.display='block'}" class="w-full rounded border-gray-300"></label><input name="birth_place" placeholder="{{ __('Birth place') }}" class="rounded border-gray-300"><select id="school-class" name="school_class_id" required class="rounded border-gray-300"><option value="">{{ __('Class') }}</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }} — {{ $class->academicYear->name }}</option>@endforeach</select><div class="flex flex-col items-start gap-2"><input type="file" name="photo" accept="image/*" title="{{ __('Photo') }}"></div></div>
    <label><textarea name="address" placeholder="{{ __('Address') }}" class="mt-1 block w-full rounded border-gray-300"></textarea></label>

    <section id="enrollment-payment" class="hidden rounded-xl border border-emerald-100 bg-emerald-50 p-4">
        <h3 class="font-semibold text-emerald-900">{{ __('Payment during enrollment') }}</h3>
        <div id="registration-payment" class="mt-3 hidden"><p id="registration-total" class="text-sm font-medium"></p><label class="mt-3 flex items-center gap-2"><input id="registration-full" type="checkbox" name="registration_payment" value="full"> {{ __('Pay the registration fee in full') }}</label><div class="mt-3 flex flex-wrap items-end gap-3"><label class="text-sm">{{ __('Or remaining amount') }}<input id="registration-remaining" name="registration_remaining_amount" type="number" min="0" step="0.01" class="mt-1 block rounded border-gray-300"></label><label class="text-sm">{{ __('Remaining balance deadline (optional)') }}<input id="registration-due-date" name="registration_due_date" type="date" class="mt-1 block rounded border-gray-300"></label></div><input id="registration-payment-mode" type="hidden" name="registration_payment" value="none"></div>
        <div id="monthly-payment" class="mt-3 hidden"><p id="monthly-total" class="text-sm font-medium"></p><p class="mt-3 text-sm font-medium">{{ __('School fee months to pay now') }}</p><div id="monthly-months" class="mt-2 flex flex-wrap gap-2"></div></div>
    </section>

    <h3 class="font-semibold">{{ __('Parent or guardian') }}</h3>
    <div class="grid gap-4 md:grid-cols-2"><input name="guardian[first_name]" required placeholder="{{ __('First name') }}" class="rounded border-gray-300"><input name="guardian[last_name]" required placeholder="{{ __('Last name') }}" class="rounded border-gray-300"><input name="guardian[phone]" required placeholder="{{ __('Phone') }}" class="rounded border-gray-300"><input name="guardian[email]" placeholder="{{ __('Email') }}" class="rounded border-gray-300"></div>
    <button class="rounded bg-emerald-700 px-5 py-2 text-white">{{ __('Save student') }}</button>
</form></div>
<script>
const classes=@json($classPaymentOptions), uiLocale=@json(app()->getLocale()==='fr'?'fr-FR':'en-US'), registrationFeeLabel=@json(__('Registration fee')), monthlyFeeDefinedLabel=@json(__('Monthly school fee defined for this class'));
const selected=document.getElementById('school-class'),panel=document.getElementById('enrollment-payment'),right=document.getElementById('registration-payment'),monthly=document.getElementById('monthly-payment');
function refreshPayment(){const item=classes.find(c=>String(c.id)===selected.value);panel.classList.toggle('hidden',!item);right.classList.add('hidden');monthly.classList.add('hidden');if(!item)return;if(item.mode==='registration_only'){right.classList.remove('hidden');document.getElementById('registration-total').textContent=registrationFeeLabel+' : '+Number(item.registration_amount).toLocaleString(uiLocale)+' Ar';return}monthly.classList.remove('hidden');document.getElementById('monthly-total').textContent=monthlyFeeDefinedLabel+' : '+Number(item.monthly_amount).toLocaleString(uiLocale)+' Ar';const area=document.getElementById('monthly-months');area.innerHTML='';if(!item.start)return;let date=new Date(item.start+'T00:00:00');for(let i=0;i<item.duration;i++){const value=`${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}`;const label=date.toLocaleDateString(uiLocale,{month:'short',year:'numeric'});area.insertAdjacentHTML('beforeend',`<label class="rounded-lg border border-emerald-200 bg-white px-2 py-1 text-xs"><input type="checkbox" name="monthly_fee_months[]" value="${value}"> ${label}</label>`);date.setMonth(date.getMonth()+1)}}
selected.addEventListener('change',refreshPayment);
selected.addEventListener('change',()=>{document.documentElement.style.overflow='auto';document.body.style.overflow='auto';if(window.frameElement)window.frameElement.setAttribute('scrolling','yes')});
document.getElementById('registration-full').addEventListener('change',e=>{const remaining=document.getElementById('registration-remaining'),due=document.getElementById('registration-due-date');document.getElementById('registration-payment-mode').value=e.target.checked?'full':(remaining.value?'remaining':'none');remaining.disabled=e.target.checked;due.disabled=e.target.checked;if(e.target.checked){remaining.value='';due.value=''}});
document.getElementById('registration-remaining').addEventListener('input',e=>{const full=document.getElementById('registration-full');if(e.target.value){full.checked=false;document.getElementById('registration-payment-mode').value='remaining'}else document.getElementById('registration-payment-mode').value='none'});
</script>
<div id="student-photo-choice-modal" class="fixed inset-0 z-[160] hidden items-center justify-center bg-black/55 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"><div class="flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-900">{{ __('Add a photo') }}</h3><button type="button" onclick="closeStudentPhotoChoice()" class="rounded-lg px-3 py-1 text-slate-500 hover:bg-slate-100">{{ __('Close') }}</button></div><p class="mt-2 text-sm text-slate-600">{{ __('Choose the source of the student photo.') }}</p><div class="mt-5 grid gap-3"><button id="student-photo-from-camera" type="button" class="rounded-xl bg-emerald-700 px-4 py-3 font-medium text-white">{{ __('Camera') }}</button><button id="student-photo-from-file" type="button" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700">{{ __('Choose a file') }}</button></div></div>
</div>
<div id="student-camera-modal" class="fixed inset-0 z-[170] hidden items-start justify-center overflow-y-auto bg-black/65 px-4 py-6 sm:items-center">
    <div class="my-auto overflow-hidden rounded-2xl bg-white shadow-2xl" style="width:460px;max-width:calc(100vw - 32px);flex:none">
        <div style="position:relative;height:280px;background:#020617">
            <button type="button" onclick="closeStudentCameraModal()" style="position:absolute;right:10px;top:10px;z-index:100;display:block;border:0;border-radius:9999px;background:rgba(0,0,0,.68);padding:6px 11px;font-size:12px;line-height:1.25;color:#fff;cursor:pointer">{{ __('Close') }}</button>
            <video id="student-modal-camera" class="object-cover" style="position:absolute;inset:0;display:block;width:100%;height:100%" autoplay playsinline muted></video>
            <img id="student-captured-preview" class="object-cover" style="position:absolute;inset:0;display:none;width:100%;height:100%" alt="{{ __('Captured photo') }}">
        </div>
        <div id="student-captured-actions" style="display:block!important;visibility:visible!important;min-height:140px;background:#fff;padding:18px 16px 24px">
            <div id="student-camera-actions" style="display:flex;justify-content:center"><button id="student-modal-capture" type="button" aria-label="{{ __('Capture') }}" title="{{ __('Capture') }}" style="display:block;width:64px;height:64px;border:5px solid #d1fae5;border-radius:50%;background:#047857;box-shadow:0 3px 12px rgba(0,0,0,.25);cursor:pointer"></button></div>
            <div id="student-photo-review-actions" style="display:none;justify-content:center;gap:14px"><button type="button" onclick="retryStudentPhoto()" style="display:inline-block;border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:10px 18px;font-size:14px;color:#334155">{{ __('Retry') }}</button><button id="student-photo-save" type="button" onclick="saveStudentPhoto()" style="display:inline-block;border:0;border-radius:8px;background:#047857;padding:10px 18px;font-size:14px;color:#fff;cursor:pointer">{{ __('Save') }}</button></div>
        </div>
    </div>
</div>
<canvas id="student-modal-canvas" class="hidden"></canvas>
<script>
(() => {
    const file = document.querySelector('input[name="photo"]');
    const controls = file.parentElement; file.classList.add('hidden');
    const add = document.createElement('button'); add.type='button'; add.textContent=@json(__('Add a photo')); add.className='rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white'; controls.append(add);
    const photoName = document.createElement('span');
    photoName.style.cssText = 'display:none;align-self:flex-start;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;border-radius:8px;background:#ecfdf5;padding:7px 10px;font-size:13px;font-weight:600;color:#047857';
    controls.append(photoName);
    const showPhotoName = name => { photoName.textContent = '✓ ' + name; photoName.title = name; photoName.style.display = 'block'; };
    const choice=document.getElementById('student-photo-choice-modal'), modal=document.getElementById('student-camera-modal'), video=document.getElementById('student-modal-camera'), preview=document.getElementById('student-captured-preview'), canvas=document.getElementById('student-modal-canvas'), capture=document.getElementById('student-modal-capture'), cameraActions=document.getElementById('student-camera-actions'), reviewActions=document.getElementById('student-photo-review-actions');
    let stream=null, photoBlob=null;
    window.closeStudentPhotoChoice=()=>{choice.classList.add('hidden');choice.classList.remove('flex')};
    const stop=()=>{stream?.getTracks().forEach(t=>t.stop());stream=null;video.srcObject=null};
    window.closeStudentCameraModal=()=>{stop();modal.classList.add('hidden');modal.classList.remove('flex');photoBlob=null};
    const showCamera=async()=>{try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:960},height:{ideal:720}},audio:false});video.srcObject=stream;preview.style.display='none';video.style.display='block';cameraActions.style.display='flex';reviewActions.style.display='none';modal.classList.remove('hidden');modal.classList.add('flex')}catch(e){alert(@json(__('Allow camera access to take a photo.')))}};
    add.onclick=()=>{choice.classList.remove('hidden');choice.classList.add('flex')};
    document.getElementById('student-photo-from-file').onclick=()=>{closeStudentPhotoChoice();file.click()};
    file.addEventListener('change',()=>{if(file.files?.[0])showPhotoName(file.files[0].name)});
    document.getElementById('student-photo-from-camera').onclick=()=>{closeStudentPhotoChoice();showCamera()};
    capture.onclick=()=>{if(!video.videoWidth)return;canvas.width=video.videoWidth;canvas.height=video.videoHeight;canvas.getContext('2d').drawImage(video,0,0);canvas.toBlob(blob=>{photoBlob=blob;preview.src=URL.createObjectURL(blob);video.style.display='none';preview.style.display='block';cameraActions.style.display='none';reviewActions.style.display='flex';stop()},'image/jpeg',.9)};
    window.retryStudentPhoto=()=>showCamera();
    window.saveStudentPhoto=()=>{if(!photoBlob)return;const picture=new File([photoBlob],`photo-eleve-${Date.now()}.jpg`,{type:'image/jpeg'});const data=new DataTransfer();data.items.add(picture);file.files=data.files;showPhotoName(picture.name);closeStudentCameraModal();};
})();
</script>
<script>
(() => {
    const grid = document.querySelector('input[name="birth_place"]')?.closest('.grid');
    const address = document.querySelector('textarea[name="address"]')?.parentElement;
    const classSelect = document.getElementById('school-class');
    const photoControl = document.querySelector('input[name="photo"]')?.parentElement;
    if (!grid || !address || !classSelect || !photoControl) return;
    grid.insertBefore(address, classSelect);
    grid.removeChild(classSelect);
    grid.removeChild(photoControl);
    grid.parentElement.insertBefore(classSelect, grid.nextSibling);
    grid.parentElement.insertBefore(photoControl, classSelect.nextSibling);
    classSelect.classList.add('w-full');
})();
</script>
</x-app-layout>
