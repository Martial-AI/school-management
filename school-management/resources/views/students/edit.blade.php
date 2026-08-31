@php($primaryGuardian = $student->guardians->first())
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ __('Edit') }} {{ __('Student') }}</h2></x-slot>
    <div class="mx-auto max-w-4xl py-8 sm:px-6 lg:px-8">
        <form method="POST" enctype="multipart/form-data" action="{{ route('students.update', $student) }}" class="space-y-4 rounded-xl bg-white p-6 shadow">
            @csrf @method('PUT')
            <div class="grid gap-4 md:grid-cols-2">
                <input name="first_name" placeholder="{{ __('First name') }}" value="{{ old('first_name', $student->first_name) }}" required class="rounded border-gray-300">
                <input name="last_name" placeholder="{{ __('Last name') }}" value="{{ old('last_name', $student->last_name) }}" required class="rounded border-gray-300">
                <select name="gender" class="rounded border-gray-300"><option value="">{{ __('Gender') }}</option><option value="male" @selected($student->gender === 'male')>{{ __('Male') }}</option><option value="female" @selected($student->gender === 'female')>{{ __('Female') }}</option></select>
                <input type="date" name="birth_date" value="{{ old('birth_date', optional($student->birth_date)->format('Y-m-d')) }}" class="rounded border-gray-300">
                <input name="birth_place" placeholder="{{ __('Birth place') }}" value="{{ old('birth_place', $student->birth_place) }}" class="rounded border-gray-300">
                <input name="phone" placeholder="{{ __('Phone') }}" value="{{ old('phone', $student->phone) }}" class="rounded border-gray-300">
            </div>
            <textarea name="address" placeholder="{{ __('Address') }}" class="block min-h-[42px] w-full resize-none rounded border-gray-300">{{ old('address', $student->address) }}</textarea>
            <h3 class="pt-2 font-semibold">{{ __('Parent or guardian') }}</h3>
            <div class="grid gap-4 md:grid-cols-2">
                <input name="guardian[first_name]" placeholder="{{ __('First name') }}" value="{{ old('guardian.first_name', $primaryGuardian?->first_name) }}" required class="rounded border-gray-300">
                <input name="guardian[last_name]" placeholder="{{ __('Last name') }}" value="{{ old('guardian.last_name', $primaryGuardian?->last_name) }}" required class="rounded border-gray-300">
                <input name="guardian[phone]" placeholder="{{ __('Phone') }}" value="{{ old('guardian.phone', $primaryGuardian?->phone) }}" required class="rounded border-gray-300">
                <input name="guardian[email]" placeholder="{{ __('Email') }}" value="{{ old('guardian.email', $primaryGuardian?->email) }}" class="rounded border-gray-300">
            </div>
            <div class="flex flex-col items-start gap-2">
                <input id="student-edit-photo" type="file" name="photo" accept="image/*" class="hidden">
                <button id="student-edit-add-photo" type="button" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">{{ __('Add a photo') }}</button>
                <span id="student-edit-photo-name" class="hidden max-w-xs truncate rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700"></span>
            </div>
            <div class="flex gap-3"><button class="rounded bg-emerald-700 px-4 py-2 text-white">{{ __('Save') }}</button><a href="{{ route('students.show',$student) }}" class="rounded border px-4 py-2">{{ __('Cancel') }}</a></div>
        </form>
    </div>
    <div id="student-edit-photo-choice" class="fixed inset-0 z-[160] hidden items-center justify-center bg-black/60 p-4"><div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"><div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ __('Add a photo') }}</h3><button type="button" onclick="closeEditPhotoChoice()" class="rounded-lg px-3 py-1 text-slate-500">{{ __('Close') }}</button></div><p class="mt-2 text-sm text-slate-600">{{ __('Choose the source of the student photo.') }}</p><div class="mt-5 grid gap-3"><button id="student-edit-photo-camera" type="button" class="rounded-xl bg-emerald-700 px-4 py-3 font-medium text-white">{{ __('Camera') }}</button><button id="student-edit-photo-file" type="button" class="rounded-xl border border-slate-300 px-4 py-3 font-medium text-slate-700">{{ __('Choose a file') }}</button></div></div></div>
    <div id="student-edit-camera" class="fixed inset-0 z-[170] hidden items-start justify-center overflow-y-auto bg-black/65 px-4 py-6 sm:items-center"><div class="my-auto w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl"><div class="relative h-56 bg-slate-950"><button type="button" onclick="closeEditCamera()" class="absolute right-3 top-3 z-10 rounded-full bg-black/70 px-3 py-1.5 text-xs text-white">{{ __('Close') }}</button><video id="student-edit-video" class="h-full w-full object-cover" autoplay playsinline muted></video><img id="student-edit-preview" class="hidden h-full w-full object-cover" alt="{{ __('Captured photo') }}"></div><div class="flex min-h-[110px] items-center justify-center gap-4 bg-white p-4"><button id="student-edit-capture" type="button" aria-label="{{ __('Capture') }}" class="h-16 w-16 rounded-full border-4 border-emerald-100 bg-emerald-700 shadow-lg"></button><div id="student-edit-review" class="hidden gap-3"><button type="button" onclick="retryEditPhoto()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">{{ __('Retry') }}</button><button type="button" onclick="saveEditPhoto()" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm text-white">{{ __('Save') }}</button></div></div></div></div>
    <canvas id="student-edit-canvas" class="hidden"></canvas>
    <script>
        const editFile=document.getElementById('student-edit-photo'), editName=document.getElementById('student-edit-photo-name'), choice=document.getElementById('student-edit-photo-choice'), camera=document.getElementById('student-edit-camera'), video=document.getElementById('student-edit-video'), preview=document.getElementById('student-edit-preview'), canvas=document.getElementById('student-edit-canvas'), capture=document.getElementById('student-edit-capture'), review=document.getElementById('student-edit-review'); let editStream=null, editBlob=null;
        const showEditName=name=>{editName.textContent='✓ '+name;editName.title=name;editName.classList.remove('hidden')};
        editFile.addEventListener('change',()=>{if(editFile.files?.[0])showEditName(editFile.files[0].name)});
        document.getElementById('student-edit-add-photo').onclick=()=>{choice.classList.remove('hidden');choice.classList.add('flex')};
        window.closeEditPhotoChoice=()=>{choice.classList.add('hidden');choice.classList.remove('flex')};
        const stopEdit=()=>{editStream?.getTracks().forEach(t=>t.stop());editStream=null;video.srcObject=null};
        window.closeEditCamera=()=>{stopEdit();camera.classList.add('hidden');camera.classList.remove('flex');editBlob=null};
        const startEdit=async()=>{closeEditPhotoChoice();try{editStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:960},height:{ideal:720}},audio:false});video.srcObject=editStream;video.classList.remove('hidden');preview.classList.add('hidden');capture.classList.remove('hidden');review.classList.add('hidden');review.classList.remove('flex');camera.classList.remove('hidden');camera.classList.add('flex')}catch(e){alert(@json(__('Allow camera access to take a photo.')))}};
        document.getElementById('student-edit-photo-camera').onclick=startEdit;
        document.getElementById('student-edit-photo-file').onclick=()=>{closeEditPhotoChoice();editFile.click()};
        capture.onclick=()=>{if(!video.videoWidth)return;canvas.width=video.videoWidth;canvas.height=video.videoHeight;canvas.getContext('2d').drawImage(video,0,0);canvas.toBlob(b=>{editBlob=b;preview.src=URL.createObjectURL(b);video.classList.add('hidden');preview.classList.remove('hidden');capture.classList.add('hidden');review.classList.remove('hidden');review.classList.add('flex');stopEdit()},'image/jpeg',.9)};
        window.retryEditPhoto=()=>{closeEditCamera();startEdit()};
        window.saveEditPhoto=()=>{if(!editBlob)return;const f=new File([editBlob],`photo-eleve-${Date.now()}.jpg`,{type:'image/jpeg'}),d=new DataTransfer();d.items.add(f);editFile.files=d.files;showEditName(f.name);closeEditCamera()};
    </script>
</x-app-layout>
