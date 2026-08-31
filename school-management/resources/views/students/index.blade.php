<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ __('Students') }}</h2></x-slot>
@if($isTeacher)
    <div class="mx-auto max-w-7xl py-8 sm:px-6 lg:px-8">
        <div class="rounded-xl bg-white p-6 shadow">
            <div class="mb-5"><h3 class="text-lg font-semibold text-slate-900">{{ __('Students') }}</h3><p class="mt-1 text-sm text-slate-500">{{ __('Students in your assigned classes') }}</p></div>
            <div class="mb-6 flex flex-wrap gap-2">
                @foreach($classes as $class)
                    <a href="{{ route('students.index', ['class_id' => $class->id]) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ (int) $classId === $class->id ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">{{ $class->name }} ({{ $class->active_students_count }})</a>
                @endforeach
            </div>
            <table class="w-full text-left"><thead><tr class="border-b"><th class="p-2">{{ __('Student number') }}</th><th class="p-2">{{ __('Student') }}</th><th class="p-2">{{ __('Class') }}</th></tr></thead><tbody>
                @forelse($students as $student)
                    <tr class="border-b"><td class="p-2">{{ $student->student_number }}</td><td class="p-2">{{ $student->last_name }} {{ $student->first_name }}</td><td class="p-2">{{ optional($student->enrollments->firstWhere('status', 'active')?->schoolClass)->name }}</td></tr>
                @empty
                    <tr><td colspan="3" class="p-4 text-center text-slate-500">{{ __('No student.') }}</td></tr>
                @endforelse
            </tbody></table>
            <div class="mt-5">{{ $students->links() }}</div>
        </div>
    </div>
@else
    <div class="mx-auto max-w-7xl py-8 sm:px-6 lg:px-8"><div class="rounded-xl bg-white p-6 shadow">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4"><form id="student-search-form" method="GET" action="{{ route('students.index') }}"><input type="hidden" name="class_id" value="{{ $classId ?: '' }}"><input id="student-search" name="q" value="{{ request('q') }}" placeholder="{{ __('Name or student number') }}" class="rounded border-gray-300"><button class="ml-2 rounded bg-gray-800 px-3 py-2 text-white">{{ __('Search') }}</button></form><div class="flex items-center gap-3"><button id="total-students-download" type="button" class="rounded-full bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800" title="{{ __('Long press to download the student list') }}"><span class="block text-xs font-medium uppercase tracking-wide text-emerald-100">{{ __('Total students') }}</span><span class="block text-lg font-bold">{{ $totalStudents }}</span></button><button type="button" onclick="openEnrollmentModal()" class="rounded bg-emerald-700 px-4 py-2 text-white">{{ __('New enrollment') }}</button></div></div>
        <div id="student-class-filters" class="mt-2 mb-6 flex flex-wrap items-center gap-2 pb-2"><a href="{{ route('students.index', request('q') ? ['q' => request('q')] : []) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ !$classId ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">{{ __('All classes') }} ({{ $totalStudents }})</a>@foreach($classes as $class)@continue(str_contains(mb_strtolower($class->name), 'ecolage') || str_contains(mb_strtolower($class->name), 'écolage'))<a href="{{ route('students.index', array_filter(['class_id' => $class->id, 'q' => request('q')])) }}" class="rounded-full px-4 py-2 text-sm font-semibold {{ (int) $classId === $class->id ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">{{ $class->name }} ({{ $class->active_students_count }})</a>@endforeach</div>
        @php
            $query = trim((string) request('q'));
            $highlight = function (string $value) use ($query) {
                return $query === '' ? e($value) : preg_replace('/('.preg_quote($query, '/').')/iu', '<span class="rounded bg-gray-200 px-0.5">$1</span>', e($value));
            };
        @endphp
        <table class="w-full text-left"><thead><tr class="border-b"><th>{{ __('Student number') }}</th><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
            @forelse($students as $student)<tr class="border-b"><td>{!! $highlight($student->student_number) !!}</td><td>{!! $highlight($student->last_name.' '.$student->first_name) !!}</td><td>{{ optional($student->enrollments->firstWhere('status', 'active')?->schoolClass)->name }}</td><td class="space-x-3"><a class="text-emerald-700" href="{{ route('students.show', $student) }}">{{ __('View') }}</a>@can('update', $student)<a class="text-blue-700" href="{{ route('students.edit', $student) }}">{{ __('Edit') }}</a>@endcan @can('delete', $student)<form class="inline" method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('{{ __('Archive') }} ?')">@csrf @method('DELETE')<button class="text-red-700">{{ __('Archive') }}</button></form>@endcan</td></tr>
            @empty<tr><td colspan="4" class="py-4">{{ __('No student.') }}</td></tr>@endforelse
        </tbody></table><div class="mt-5">{{ $students->links() }}</div>
    </div></div>
