<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsersRequest;
use App\Http\Requests\TariffsUpdateRequest;
use App\Models\Material;
use App\Models\Role;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UserTariff;
use App\Models\Workshop;
use App\Services\ScheduleService;
use App\Services\TgService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    /**
     * Список сотрудников с фильтрацией по роли и текущему цеху.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = UserService::getFiltered($request)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'title' => 'Пользователи',
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'workshops' => Workshop::active()->orderBy('title')->get(),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'title' => 'Добавить сотрудника',
        ]);
    }

    public function store(StoreUsersRequest $request): RedirectResponse
    {
        $validate = $request->safe()->toArray();
        $user = User::query()->create($validate);

        Log::channel('users')
            ->info('Создан пользователь', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'workshop_id' => $user->workshop_id,
                'created_by' => auth()->id(),
            ]);

        return redirect()->route('users.index')->with('success', 'Пользователь добавлен');
    }

    public function edit(User $user): View
    {
        $user = User::query()->findOrFail($user->id);
        $role = $user->role->name;

        $actions = \App\Models\UserTariff::getActionsForRole($role);

        $userTariffsCollection = $user->userTariffs()->with('tariffs')->get();

        // Разделяем тарифы на Зарплату и Бонусы
        $userTariffsSalary = collect($actions)->mapWithKeys(function ($action) use ($userTariffsCollection) {
            $userTariff = $userTariffsCollection->where('action', $action)->where('is_bonus', false)->first();
            if (! $userTariff) {
                return [$action => new \App\Models\UserTariff(['action' => $action, 'type' => '', 'is_bonus' => false])];
            }

            return [$action => $userTariff];
        });

        $userTariffsBonus = collect($actions)->mapWithKeys(function ($action) use ($userTariffsCollection) {
            $userTariff = $userTariffsCollection->where('action', $action)->where('is_bonus', true)->first();
            if (! $userTariff) {
                return [$action => new \App\Models\UserTariff(['action' => $action, 'type' => '', 'is_bonus' => true])];
            }

            return [$action => $userTariff];
        });

        // Загружаем диапазоны для каждого действия отдельно для ЗП и Бонусов
        $tariffRangesSalary = [];
        $tariffRangesBonus = [];

        foreach ($actions as $action) {
            if ($action === 'Оклад') {
                continue;
            }

            // Для Зарплаты
            $ranges = Tariff::query()
                ->whereHas('userTariff', function ($q) use ($user, $action) {
                    $q->where('user_id', $user->id)->where('action', $action)->where('is_bonus', false);
                })
                ->whereNotNull('range')
                ->pluck('range')
                ->unique()
                ->sort()
                ->values();

            $tariffRangesSalary[$action] = $ranges;

            // Для Бонусов
            $rangesBonus = Tariff::query()
                ->whereHas('userTariff', function ($q) use ($user, $action) {
                    $q->where('user_id', $user->id)->where('action', $action)->where('is_bonus', true);
                })
                ->whereNotNull('range')
                ->pluck('range')
                ->unique()
                ->sort()
                ->values();

            $tariffRangesBonus[$action] = $rangesBonus;
        }

        return view('users.edit', [
            'title' => 'Изменить пользователя',
            'user' => $user,
            'events' => ScheduleService::getScheduleByUserId($user->id),
            'isBeforeStartWorkDay' => ScheduleService::isBeforeStartWorkDay($user),
            'materials' => Material::query()->where('type_id', 1)->get(),
            'selectedMaterials' => $user->materials()->pluck('id')->toArray(),
            'userTariffsSalary' => $userTariffsSalary,
            'userTariffsBonus' => $userTariffsBonus,
            'tariffActions' => $actions,
            'tariffRangesSalary' => $tariffRangesSalary,
            'tariffRangesBonus' => $tariffRangesBonus,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (UserService::saved($request, $user)) {
            return back()->with('success', 'Изменения сохранены.');
        }

        return back()->with('error', 'Ошибка сохранения');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (UserService::hasUnpaidSalary($user)) {
            return back()
                ->with('error', 'У сотрудника есть невыплаченная зп. Удаление невозможно.');
        }

        Log::channel('users')
            ->warning('Удалён пользователь', [
                'user_id' => $user->id,
                'name' => $user->name,
                'deleted_by' => auth()->id(),
            ]);

        User::query()->findOrFail($user->id)->delete();

        return redirect()->route('users.index')->with('success', 'Пользователь удален');
    }

    public function profile(Request $request)
    {
        $tgId = $request->all()['tg_id'] ?? null;

        if ($tgId) {
            auth()->user()->update([
                'tg_id' => $tgId,
            ]);

            TgService::sendMessage(
                $tgId,
                'Поздравляю, '.auth()->user()->name.'! Вы авторизовались в системе как '
                .UserService::translateRoleName(auth()->user()->role->name).
                ' и теперь будете получать все уведомления системы через меня.'
            );

            Log::channel('tg')
                ->info(
                    'Сотрудник '.auth()->user()->name.' ('.auth()->user()->id.') подключился к боту с tg_id: '.$tgId
                );
        }

        return view('users.profile', [
            'title' => 'Профиль',
            'user' => auth()->user(),
        ]);
    }

    public function profileUpdate(Request $request)
    {
        if (UserService::saved($request, auth()->user())) {
            return redirect()->route('profile')->with('success', 'Изменения сохранены.');
        }

        return back()->with('error', 'Ошибка сохранения');
    }

    public function disconnectTg()
    {
        auth()->user()->update([
            'tg_id' => null,
        ]);

        Log::channel('tg')
            ->info(
                'Сотрудник '.auth()->user()->name.' ('.auth()->user()->id.') отключился от бота.'
            );

        return redirect()->route('profile');
    }

    public function autologin(string $email)
    {
        if (! App::environment(['local'])) {
            abort(403, 'Доступ запрещён');
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            abort(404, 'Пользователь не найден');
        }

        Auth::login($user);

        return redirect('/home');
    }

    public function getBarcode(User $user)
    {
        return view('pdf.user_barcode', [
            'user' => $user,
        ]);
    }

    public function tariffsUpdate(TariffsUpdateRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $role = $user->role->name;
        $actions = UserTariff::getActionsForRole($role);

        // Удаляем старые тарифы пользователя
        UserTariff::where('user_id', $user->id)->delete();

        // Обрабатываем оба типа: salary и bonus
        foreach (['salary', 'bonus'] as $bonusType) {
            $isBonus = $bonusType === 'bonus';

            // Сохраняем оклад
            if (isset($data[$bonusType]['fixed_salary_per_day']) && $data[$bonusType]['fixed_salary_per_day'] > 0) {
                $userTariff = UserTariff::create([
                    'user_id' => $user->id,
                    'action' => 'Оклад',
                    'type' => 'fixed',
                    'is_bonus' => $isBonus,
                ]);

                Tariff::create([
                    'user_tariff_id' => $userTariff->id,
                    'material_id' => null,
                    'range' => null,
                    'width' => null,
                    'value' => $data[$bonusType]['fixed_salary_per_day'],
                ]);
            }

            foreach ($actions as $action) {
                if ($action === 'Оклад') {
                    continue;
                }

                $type = $data[$bonusType]['tariffs'][$action]['type'] ?? null;

                if (! $type) {
                    continue; // Пропускаем, если "не начислять"
                }

                // Создаём UserTariff
                $userTariff = UserTariff::create([
                    'user_id' => $user->id,
                    'action' => $action,
                    'type' => $type,
                    'is_bonus' => $isBonus,
                ]);

                // Создаём тарифы в зависимости от типа
                if ($type === 'per_meter') {
                    // Получаем диапазоны динамически из входящих данных
                    $ranges = array_keys($data[$bonusType]['tariffs'][$action]['per_meter'] ?? []);

                    foreach ($ranges as $range) {
                        foreach ($data[$bonusType]['tariffs'][$action]['per_meter'][$range] ?? [] as $materialId => $value) {
                            $value = is_numeric($value) ? floatval($value) : null;
                            if ($value !== null && $value > 0) {
                                Tariff::create([
                                    'user_tariff_id' => $userTariff->id,
                                    'material_id' => $materialId,
                                    'range' => $range,
                                    'value' => $value,
                                ]);
                            }
                        }
                    }
                } elseif ($type === 'per_piece') {
                    $widths = ['200', '300', '400', '500', '600', '700', '800'];
                    foreach ($widths as $width) {
                        foreach ($data[$bonusType]['tariffs'][$action]['per_piece'][$width] ?? [] as $materialId => $value) {
                            $value = is_numeric($value) ? floatval($value) : null;
                            if ($value !== null && $value > 0) {
                                Tariff::create([
                                    'user_tariff_id' => $userTariff->id,
                                    'material_id' => $materialId,
                                    'width' => $width,
                                    'value' => $value,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $tariffsCount = Tariff::whereHas('userTariff', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        Log::channel('users')
            ->info('Обновлены тарифы пользователя', [
                'user_id' => $user->id,
                'tariffs_count' => $tariffsCount,
                'updated_by' => auth()->id(),
            ]);

        return redirect()
            ->route('users.edit', ['user' => $user->id])
            ->with('success', 'Тарифы сохранены.');
    }
}
