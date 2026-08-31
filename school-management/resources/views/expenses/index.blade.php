<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800">{{ __('Expenses') }}</h2>
            @can('expenses.create')
                <button type="button" onclick="openExpenseModal()" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">
                    + {{ __('Add expense') }}
                </button>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-5 md:grid-cols-3">
                <div class="max-w-md rounded-2xl bg-gradient-to-br from-emerald-600 to-red-600 p-6 text-white shadow-lg" style="background:linear-gradient(135deg,rgba(16,185,129,.72) 0%,rgba(220,38,38,.72) 100%)">
                    <p class="text-sm font-medium text-orange-100">{{ __('Total expenses') }}</p>
                    <p class="mt-2 inline-flex w-fit max-w-full rounded-xl px-4 py-2 text-3xl font-bold shadow-inner" style="background:rgba(127,29,29,.48)">{{ number_format((float) $totalExpenses, 0, ',', ' ') }} Ar</p>
                    <p class="mt-1 text-xs text-orange-100">{{ __('Manual expenses and salary payments') }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 md:col-span-2">
                    <p class="text-sm font-semibold text-slate-700">{{ __('Expense ledger') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Salary payments are added automatically as expenses.') }}</p>
                </div>
            </div>

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('Description') }}</th>
                                <th class="px-5 py-3">{{ __('Date') }}</th>
                                <th class="px-5 py-3">{{ __('Time') }}</th>
                                <th class="px-5 py-3">{{ __('Added by') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($expenses as $expense)
                                @php($salary = $expense->employee_payment_id !== null)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-800">{{ $expense->reason }}</div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            <span class="rounded-full {{ $salary ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600' }} px-2 py-0.5">{{ $salary ? __('Salary payment') : $expense->category }}</span>
                                            <span class="ml-2">{{ $expense->reference }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ ($expense->spent_at ?? $expense->spent_on)?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $expense->spent_at?->format('H:i') ?? $expense->created_at?->format('H:i') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $expense->recordedBy?->localizedFunctionLabel() ?? __('System') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right"><p class="font-bold text-red-700">{{ number_format((float) $expense->amount, 0, ',', ' ') }} Ar</p></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">{{ __('No expense recorded.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($expenses->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $expenses->links() }}</div>
                @endif
            </section>
        </div>
    </div>

    @can('expenses.create')
        <div id="expense-modal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/70 p-4" style="background-color:rgba(0,0,0,.70)">
            <form method="POST" action="{{ route('expenses.store') }}" class="min-h-[390px] max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
                @csrf
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('Add expense') }}</h3>
                    <button type="button" onclick="closeExpenseModal()" class="rounded-lg px-3 py-1 text-xl text-slate-500 hover:bg-slate-100" aria-label="{{ __('Close') }}">×</button>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="sm:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">{{ __('Category') }}</span><input name="category" required maxlength="100" class="w-full rounded-lg border-slate-300" placeholder="{{ __('For example: supplies') }}"></label>
                    <label class="sm:col-span-2"><span class="mb-1 block text-sm font-medium text-slate-700">{{ __('Description') }}</span><textarea name="reason" required maxlength="1000" rows="3" class="w-full rounded-lg border-slate-300"></textarea></label>
                    <label><span class="mb-1 block text-sm font-medium text-slate-700">{{ __('Amount') }}</span><input type="number" name="amount" required min="0.01" step="0.01" class="w-full rounded-lg border-slate-300"></label>
                    <label><span class="mb-1 block text-sm font-medium text-slate-700">{{ __('Date and time') }}</span><input type="datetime-local" name="spent_at" required value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full rounded-lg border-slate-300"></label>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeExpenseModal()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">{{ __('Cancel') }}</button>
                    <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
        <script>
            function openExpenseModal(){const m=document.getElementById('expense-modal');m.classList.remove('hidden');m.classList.add('flex')}
            function closeExpenseModal(){const m=document.getElementById('expense-modal');m.classList.add('hidden');m.classList.remove('flex')}
        </script>
    @endcan
</x-app-layout>