<script>
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('#student-class-filters a').forEach(link=>{const label=link.textContent.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();if(label.includes('ecolage'))link.remove()})});
</script>
<style>
    #total-students-download { border-radius: 0.5rem; display: inline-flex; align-items: center; gap: 0.35rem; white-space: nowrap; }
    #total-students-download span { display: inline !important; font-size: 0.875rem !important; line-height: 1.25rem !important; }
    #total-students-download span:first-child::after { content: ' :'; }
</style>
<div id="student-export-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 p-4"><div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl"><h3 class="text-lg font-semibold text-gray-900">{{ __('Download student list') }}</h3><p class="mt-2 text-sm text-gray-600">{{ __('Download all enrolled students grouped by class?') }}</p><div class="mt-6 flex justify-center gap-3"><button type="button" onclick="closeStudentExportModal()" class="rounded-lg border px-4 py-2">{{ __('Cancel') }}</button><button type="button" onclick="confirmStudentExport()" class="rounded-lg bg-emerald-700 px-4 py-2 text-white">{{ __('Download') }}</button></div></div></div>
<script>
let studentExportTimer=null;function openStudentExportModal(){const modal=document.getElementById('student-export-modal');modal.classList.remove('hidden');modal.classList.add('flex')}function closeStudentExportModal(){const modal=document.getElementById('student-export-modal');modal.classList.add('hidden');modal.classList.remove('flex')}function confirmStudentExport(){window.location.href='{{ route('students.export-list') }}';closeStudentExportModal()}
document.addEventListener('DOMContentLoaded',()=>{const button=document.getElementById('total-students-download');if(!button)return;const start=event=>{event.preventDefault();studentExportTimer=setTimeout(openStudentExportModal,650)};const stop=()=>{clearTimeout(studentExportTimer);studentExportTimer=null};button.addEventListener('pointerdown',start);['pointerup','pointerleave','pointercancel'].forEach(name=>button.addEventListener(name,stop));button.addEventListener('contextmenu',event=>{event.preventDefault();openStudentExportModal()})});
</script>
<div id="enrollment-modal" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index:99999;background-color:rgba(0,0,0,.6)"><div class="relative z-[100000] flex flex-col overflow-hidden rounded-xl bg-white shadow-xl" style="width:min(90vw,672px);height:min(96vh,900px)"><div class="flex items-center justify-between border-b px-5 py-3"><h3 class="text-lg font-semibold">{{ __('New enrollment') }}</h3><button type="button" onclick="closeEnrollmentModal()" class="rounded-lg px-3 py-1 text-xl text-gray-500 hover:bg-gray-100" aria-label="{{ __('Close') }}">×</button></div><iframe id="enrollment-frame" class="min-h-0 flex-1 w-full overflow-auto bg-gray-50" scrolling="yes" src="" title="{{ __('New enrollment') }}"></iframe></div></div>
<script>
function openEnrollmentModal(){const modal=document.getElementById('enrollment-modal');document.getElementById('enrollment-frame').src='{{ route('students.create') }}?embedded=1';modal.classList.remove('hidden');modal.classList.add('flex')}
function closeEnrollmentModal(){const modal=document.getElementById('enrollment-modal');modal.classList.add('hidden');modal.classList.remove('flex');document.getElementById('enrollment-frame').src=''}
document.getElementById('enrollment-frame')?.addEventListener('load',function(){try{const path=new URL(this.contentWindow.location.href).pathname;if(path.endsWith('/students')||path.endsWith('/students/')){closeEnrollmentModal();window.location.reload()}}catch(error){}});
</script>
@if(session('enrollment_receipt'))
<div id="enrollment-receipt-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"><div class="flex h-[78vh] w-full max-w-xs flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"><div class="flex items-center justify-between border-b px-4 py-3"><h3 class="font-semibold">{{ __('Enrollment receipt') }}</h3><button type="button" onclick="closeEnrollmentReceipt()" class="rounded px-3 py-1 text-sm">{{ __('Close') }}</button></div><iframe class="min-h-0 flex-1" src="{{ session('enrollment_receipt.preview') }}"></iframe><div class="flex justify-end gap-3 border-t p-3"><button type="button" onclick="printEnrollmentReceipt()" class="rounded border px-3 py-2 text-sm">{{ __('Print') }}</button><a href="{{ session('enrollment_receipt.pdf') }}?download=1" class="rounded bg-emerald-700 px-3 py-2 text-sm text-white">{{ __('Download') }}</a></div></div></div><iframe id="enrollment-print-frame" class="hidden"></iframe><script>function closeEnrollmentReceipt(){document.getElementById('enrollment-receipt-modal').remove()}function printEnrollmentReceipt(){const f=document.getElementById('enrollment-print-frame');f.onload=()=>setTimeout(()=>f.contentWindow?.print(),300);f.src='{{ session('enrollment_receipt.pdf') }}'}</script>
@endif
<style>
    #student-scan-modal > div { max-width:360px; }
    #student-qr-reader { width:280px; max-width:100%; margin:0 auto; }
    #student-qr-reader video { max-height:220px; object-fit:cover; }
