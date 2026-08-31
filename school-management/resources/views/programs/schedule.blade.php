<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ __('Schedule') }}</h2></x-slot>

    <div class="mx-auto max-w-7xl py-8 sm:px-6 lg:px-8">
        <section class="rounded-xl bg-white p-6 shadow">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('Schedule') }}</h3>
                    @if($selectedClass)<p class="mt-1 text-sm text-slate-500">{{ __('Schedule for :class', ['class' => $selectedClass->name]) }}</p>@endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($selectedClass)<a href="{{ route('programs.schedule.pdf', ['class_id' => $selectedClass->id]) }}" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">{{ __('Download PDF') }}</a>@endif
                    @if($isAdmin && $selectedClass)<button type="button" onclick="openScheduleSlotModal()" @disabled($teachers->isEmpty() || $subjects->isEmpty()) class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">{{ __('Add time slot') }}</button>@endif
                    <a href="{{ route('programs.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">{{ __('Back') }}</a>
                </div>
            </div>

            @if($classes->isNotEmpty())
                <div class="mt-5 flex flex-wrap gap-2 border-b border-slate-100 pb-4">
                    @foreach($classes as $schoolClass)
                        <a href="{{ route('programs.schedule', ['class_id' => $schoolClass->id]) }}" class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $selectedClass?->is($schoolClass) ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ $schoolClass->name }}</a>
                    @endforeach
                </div>

                @if($selectedClass)
                    @if($isAdmin)
                        <form id="schedule-slot-inline-form" method="POST" action="{{ route('programs.schedule.slots.store') }}" class="hidden">
                            @csrf
                            <input type="hidden" name="school_class_id" value="{{ $selectedClass->id }}">
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                                <label class="text-sm font-medium text-slate-700">{{ __('Monday') }}–{{ __('Saturday') }}
                                    <select name="day_of_week" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        @foreach($dayLabels as $day => $label)<option value="{{ $day }}">{{ $label }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="text-sm font-medium text-slate-700">{{ __('Subject') }}
                                    <select name="subject_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        <option value="" disabled selected>—</option>
                                        @foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->localizedName() }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="text-sm font-medium text-slate-700">{{ __('Teacher') }}
                                    <select name="teacher_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm" @disabled($teachers->isEmpty())>
                                        <option value="" disabled selected>—</option>
                                        @foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->first_name ?: $teacher->name }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="text-sm font-medium text-slate-700">{{ __('Start') }}
                                    <input type="time" name="starts_at" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </label>
                                <label class="text-sm font-medium text-slate-700">{{ __('End') }}
                                    <input type="time" name="ends_at" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </label>
                                <div class="flex items-end"><button @disabled($teachers->isEmpty() || $subjects->isEmpty()) class="w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">{{ __('Add time slot') }}</button></div>
                            </div>
                        </form>
                    @endif

                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left">
                            <thead>
                                <tr class="border-b border-slate-200 text-sm text-slate-500">
                                    <th class="p-3">{{ __('Monday') }}–{{ __('Saturday') }}</th>
                                    <th class="p-3">{{ __('Subject') }}</th>
                                    <th class="p-3">{{ __('Time') }}</th>
                                    @if($isAdmin)<th class="p-3">{{ __('Teacher') }}</th>@else<th class="p-3">{{ __('Class') }}</th>@endif
                                    @if($isAdmin)<th class="p-3 text-right">{{ __('Action') }}</th>@endif
                                </tr>
                            </thead>
                            <tbody class="text-sm text-slate-700">
                                @forelse($slots as $slot)
                                    <tr class="border-b border-slate-100 last:border-0">
                                        <td class="p-3">
                                            @if($isAdmin)<form id="schedule-slot-{{ $slot->id }}" method="POST" action="{{ route('programs.schedule.slots.update', $slot) }}" class="hidden">@csrf @method('PUT')<input type="hidden" name="school_class_id" value="{{ $selectedClass->id }}"></form><select form="schedule-slot-{{ $slot->id }}" name="day_of_week" class="w-full rounded-lg border-slate-300 text-sm">@foreach($dayLabels as $day => $label)<option value="{{ $day }}" @selected($slot->day_of_week === $day)>{{ $label }}</option>@endforeach</select>@else{{ $dayLabels[$slot->day_of_week] ?? '—' }}@endif
                                        </td>
                                        <td class="p-3">
                                            @if($isAdmin)<select form="schedule-slot-{{ $slot->id }}" name="subject_id" class="w-full rounded-lg border-slate-300 text-sm">@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected($slot->subject_id === $subject->id)>{{ $subject->localizedName() }}</option>@endforeach</select>@else{{ $slot->subject?->localizedName() ?? '—' }}@endif
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            @if($isAdmin)<div class="flex items-center gap-1"><input form="schedule-slot-{{ $slot->id }}" type="time" name="starts_at" value="{{ substr($slot->starts_at, 0, 5) }}" class="w-24 rounded-lg border-slate-300 text-sm"><span>–</span><input form="schedule-slot-{{ $slot->id }}" type="time" name="ends_at" value="{{ substr($slot->ends_at, 0, 5) }}" class="w-24 rounded-lg border-slate-300 text-sm"></div>@else{{ substr($slot->starts_at, 0, 5) }} – {{ substr($slot->ends_at, 0, 5) }}@endif
                                        </td>
                                        <td class="p-3">
                                            @if($isAdmin)<select form="schedule-slot-{{ $slot->id }}" name="teacher_id" class="w-full rounded-lg border-slate-300 text-sm">@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($slot->teacher_id === $teacher->id)>{{ $teacher->first_name ?: $teacher->name }}</option>@endforeach</select>@else{{ $selectedClass->name }}@endif
                                        </td>
                                        @if($isAdmin)<td class="p-3"><div class="flex justify-end gap-2"><button form="schedule-slot-{{ $slot->id }}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">{{ __('Save') }}</button><form method="POST" action="{{ route('programs.schedule.slots.destroy', $slot) }}">@csrf @method('DELETE')<button class="rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">{{ __('Delete') }}</button></form></div></td>@endif
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $isAdmin ? 5 : 4 }}" class="py-8 text-center text-slate-500">{{ __('No scheduled time slot.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            @else
                <p class="mt-6 rounded-lg bg-slate-50 p-5 text-center text-sm text-slate-500">{{ __('No class available.') }}</p>
            @endif
        </section>

        <section class="mt-6 rounded-xl bg-white p-6 shadow">
            <h3 class="text-lg font-semibold text-slate-900">{{ __('Teaching programs') }}</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[700px] text-left">
                    <thead><tr class="border-b border-slate-200 text-sm text-slate-500"><th class="p-3">{{ __('Program') }}</th><th class="p-3">{{ __('Subject') }}</th><th class="p-3">{{ __('Class') }}</th>@if($isAdmin)<th class="p-3">{{ __('Teacher') }}</th>@endif<th class="p-3">{{ __('Period') }}</th><th class="p-3">{{ __('Start') }}</th><th class="p-3">{{ __('End') }}</th></tr></thead>
                    <tbody class="text-sm text-slate-700">
                        @forelse($programs as $program)
                            <tr class="border-b border-slate-100 last:border-0"><td class="p-3 font-medium text-slate-900">{{ $program->title }}</td><td class="p-3">{{ $program->subject?->localizedName() ?? '—' }}</td><td class="p-3">{{ $program->schoolClass?->name ?? '—' }}</td>@if($isAdmin)<td class="p-3">{{ $program->teacher?->localizedFunctionLabel() ?? '—' }}</td>@endif<td class="p-3">{{ $program->period_name }}</td><td class="p-3 whitespace-nowrap">{{ $program->starts_on?->format('d/m/Y') }}</td><td class="p-3 whitespace-nowrap">{{ $program->ends_on?->format('d/m/Y') }}</td></tr>
                        @empty
                            <tr><td colspan="{{ $isAdmin ? 7 : 6 }}" class="py-8 text-center text-slate-500">{{ __('No program created.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @if($isAdmin && $selectedClass)
        <div id="schedule-slot-modal" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-black/70 p-4" style="background-color:rgba(0,0,0,.70)">
            <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('Add time slot') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $selectedClass->name }}</p>
                    </div>
                    <button type="button" onclick="closeScheduleSlotModal()" class="rounded-lg px-3 py-2 text-xl leading-none text-slate-500 transition hover:bg-slate-100" aria-label="{{ __('Close') }}">×</button>
                </div>

                <form method="POST" action="{{ route('programs.schedule.slots.store') }}" class="p-6">
                    @csrf
                    <input type="hidden" name="school_class_id" value="{{ $selectedClass->id }}">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-medium text-slate-700">{{ __('Monday') }}–{{ __('Saturday') }}
                            <select name="day_of_week" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                                @foreach($dayLabels as $day => $label)<option value="{{ $day }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-slate-700">{{ __('Subject') }}
                            <select name="subject_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <option value="" disabled selected>—</option>
                                @foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->localizedName() }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-slate-700">{{ __('Teacher') }}
                            <select name="teacher_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <option value="" disabled selected>—</option>
                                @foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->first_name ?: $teacher->name }}</option>@endforeach
                            </select>
                        </label>
                        <div class="hidden sm:block"></div>
                        <label class="text-sm font-medium text-slate-700">{{ __('Start') }}
                            <input type="time" name="starts_at" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </label>
                        <label class="text-sm font-medium text-slate-700">{{ __('End') }}
                            <input type="time" name="ends_at" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </label>
                    </div>
                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <button type="button" onclick="closeScheduleSlotModal()" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">{{ __('Cancel') }}</button>
                        <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">{{ __('Add time slot') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            function openScheduleSlotModal() {
                const modal = document.getElementById('schedule-slot-modal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
            function closeScheduleSlotModal() {
                const modal = document.getElementById('schedule-slot-modal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        </script>
    @endif
</x-app-layout>
