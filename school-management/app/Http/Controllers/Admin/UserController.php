<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmployeePayment;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Services\SensitiveActivityNotifier;
use App\Services\ExpenseService;
use App\Services\TrashService;
use App\Services\ProfessionalIdentifierService;
use App\Services\ProfessionalCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->can('roles.manage'), 403);
    }

    public function index(): View
    {
        $this->authorizeAdmin();
        return view('admin.users.index', ['users' => User::with('roles')->latest()->paginate(15), 'canManageAccounts' => true, 'canResetPasswords' => true]);
    }

    public function show(User $user): View
    {
        $this->authorizeAdmin();
        $user->load('roles', 'permissions', 'teacherAssignments.schoolClass', 'teacherAssignments.subject');
        $salaryMonths = collect();
        if ((float) $user->monthly_salary_amount > 0) {
            $start = $this->salaryPeriodStart($user);
            $end = $this->salaryPeriodEnd($user);
            $payments = EmployeePayment::where('user_id', $user->id)->get()->keyBy(fn ($payment) => $payment->due_date->format('Y-m'));
            for ($date=$start->copy(); $date->lessThanOrEqualTo($end); $date->addMonth()) {
                $due = $date->copy()->day(min((int)($user->salary_payment_day ?: 1), $date->daysInMonth));
                $payment = $payments->get($date->format('Y-m'));
                $salaryMonths->push([
                    'date' => $date->copy(), 'due' => $due, 'payment' => $payment,
                    'receipt_preview_url' => $payment?->paid_at ? route('admin.users.salary.receipt-preview', [$user, $payment]) : null,
                    'receipt_download_url' => $payment?->paid_at ? route('admin.users.salary.receipt', [$user, $payment, 'download' => 1]) : null,
                ]);
            }
            $this->notifySalaryDue($user, $salaryMonths);
        }
        $salaryReceiptLinks = $salaryMonths
            ->filter(fn ($item) => $item['payment']?->paid_at)
            ->mapWithKeys(fn ($item) => [$item['date']->format('Y-m') => [
                'preview' => $item['receipt_preview_url'],
                'download' => $item['receipt_download_url'],
            ]]);
        return view('admin.users.show', compact('user','salaryMonths','salaryReceiptLinks'));
    }

    public function create(): View
    {
        $this->authorizeAdmin();
        return view('admin.users.create', ['roles' => ['Prof', 'Secrétaire', 'Trésorier'], 'permissions' => Permission::where('name', '!=', 'accounts.reset_password')->orderBy('name')->get(), 'classes' => SchoolClass::orderBy('name')->get(), 'subjects' => Subject::where('is_active', true)->orderBy('name')->get()]);
    }

    public function scanQr(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorizeAdmin();
        $value = trim(explode('|', $request->string('value')->toString(), 2)[0]);
        $user = User::where('professional_number', $value)->first();
        if (! $user) {
            if ($request->expectsJson()) return response()->json(['message' => __('No account found.')], 404);
            return back()->with('error', __('No account found.'));
        }
        $url = route('admin.users.show', $user);
        return $request->expectsJson() ? response()->json(['url' => $url]) : to_route('admin.users.show', $user);
    }

    public function store(Request $request, ProfessionalIdentifierService $identifiers): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $this->accountData($request, null, false);
        if ($request->hasFile('photo')) $data['photo_path'] = $request->file('photo')->store('users/photos', 'local');
        unset($data['photo']);
        $role = $data['role']; unset($data['role']);
        $data['professional_number'] = $identifiers->next();
        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);
        $user->syncRoles([$role]);
        $user->syncPermissions($request->input('permissions', []));
        $this->syncTeacherAssignments($user, $role, $request);
        $this->record($user, 'a créé le compte '.$user->name);
        return to_route('admin.users.index')->with('success', __('Account created successfully.'));
    }

    public function edit(User $user): View
    {
        $this->authorizeAdmin();
        $user->load('teacherAssignments');
        return view('admin.users.edit', ['user' => $user, 'roles' => ['Admin', 'Prof', 'Secrétaire', 'Trésorier'], 'permissions' => Permission::where('name', '!=', 'accounts.reset_password')->orderBy('name')->get(), 'assignedPermissions' => $user->getDirectPermissions()->pluck('name')->all(), 'classes' => SchoolClass::orderBy('name')->get(), 'subjects' => Subject::where('is_active', true)->orderBy('name')->get(), 'assignedClassIds' => $user->teacherAssignments->pluck('school_class_id')->all()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();
        $data = $this->accountData($request, $user, true);
        if ($request->hasFile('photo')) $data['photo_path'] = $request->file('photo')->store('users/photos', 'local');
        unset($data['photo']);
        $role = $data['role']; unset($data['role']);
        if (blank($data['password'])) { unset($data['password']); } else { $data['password'] = Hash::make($data['password']); }
        $user->update($data);
        $this->invalidateUserSessions($user);
        $user->syncRoles([$role]);
        $user->syncPermissions($request->input('permissions', []));
        $this->syncTeacherAssignments($user, $role, $request);
        $this->record($user, 'a modifié le compte '.$user->name);
        return to_route('admin.users.index')->with('success', __('Account updated.'));
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin(); $this->confirmAdminPassword($request);
        abort_if($user->is(auth()->user()), 422, __('You cannot deactivate your own account.'));
        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) $this->invalidateUserSessions($user);
        $this->record($user, $user->is_active ? 'a réactivé le compte '.$user->name : 'a suspendu le compte '.$user->name);
        SensitiveActivityNotifier::send('Compte modifié', ($user->is_active ? 'Compte réactivé : ' : 'Compte suspendu : ').$user->name);
        return back()->with('success', $user->is_active ? __('Account reactivated.') : __('Account suspended.'));
    }

    public function endContract(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->confirmAdminPassword($request);
        abort_if($user->is(auth()->user()), 422, __('You cannot end your own contract.'));
        $user->update(['contract_end_date' => now()->toDateString()]);
        $this->invalidateUserSessions($user);
        $this->record($user, 'a mis fin au contrat de '.$user->name);
        return back()->with('success', __('Contract ended successfully.'));
    }

    public function photo(User $user): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizeAdmin();
        abort_unless($user->photo_path && Storage::disk('local')->exists($user->photo_path), 404);
        return Storage::disk('local')->response($user->photo_path);
    }

    public function card(User $user, ProfessionalCardService $cards): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        return $cards->pdf($user)->download('carte-pro-'.$user->professional_number.'.pdf');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin(); $this->confirmAdminPassword($request);
        abort_if($user->is(auth()->user()), 422, __('Use your profile to change your password.'));
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $user->update(['password' => Hash::make($data['password'])]);
        $this->invalidateUserSessions($user);
        $this->record($user, 'a réinitialisé le mot de passe du compte '.$user->name);
        SensitiveActivityNotifier::send('Mot de passe réinitialisé', 'Le mot de passe du compte '.$user->name.' a été réinitialisé.');
        return back()->with('success', __('Account password reset.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin(); $this->confirmAdminPassword($request);
        abort_if($user->is(auth()->user()), 422, __('You cannot delete your own account.'));
        TrashService::store('user', $user, 'Compte : '.$user->name, ['roles' => $user->getRoleNames()->all(), 'permissions' => $user->getDirectPermissions()->pluck('name')->all()]);
        $this->record($user, 'a supprimé le compte '.$user->name);
        SensitiveActivityNotifier::send('Compte supprimé', 'Le compte '.$user->name.' a été supprimé définitivement.');
        $user->delete();
        return back()->with('success', __('Account deleted.'));
    }

    private function accountData(Request $request, ?User $user, bool $updating): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'nickname' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', $user ? 'unique:users,email,'.$user->id : 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'], 'cin' => ['nullable', 'string', 'max:50', $user ? 'unique:users,cin,'.$user->id : 'unique:users,cin'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'teaching_subjects' => ['nullable', 'string', 'max:500'],
            'teacher_class_ids' => ['nullable', 'array'], 'teacher_class_ids.*' => ['integer', 'exists:school_classes,id'],
            'birth_date' => ['nullable', 'date'], 'birth_place' => ['nullable', 'string', 'max:150'], 'address' => ['nullable', 'string', 'max:1000'], 'emergency_contact' => ['nullable', 'string', 'max:255'],
            'contract_type' => ['nullable', 'in:CDI,CDD,Stage,Prestataire'], 'contract_start_date' => ['nullable', 'date'], 'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'leave_start_date' => ['nullable', 'date'], 'leave_end_date' => ['nullable', 'date', 'after_or_equal:leave_start_date'],
            'monthly_salary_amount' => ['nullable','numeric','min:0'], 'salary_payment_day' => ['nullable','integer','min:1','max:31'],
            'password' => [$updating ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', $updating ? 'in:Admin,Prof,Secrétaire,Trésorier' : 'in:Prof,Secrétaire,Trésorier'],
            'permissions' => ['nullable', 'array'], 'permissions.*' => ['string', 'exists:permissions,name'],
        ]);
        $data['name'] = trim($data['first_name'].' '.$data['last_name']);
        $data['nickname'] = $data['role'] === 'Admin' ? 'Admin' : $data['role'].' '.$data['first_name'];
        if ($data['contract_type'] === 'CDI') $data['contract_end_date'] = null;
        unset($data['teacher_class_ids']);

        return $data;
    }

    private function syncTeacherAssignments(User $user, string $role, Request $request): void
    {
        $user->teacherAssignments()->delete();
        if ($role !== 'Prof') return;

        $classIds = collect($request->input('teacher_class_ids', []))->map(fn ($id) => (int) $id)->filter()->unique();
        $subjectName = trim((string) $request->input('teaching_subjects', ''));
        if ($classIds->isEmpty() || $subjectName === '') return;

        $subject = Subject::firstOrCreate(
            ['name' => $subjectName],
            ['code' => 'CUSTOM-'.strtoupper(substr(md5($subjectName), 0, 10)), 'coefficient' => 1, 'is_active' => true],
        );
        foreach ($classIds as $classId) {
            TeacherAssignment::create(['teacher_id' => $user->id, 'school_class_id' => $classId, 'subject_id' => $subject->id]);
        }
    }

    public function toggleSalary(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();
        $data=$request->validate(['month'=>['required','date_format:Y-m'],'payment_method'=>['nullable','in:cash,mobile_money,bank_transfer'],'payment_phone'=>['nullable','string','max:50'],'transaction_id'=>['nullable','string','max:100'],'bank_details'=>['nullable','string','max:2000'],'save_payment_preference'=>['nullable','boolean']]);
        abort_unless((float)$user->monthly_salary_amount>0,422,__("Define the account's monthly salary."));
        $month=\Illuminate\Support\Carbon::createFromFormat('Y-m',$data['month'])->startOfMonth();
        $due=$month->copy()->day(min((int)($user->salary_payment_day?:1),$month->daysInMonth));
        $payment=EmployeePayment::firstOrNew(['user_id'=>$user->id,'due_date'=>$due->toDateString()]);
        if($payment->exists && $payment->paid_at){abort_unless(auth()->user()->hasRole('Admin'),403);$payment->update(['paid_at'=>null,'status'=>'pending','recorded_by'=>null,'payment_method'=>null,'payment_phone'=>null,'transaction_id'=>null,'bank_details'=>null]);app(ExpenseService::class)->removeSalaryPayment($payment);return back()->with('success',__('Salary payment canceled.'));}
        $this->confirmAdminPassword($request);
        abort_unless(filled($data['payment_method'] ?? null),422,__('Choose a payment method.'));
        if(($data['payment_method'] ?? null)==='mobile_money') $request->validate(['payment_phone'=>['required'],'transaction_id'=>['required']]);
        if(($data['payment_method'] ?? null)==='bank_transfer') $request->validate(['bank_details'=>['required'],'transaction_id'=>['required']]);
        $payment->fill(['reference'=>$payment->reference?:'SAL-'.$user->id.'-'.$month->format('Ym'),'amount'=>$user->monthly_salary_amount,'paid_at'=>now(),'status'=>'paid','recorded_by'=>auth()->id(),'payment_method'=>$data['payment_method'],'payment_phone'=>$data['payment_phone'] ?? null,'transaction_id'=>$data['transaction_id'] ?? null,'bank_details'=>$data['bank_details'] ?? null])->save();
        app(ExpenseService::class)->syncSalaryPayment($payment);
        if ($request->boolean('save_payment_preference') || blank($user->salary_payment_method)) {
            $user->update([
                'salary_payment_method' => $data['payment_method'],
                'salary_payment_phone' => $data['payment_method'] === 'mobile_money' ? ($data['payment_phone'] ?? null) : null,
                // A transaction identifier is unique to one payment and must never be reused.
                'salary_payment_transaction_hint' => null,
                'salary_payment_bank_details' => $data['payment_method'] === 'bank_transfer' ? ($data['bank_details'] ?? null) : null,
            ]);
        }
        activity('salaires')->causedBy(auth()->user())->performedOn($user)->log('Salaire payé.');
        return back()->with('success',__('Salary payment recorded.'))->with('salary_receipt', [
            'preview' => route('admin.users.salary.receipt-preview', [$user, $payment]),
            'download' => route('admin.users.salary.receipt', [$user, $payment, 'download' => 1]),
        ]);
    }

    public function salaryReceipt(Request $request, User $user, EmployeePayment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeAdmin();
        abort_unless($payment->user_id === $user->id && $payment->paid_at, 404);
        $payment->loadMissing('user', 'recordedBy');
        $pdf = Pdf::loadView('admin.users.salary-receipt', compact('user', 'payment'))->setPaper([0, 0, 255.12, 560]);
        $filename = 'recu-salaire-'.$payment->reference.'.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function salaryReceiptPreview(User $user, EmployeePayment $payment): View
    {
        $this->authorizeAdmin();
        abort_unless($payment->user_id === $user->id && $payment->paid_at, 404);
        $payment->loadMissing('user', 'recordedBy');
        return view('admin.users.salary-receipt', compact('user', 'payment'));
    }

    private function notifySalaryDue(User $user, $months): void
    {
        foreach($months as $item){ if($item['payment']?->paid_at) continue; if(!($item['due']->isToday() || $item['due']->isTomorrow() || $item['due']->isPast())) continue; $key='salary-due-'.$user->id.'-'.$item['date']->format('Y-m'); if(cache()->add($key,true,now()->endOfDay())) SensitiveActivityNotifier::send($item['due']->isPast()?'Salaire en retard':'Salaire à payer', $user->name.' : salaire à payer le '.$item['due']->format('d/m/Y').'.'); }
    }

    private function salaryPeriodStart(User $user): \Illuminate\Support\Carbon
    {
        // A salary period starts with the month in which the contract begins.
        // This applies to CDI as well as CDD: months before the start date are
        // not payable and must not appear as overdue.
        return $user->contract_start_date?->copy()->startOfMonth() ?: now()->startOfYear();
    }

    private function salaryPeriodEnd(User $user): \Illuminate\Support\Carbon
    {
        if ($user->contract_end_date) {
            return $user->contract_end_date->copy()->startOfMonth();
        }
        if ($user->contract_type === 'CDI') {
            return now()->endOfYear()->startOfMonth();
        }
        return now()->endOfYear()->startOfMonth();
    }

    private function confirmAdminPassword(Request $request): void
    {
        $request->validate(['admin_password' => ['required', 'string']]);
        if (! Hash::check($request->input('admin_password'), auth()->user()->password)) {
            throw ValidationException::withMessages(['admin_password' => __('The administrator password is incorrect.')]);
        }
    }

    private function invalidateUserSessions(User $user): void
    {
        DB::table('active_sessions')->where('user_id', $user->id)->update(['ended_at' => now(), 'updated_at' => now()]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    private function record(User $user, string $description): void
    {
        activity('comptes')->causedBy(auth()->user())->performedOn($user)->log($description);
    }
}