</style>
<style>#student-scan-error-modal{background-color:rgba(0,0,0,.70)!important}</style>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<div id="student-scan-modal" class="fixed inset-0 z-[100000] hidden items-center justify-center bg-black/65 p-4">
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-5 py-4"><h3 class="text-lg font-semibold">{{ app()->getLocale() === 'fr' ? 'Scanner un élève' : 'Scan a student' }}</h3><button type="button" onclick="closeStudentScanner()" class="rounded-lg px-3 py-1 text-xl text-slate-500 hover:bg-slate-100" aria-label="{{ __('Close') }}">×</button></div>
        <div class="p-5"><div id="student-qr-reader" class="overflow-hidden rounded-xl border border-slate-200"></div><p id="student-scan-status" class="mt-3 text-center text-sm text-slate-500">{{ app()->getLocale() === 'fr' ? 'Autorisez la caméra puis présentez le QR code.' : 'Allow camera access and present the QR code.' }}</p></div>
    </div>
</div>
<div id="student-scan-error-modal" class="fixed inset-0 z-[100001] hidden items-center justify-center bg-black/65 p-4"><div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl"><h3 class="text-lg font-semibold text-slate-900">{{ app()->getLocale() === 'fr' ? 'Résultat du scan' : 'Scan result' }}</h3><p id="student-scan-error-message" class="mt-3 text-sm text-slate-600"></p><div class="mt-6 flex justify-center gap-3"><button type="button" onclick="retryStudentScannerV3()" class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">{{ app()->getLocale() === 'fr' ? 'Réessayer' : 'Retry' }}</button><button type="button" onclick="cancelStudentScanErrorV3()" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300">{{ __('Cancel') }}</button></div></div></div>
<script>
let studentQrScanner = null, studentQrBusy = false;
async function openStudentScanner() {
    const modal = document.getElementById('student-scan-modal');
    const status = document.getElementById('student-scan-status');
    modal.classList.remove('hidden'); modal.classList.add('flex'); status.textContent = @json(app()->getLocale() === 'fr' ? 'Initialisation de la caméra…' : 'Starting camera…');
    if (typeof Html5Qrcode === 'undefined') { status.textContent = @json(app()->getLocale() === 'fr' ? 'Lecteur QR indisponible. Vérifiez la connexion internet.' : 'QR reader unavailable. Check the internet connection.'); return; }
    if (studentQrScanner) return;
    studentQrBusy = false;
    studentQrScanner = new Html5Qrcode('student-qr-reader');
    try {
        await studentQrScanner.start({facingMode:'environment'}, {fps:10, qrbox:{width:250, height:250}}, async (decodedText) => {
            if (studentQrBusy) return;
            studentQrBusy = true;
            status.textContent = @json(app()->getLocale() === 'fr' ? 'Élève trouvé, ouverture de la fiche…' : 'Student found, opening the record…');
            try { await studentQrScanner.stop(); } catch (e) {}
            studentQrScanner.clear(); studentQrScanner = null;
            const response = await fetch('{{ route('students.scan') }}?value=' + encodeURIComponent(decodedText), {headers:{Accept:'application/json'}});
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.url) { studentQrBusy = false; status.textContent = data.message || @json(__('No student.')); return; }
            window.location.href = data.url;
        }, () => {});
    } catch (error) { status.textContent = @json(app()->getLocale() === 'fr' ? 'Impossible d’accéder à la caméra.' : 'Unable to access the camera.'); studentQrScanner = null; }
}
async function closeStudentScanner() {
    if (studentQrScanner) { try { await studentQrScanner.stop(); } catch (e) {} try { studentQrScanner.clear(); } catch (e) {} studentQrScanner = null; }
    document.getElementById('student-scan-modal').classList.add('hidden'); document.getElementById('student-scan-modal').classList.remove('flex');
}
function ensureStudentScanButton(){
    const total = document.getElementById('total-students-download');
    const actions = total?.parentElement;
    if (!actions || actions.querySelector('[data-student-scan]')) return;
    const button = document.createElement('button'); button.type='button'; button.dataset.studentScan='1'; button.onclick=()=>{if(typeof window.openStudentScannerV4==='function'){window.openStudentScannerV4();return}const modal=document.getElementById('student-scan-error-modal');const message=document.getElementById('student-scan-error-message');if(message)message.textContent=@json(app()->getLocale() === 'fr' ? 'Le scanner est indisponible.' : 'The scanner is unavailable.');modal.classList.remove('hidden');modal.classList.add('flex');modal.style.display='flex'}; button.className='rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800'; button.textContent=@json(app()->getLocale() === 'fr' ? 'Scanner' : 'Scan');
    actions.insertBefore(button, actions.querySelector('button[onclick="openEnrollmentModal()"]') || null);
}
if(document.readyState === 'loading'){document.addEventListener('DOMContentLoaded',ensureStudentScanButton)}else{ensureStudentScanButton()}
</script>
<script>
let studentQrScannerV2=null,studentQrBusyV2=false;
function showStudentScanErrorV2(message){const camera=document.getElementById('student-scan-modal');camera.classList.add('hidden');camera.classList.remove('flex');const error=document.getElementById('student-scan-error-modal');document.getElementById('student-scan-error-message').textContent=message;error.classList.remove('hidden');error.classList.add('flex')}
function cancelStudentScanErrorV2(){const error=document.getElementById('student-scan-error-modal');error.classList.add('hidden');error.classList.remove('flex')}
async function stopStudentScannerV2(){if(studentQrScannerV2){try{await studentQrScannerV2.stop()}catch(e){}try{studentQrScannerV2.clear()}catch(e){}studentQrScannerV2=null}}
async function startStudentScannerV2(){const modal=document.getElementById('student-scan-modal'),status=document.getElementById('student-scan-status');const error=document.getElementById('student-scan-error-modal');error.classList.add('hidden');error.classList.remove('flex');modal.classList.remove('hidden');modal.classList.add('flex');status.textContent=@json(app()->getLocale() === 'fr' ? 'Initialisation de la caméra…' : 'Starting camera…');if(typeof Html5Qrcode==='undefined'){showStudentScanErrorV2(@json(app()->getLocale() === 'fr' ? 'Lecteur QR indisponible. Vérifiez la connexion internet.' : 'QR reader unavailable. Check the internet connection.'));return}studentQrBusyV2=false;studentQrScannerV2=new Html5Qrcode('student-qr-reader');try{await studentQrScannerV2.start({facingMode:'environment'},{fps:10,qrbox:{width:250,height:250}},async(value)=>{if(studentQrBusyV2)return;studentQrBusyV2=true;status.textContent=@json(app()->getLocale() === 'fr' ? 'Élève trouvé, ouverture de la fiche…' : 'Student found, opening the record…');await stopStudentScannerV2();const response=await fetch('{{ route('students.scan') }}?value='+encodeURIComponent(value),{headers:{Accept:'application/json'}});const data=await response.json().catch(()=>({}));if(!response.ok||!data.url){studentQrBusyV2=false;showStudentScanErrorV2(data.message||@json(__('No student.')));return}window.location.href=data.url},()=>{})}catch(error){await stopStudentScannerV2();showStudentScanErrorV2(@json(app()->getLocale() === 'fr' ? 'Impossible d’accéder à la caméra.' : 'Unable to access the camera.')))}}
async function openStudentScannerV2(){await startStudentScannerV2()}
async function retryStudentScannerV2(){cancelStudentScanErrorV2();await startStudentScannerV2()}
async function closeStudentScanner(){await stopStudentScannerV2();const modal=document.getElementById('student-scan-modal');modal.classList.add('hidden');modal.classList.remove('flex')}
</script>
<script>
let studentQrScannerV3=null,studentQrBusyV3=false;
function showStudentScanErrorV3(message){const camera=document.getElementById('student-scan-modal');camera.classList.add('hidden');camera.classList.remove('flex');const error=document.getElementById('student-scan-error-modal');document.getElementById('student-scan-error-message').textContent=message;error.classList.remove('hidden');error.classList.add('flex')}
function cancelStudentScanErrorV3(){const error=document.getElementById('student-scan-error-modal');error.classList.add('hidden');error.classList.remove('flex')}
async function stopStudentScannerV3(){if(studentQrScannerV3){try{await studentQrScannerV3.stop()}catch(e){}try{studentQrScannerV3.clear()}catch(e){}studentQrScannerV3=null}}
async function startStudentScannerV3(){const modal=document.getElementById('student-scan-modal'),status=document.getElementById('student-scan-status');const error=document.getElementById('student-scan-error-modal');error.classList.add('hidden');error.classList.remove('flex');modal.classList.remove('hidden');modal.classList.add('flex');status.textContent=@json(app()->getLocale() === 'fr' ? 'Initialisation de la caméra…' : 'Starting camera…');if(typeof Html5Qrcode==='undefined'){showStudentScanErrorV3(@json(app()->getLocale() === 'fr' ? 'Lecteur QR indisponible. Vérifiez la connexion internet.' : 'QR reader unavailable. Check the internet connection.'));return}studentQrBusyV3=false;studentQrScannerV3=new Html5Qrcode('student-qr-reader');try{await studentQrScannerV3.start({facingMode:'environment'},{fps:10,qrbox:{width:250,height:250}},async(value)=>{if(studentQrBusyV3)return;studentQrBusyV3=true;status.textContent=@json(app()->getLocale() === 'fr' ? 'Vérification de l’élève…' : 'Checking student…');await stopStudentScannerV3();const response=await fetch('{{ route('students.scan') }}?value='+encodeURIComponent(value),{headers:{Accept:'application/json'}});const data=await response.json().catch(()=>({}));if(!response.ok||!data.url){studentQrBusyV3=false;showStudentScanErrorV3(data.message||@json(__('No student.')));return}window.location.href=data.url},()=>{})}catch(error){await stopStudentScannerV3();showStudentScanErrorV3(@json(app()->getLocale() === 'fr' ? 'Impossible d’accéder à la caméra.' : 'Unable to access the camera.')))}}
window.openStudentScannerV3=async function(){await startStudentScannerV3()};
window.retryStudentScannerV3=async function(){cancelStudentScanErrorV3();await startStudentScannerV3()};
window.closeStudentScanner=async function(){if(studentQrScanner){try{await studentQrScanner.stop()}catch(e){}try{studentQrScanner.clear()}catch(e){}studentQrScanner=null}await stopStudentScannerV3();const modal=document.getElementById('student-scan-modal');modal.classList.add('hidden');modal.classList.remove('flex')};
</script>
<script>
// Scanner élève : même flux que le scanner des comptes.
let studentQrScannerV4=null,studentQrBusyV4=false;
const studentScanCameraModal=()=>document.getElementById('student-scan-modal');
const studentScanErrorModal=()=>document.getElementById('student-scan-error-modal');
async function stopStudentScannerV4(){if(!studentQrScannerV4)return;try{await studentQrScannerV4.stop()}catch(e){}try{studentQrScannerV4.clear()}catch(e){}studentQrScannerV4=null}
function hideStudentCameraV4(){const modal=studentScanCameraModal();modal.classList.add('hidden');modal.classList.remove('flex');modal.style.display=''}
function showStudentScanErrorV4(message){hideStudentCameraV4();const modal=studentScanErrorModal();document.getElementById('student-scan-error-message').textContent=message;modal.classList.remove('hidden');modal.classList.add('flex');modal.style.display='flex'}
function cancelStudentScanErrorV4(){const modal=studentScanErrorModal();modal.classList.add('hidden');modal.classList.remove('flex');modal.style.display=''}
async function startStudentScannerV4(){const camera=studentScanCameraModal(),error=studentScanErrorModal(),status=document.getElementById('student-scan-status');error.classList.add('hidden');error.classList.remove('flex');error.style.display='';camera.classList.remove('hidden');camera.classList.add('flex');camera.style.display='flex';status.textContent=@json(app()->getLocale() === 'fr' ? 'Initialisation de la caméra…' : 'Starting camera…');if(typeof Html5Qrcode==='undefined'){showStudentScanErrorV4(@json(app()->getLocale() === 'fr' ? 'Lecteur QR indisponible. Vérifiez la connexion internet.' : 'QR reader unavailable. Check the internet connection.'));return}studentQrBusyV4=false;studentQrScannerV4=new Html5Qrcode('student-qr-reader');try{await studentQrScannerV4.start({facingMode:'environment'},{fps:10,qrbox:{width:250,height:250}},async(value)=>{if(studentQrBusyV4)return;studentQrBusyV4=true;status.textContent=@json(app()->getLocale() === 'fr' ? 'Vérification de l’élève…' : 'Checking student…');await stopStudentScannerV4();const response=await fetch('{{ route('students.scan') }}?value='+encodeURIComponent(value),{headers:{Accept:'application/json'}});const data=await response.json().catch(()=>({}));if(!response.ok||!data.url){studentQrBusyV4=false;showStudentScanErrorV4(data.message||@json(__('No student.')));return}window.location.href=data.url},()=>{})}catch(error){await stopStudentScannerV4();showStudentScanErrorV4(@json(app()->getLocale() === 'fr' ? 'Impossible d’accéder à la caméra.' : 'Unable to access the camera.'))}}
window.openStudentScannerV4=async()=>{await startStudentScannerV4()};
window.retryStudentScannerV4=async()=>{cancelStudentScanErrorV4();await startStudentScannerV4()};
window.cancelStudentScanErrorV4=cancelStudentScanErrorV4;
window.closeStudentScanner=async()=>{await stopStudentScannerV4();hideStudentCameraV4()};
const studentScanErrorActions=document.querySelectorAll('#student-scan-error-modal button');
if(studentScanErrorActions[0])studentScanErrorActions[0].onclick=window.retryStudentScannerV4;
if(studentScanErrorActions[1])studentScanErrorActions[1].onclick=window.cancelStudentScanErrorV4;
</script>
@endif
</x-app-layout>
