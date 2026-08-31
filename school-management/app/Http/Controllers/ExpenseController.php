<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Services\ExpenseService;
use App\Services\SensitiveActivityNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * Display the expense ledger, including salary payments already recorded
     * through the account-management screen.
     */
    public function index(ExpenseService $expenses): View
    {
        abort_unless(auth()->user()?->can('expenses.view'), 403);

        // Salary payments are expenses as well. Synchronising here also brings
        // payments created before the expenses screen was introduced into the
        // ledger without creating duplicates.
        $expenses->synchronizePaidSalaryExpenses();

        return view('expenses.index', [
            'totalExpenses' => Expense::sum('amount'),
            'expenses' => Expense::query()
                ->with(['recordedBy', 'employeePayment.user'])
                ->orderByDesc('spent_at')
                ->orderByDesc('spent_on')
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }

    /**
     * Record a non-salary expense. Salary entries are created automatically
     * from employee payments and are intentionally not entered here.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('expenses.create'), 403);

        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'spent_at' => ['required', 'date'],
        ]);

        $spentAt = Carbon::parse($data['spent_at']);
        $expense = Expense::create([
            'reference' => $this->nextReference(),
            'category' => $data['category'],
            'reason' => $data['reason'],
            'amount' => $data['amount'],
            'spent_on' => $spentAt->toDateString(),
            'spent_at' => $spentAt,
            'recorded_by' => $request->user()->id,
        ]);

        activity('dépenses')
            ->causedBy($request->user())
            ->performedOn($expense)
            ->log('Expense added: '.$expense->reason);

        SensitiveActivityNotifier::send(
            __('Expense added'),
            __('Expense added: :expense', ['expense' => $expense->reason]),
        );

        return back()->with('success', __('Expense added successfully.'));
    }

    private function nextReference(): string
    {
        do {
            $reference = 'EXP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
        } while (Expense::where('reference', $reference)->exists());

        return $reference;
    }
}
