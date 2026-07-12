<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;

class UserService
{
    /** Размер стороны аватара в пикселях (квадрат). */
    private const AVATAR_SIZE = 256;

    public static function translateRoleName($role): string
    {
        $roleName = '---';
        match ($role) {
            'admin' => $roleName = 'Руководитель',
            'storekeeper' => $roleName = 'Кладовщик',
            'seamstress' => $roleName = 'Швея',
            'cutter' => $roleName = 'Закройщик',
            'otk' => $roleName = 'Сотрудник ОТК',
            'driver' => $roleName = 'Водитель',
            'manager' => $roleName = 'Менеджер маркетплейса',
            'cleaner' => $roleName = 'Уборщица',
            default => $roleName,
        };

        return $roleName;
    }

    /**
     * Возвращает коллекцию пользователей, подключённых к MAX (max_id заполнено),
     * отсортированных по ФИО.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function getConnectedToMaxUsers(): Collection
    {
        return User::query()
            ->whereNotNull('max_id')
            ->where('max_id', '!=', '')
            ->orderBy('name')
            ->get();
    }

    /**
     * Построить запрос пользователей с фильтрами по роли и текущему цеху.
     *
     * Возвращает Builder для дальнейшей пагинации в контроллере.
     * Фильтр по цеху эквивалентен User::currentWorkshop(): берётся последняя
     * (максимальная effective_from <= сегодня) смена сотрудника и сравнивается
     * её workshop_id с запрошенным.
     */
    public static function getFiltered(Request $request): Builder
    {
        $today = Carbon::today()->toDateString();

        return User::query()
            ->with('role')
            ->when($request->filled('role_id'), fn (Builder $q) => $q->where('role_id', $request->integer('role_id')))
            ->when($request->filled('workshop_id'), function (Builder $q) use ($request, $today) {
                $workshopId = $request->integer('workshop_id');
                // workshop_id текущей смены сотрудника (NULL, если нет смены <= сегодня) = X
                $q->whereRaw('(
                    SELECT s.workshop_id
                    FROM shift_user su
                    JOIN shifts s ON s.id = su.shift_id
                    WHERE su.user_id = users.id
                      AND su.effective_from <= ?
                    ORDER BY su.effective_from DESC
                    LIMIT 1
                ) = ?', [$today, $workshopId]);
            });
    }

    /**
     * Работающие сегодня швеи (опционально по цеху) с привязанным мессенджером.
     *
     * @return Collection<int, User>
     */
    public static function getListSeamstressesWorkingToday(?int $workshopId = null): Collection
    {
        return self::getListEmployeesWorkingTodayByRole(1, $workshopId);
    }

    /**
     * Работающие сегодня кладовщики с привязанным мессенджером.
     *
     * @return Collection<int, User>
     */
    public static function getListStorekeepersWorkingToday(): Collection
    {
        return self::getListEmployeesWorkingTodayByRole(2);
    }

