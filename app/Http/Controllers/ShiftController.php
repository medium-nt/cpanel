<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftScheduleRequest;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use App\Models\Workshop;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(): View
    {
        $shifts = Shift::withCount('users')->with(['rolls', 'workshop'])->get();

        return view('shifts.index', compact('shifts'));
    }

    public function create(): View
    {
        $workshops = Workshop::query()->where('status', Workshop::STATUS_ACTIVE)->get();

        return view('shifts.create', compact('workshops'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'workshop_id' => 'required|exists:workshops,id',
        ]);

        Shift::create([
            'name' => $validated['name'],
            'workshop_id' => $validated['workshop_id'],
            'status' => Shift::STATUS_ACTIVE,
        ]);

        return redirect()->route('shifts.index')->with('success', 'Смена создана');
    }

    public function show(Shift $shift): View
    {
        $shift->load(['rolls.material', 'users.role']);

        $employees = $shift->getCurrentUsers();
        $incoming = $shift->getIncomingUsers();
        $outgoing = $shift->getOutgoingUsers();
        $history = $shift->getUsersHistory();

        // Активные цеха для select и проверка наличия будущего расписания
        $workshops = Workshop::query()->where('status', Workshop::STATUS_ACTIVE)->get();
        $hasFutureSchedule = DB::table('shift_schedule')
            ->where('shift_id', $shift->id)
            ->where('date', '>=', now()->toDateString())
            ->exists();

        return view('shifts.show', compact('shift', 'employees', 'incoming', 'outgoing', 'history', 'workshops', 'hasFutureSchedule'));
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'workshop_id' => 'required|exists:workshops,id',
        ]);

        // Проверка: нельзя менять цех, если есть расписание на будущие дни
        if ((int) $validated['workshop_id'] !== $shift->workshop_id) {
            $hasFutureSchedule = DB::table('shift_schedule')
                ->where('shift_id', $shift->id)
                ->where('date', '>=', now()->toDateString())
                ->exists();

            if ($hasFutureSchedule) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Нельзя изменить цех: у смены есть расписание на будущие дни.');
            }
        }

        $shift->update($validated);

        return redirect()->route('shifts.show', $shift)->with('success', 'Смена обновлена');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $shift->update(['status' => Shift::STATUS_INACTIVE]);

        return redirect()->route('shifts.index')->with('success', 'Смена деактивирована');
    }

    public function attachUser(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $shift->users()->attach($validated['user_id'], [
            'effective_from' => now()->toDateString(),
        ]);

        return redirect()->route('shifts.show', $shift)->with('success', 'Сотрудник добавлен');
    }

    public function detachUser(Shift $shift, User $user): RedirectResponse
    {
        $shift->users()->wherePivot('user_id', $user->id)->detach();

        return redirect()->route('shifts.show', $shift)->with('success', 'Сотрудник удалён из смены');
    }

    public function destroyRecord(Shift $shift, int $recordId): RedirectResponse
    {
        DB::table('shift_user')->where('id', $recordId)->delete();

        return redirect()->route('shifts.show', $shift)->with('success', 'Запись удалена');
    }

    public function transferUser(Request $request, Shift $shift, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'new_shift_id' => 'required|exists:shifts,id',
            'effective_from' => 'required|date|after_or_equal:today',
        ]);

        $newShift = Shift::findOrFail($validated['new_shift_id']);
        ShiftService::transferEmployee($user, $newShift, $validated['effective_from']);

        return redirect()->route('shifts.show', $shift)->with('success', 'Перевод сотрудника запланирован');
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $users = User::query()
            ->whereIn('role_id', function ($q) {
                $q->select('id')->from('roles')
                    ->whereIn('name', ['seamstress', 'cutter', 'otk']);
            })
            ->whereDoesntHave('shifts')
            ->when($query, function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($users);
    }

    public function scheduleIndex(Request $request): View
    {
        $workshopId = $request->input('workshop_id', 1);
        $workshops = Workshop::query()->where('status', Workshop::STATUS_ACTIVE)->get();

        // Фильтруем смены по выбранному цеху
        $shifts = Shift::active()->where('workshop_id', $workshopId)->get();

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $currentDate = Carbon::createFromDate($year, $month, 1);
        $prevMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        return view('shift-schedule.index', compact('shifts', 'currentDate', 'prevMonth', 'nextMonth', 'workshops', 'workshopId'));
    }

    public function scheduleStore(StoreShiftScheduleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $workshopId = (int) $validated['workshop_id'];

        $data = [];
        $dates = [];
        foreach ($validated['dates'] as $entry) {
            $dates[] = $entry['date'];
            // Пропускаем пустые значения ('' / null) — для них не создаём запись.
            // 'day_off' и id смены — оба truthy и проходят.
            if ($entry['shift_id']) {
                $data[$entry['date']] = $entry['shift_id'];
            }
        }

        // Удаляем старые записи цеха в диапазоне сохраняемых дат
        // (включая прежние «выходные» — у них shift_id NULL).
        if ($dates) {
            ShiftSchedule::query()
                ->where('workshop_id', $workshopId)
                ->whereIn('date', $dates)
                ->delete();
        }

        ShiftService::fillSchedule($data, $workshopId);

        return redirect()->route('shift-schedule.index', [
            'month' => $request->input('month', now()->month),
            'year' => $request->input('year', now()->year),
            'workshop_id' => $workshopId,
        ])->with('success', 'Календарь обновлён');
    }
}