    /**
     * Активные менеджеры с привязанным мессенджером (Telegram или MAX).
     *
     * @return Collection<int, User>
     */
    public static function getListManagersWithTg(): Collection
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'manager'))
            ->where(fn ($q) => $q->whereNotNull('tg_id')->orWhereNotNull('max_id'))
            ->get();
    }

    /**
     * Работающие сегодня сотрудники по роли (опционально по цеху) с привязанным мессенджером.
     *
     * @return Collection<int, User>
     */
    private static function getListEmployeesWorkingTodayByRole($roleId, ?int $workshopId = null): Collection
    {
        $users = Schedule::query()
            ->where('date', now()->toDateString())
            ->whereHas('user', function ($query) use ($roleId) {
                $query->where('role_id', $roleId)
                    ->where(fn ($q) => $q->whereNotNull('tg_id')->orWhereNotNull('max_id'));
            })
            ->with('user')
            ->distinct()
            ->get()
            ->pluck('user')
            ->unique();

        // Фильтр по цеху: оставляем только сотрудников, чья текущая смена в указанном цехе
        if ($workshopId !== null) {
            $users = $users->filter(fn (User $user) => $user->currentWorkshop()?->id === $workshopId);
        }

        return $users;
    }

    public static function sendMessageForWorkingTodayEmployees(): void
    {
        $schedules = Schedule::query()
            ->where('date', now()->toDateString())
            ->with('user')
            ->distinct()
            ->get()
            ->unique();

        $list = '';
        foreach ($schedules as $schedule) {
            if ($schedule->user && $schedule->user->role) {
                $list .= '• '.$schedule->user->name.' ('.UserService::translateRoleName($schedule->user->role->name).')'."\n";
            }
        }

        $text = "Сегодня работают: \n".$list;

        foreach ($schedules as $index => $schedule) {
            if ($schedule->user) {
                NotificationService::notify($schedule->user, $text, queued: true, delaySeconds: $index + 1);
            }
        }

        Log::channel('work_shift')->notice('В ТГ отправлено сообщение сотрудникам: '.$text);
    }

    public static function hasUnpaidSalary(User $user): bool
    {
        return Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('paid_at')
            ->exists();
    }

    public static function saved(Request $request, User $user): bool
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'sometimes|nullable|string|min:8|max:50',
            'password' => 'nullable|confirmed|string|min:6',
            'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'orders_priority' => 'string|in:all,fbo,fbo_200',
            'is_cutter' => 'boolean',
            'start_work_shift' => 'sometimes|date_format:H:i',
            'duration_work_shift' => 'sometimes|date_format:H:i|after_or_equal:00:00|before_or_equal:15:00',
            'max_late_minutes' => 'sometimes|numeric|min:0|max:180',
            'materials' => 'nullable|array|exists:materials,id',
            'is_show_finance' => 'boolean',
        ];

        $validatedData = $request->validate($rules);

        if ($request->filled('password')) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        if ($request->hasFile('avatar')) {
            $validatedData['avatar'] = self::saveAvatar($request->file('avatar'), $user);
        }

        if (auth()->user()->isAdmin()) {
            $user->materials()->sync($validatedData['materials'] ?? []);
        }

        $updated = $user->update($validatedData);

        Log::channel('users')->info('Обновлён профиль пользователя', [
            'user_id' => $user->id,
            'changed' => collect($user->getChanges())->except(['password', 'updated_at'])->keys(),
            'updated_by' => auth()->id(),
        ]);

        return $updated;
    }

    /**
     * Сохраняет аватар пользователя: нормализует изображение через Intervention Image
     * до квадрата AVATAR_SIZE и кодирует в PNG. Возвращает относительный путь файла в disk('public').
     *
     * @throws ValidationException если изображение не удалось декодировать/обработать
     *                             (например, формат не поддерживается сборкой GD на сервере).
     */
    private static function saveAvatar(UploadedFile $file, User $user): string
    {
        if (! Storage::disk('public')->exists('avatars')) {
            Storage::disk('public')->makeDirectory('avatars');
        }

        try {
            $image = (new ImageManager(new Driver))
                ->decode($file)
                ->cover(self::AVATAR_SIZE, self::AVATAR_SIZE);

            $path = 'avatars/'.$user->id.'.png';
            Storage::disk('public')->put($path, (string) $image->encode(new PngEncoder));
        } catch (\Throwable $e) {
            Log::channel('users')->error('Не удалось обработать аватар', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'avatar' => 'Не удалось обработать изображение. Попробуйте JPG или PNG.',
            ]);
        }

        return $path;
    }

    public static function getUserByBarcode($barcode): ?User
    {
        $parts = explode('-', $barcode);
        $id = $parts[1] ?? null;

        $user = User::query()->find($id);
        if ($user) {
            return $user;
        }

        return null;
    }

    public static function checkWorkShiftClosure(User $user): void
    {
        $previousUserWhoClosedShift = User::query()
            ->where('shift_is_open', false)
            ->where('closed_work_shift', '!=', '00:00:00')
            ->where('id', '!=', $user->id)
            ->orderByDesc('closed_work_shift')
            ->first();

        if (! $previousUserWhoClosedShift) {
            return;
        }

        $closedTime = Carbon::createFromFormat('H:i:s', $previousUserWhoClosedShift->closed_work_shift)
            ->setDate(now()->year, now()->month, now()->day);

        $minutes = 2;

        if ($closedTime->diffInMinutes(now()) < $minutes) {
            $text = 'Внимание! Сотрудник '.$user->name.' ('.$user->id.') '.
                'пытался закрыть смену, сразу после '.$previousUserWhoClosedShift->name.' ('.$previousUserWhoClosedShift->id.').';

            Log::channel('work_shift')->error($text);

            NotificationService::notifyAdmin($text);
        }
    }

    /**
     * Проверяет незакрытые смены: начисляет штраф сотрудникам и закрывает смены.
     * (админы освобождены от штрафа).
     */
    public static function checkUnclosedWorkShifts(): void
    {
        $users = User::query()
            ->where('shift_is_open', true)
            ->get();

        $amount = Setting::getValue('unclosed_shift_penalty');
        $actualDate = now()->subDay();

        foreach ($users as $user) {
            if (! $user->isAdmin()) {
                Transaction::query()->create([
                    'user_id' => $user->id,
                    'title' => 'Штраф за незакрытую смену '.$actualDate->format('d/m/Y'),
                    'accrual_for_date' => $actualDate->format('Y-m-d'),
                    'amount' => $amount,
                    'transaction_type' => 'in',
                    'status' => 1,
                ]);

                Log::channel('salary')->info(
                    "Сотруднику $user->name (id $user->id) начислен штраф за незакрытую смену "
                    .$actualDate->format('d/m/Y')." в размере $amount бонусов."
                );
            }

            $user->shift_is_open = false;
            $user->closed_work_shift = '00:00:00';
            $user->save();
        }
    }

    /**
     * Проверяет опоздание сотрудника на смену и начисляет штраф.
     * (админы освобождены от штрафа).
     */
    public static function checkLateStartWorkShift(User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $start_work_shift = Carbon::parse($user->start_work_shift);
        $maxLateTime = $start_work_shift->addMinutes($user->max_late_minutes);

        if ($maxLateTime->lessThan(now())) {
            $amount = Setting::getValue('late_opened_shift_penalty');
            $actualDate = now();

            Transaction::query()->create([
                'user_id' => $user->id,
                'title' => 'Штраф за опоздание на смену '.$actualDate->format('d/m/Y'),
                'accrual_for_date' => $actualDate->format('Y-m-d'),
                'amount' => $amount,
                'transaction_type' => 'in',
                'status' => 1,
            ]);

            Log::channel('salary')->info(
                "Сотруднику $user->name (id $user->id) начислен штраф за опоздание за смену "
                .$actualDate->format('d/m/Y')." в размере $amount бонусов."
            );
        }
    }

    public static function clearTimeForClosedWorkShifts(): void
    {
        User::query()
            ->where('shift_is_open', false)
            ->where('closed_work_shift', '!=', '00:00:00')
            ->update([
                'closed_work_shift' => '00:00:00',
            ]);
    }

    public static function isSecondShiftOpeningToday(User $user): bool
    {
        return Schedule::query()
            ->where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->where('shift_opened_time', '!=', '00:00:00')
            ->exists();
    }
}
