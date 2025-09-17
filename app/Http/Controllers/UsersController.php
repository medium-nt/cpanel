<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotivationUpdateUsersRequest;
use App\Http\Requests\RateUpdateUsersRequest;
use App\Http\Requests\StoreUsersRequest;
use App\Models\Motivation;
use App\Models\Rate;
use App\Models\User;
use App\Services\ScheduleService;
use App\Services\TgService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $data = array();
        $data['users'] = User::query()->paginate(10);
        $data['title'] = 'Пользователи';

        return view('users.index', $data);
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
        User::query()->create($validate);

        return redirect()->route('users.index')->with('success', 'Пользователь добавлен');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'title' => 'Изменить пользователя',
            'user' => User::query()->findOrFail($user->id),
            'events' => ScheduleService::getScheduleByUserId($user->id),
            'motivations' => UserService::getMotivationByUserId($user->id),
            'isBeforeStartWorkDay' => ScheduleService::isBeforeStartWorkDay(),
            'rates' => UserService::getRateByUserId($user->id),
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
                'Поздравляю, ' . auth()->user()->name . '! Вы авторизовались в системе как '
                . UserService::translateRoleName(auth()->user()->role->name) .
                ' и теперь будете получать все уведомления системы через меня.'
            );

            Log::channel('tg_api')
                ->info(
                    'Сотрудник ' . auth()->user()->name . ' (' . auth()->user()->id . ') подключился к боту с tg_id: ' . $tgId
                );
        }

        return view('users.profile', [
            'title' => 'Профиль',
            'user' => auth()->user()
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

        Log::channel('tg_api')
            ->info(
                'Сотрудник ' . auth()->user()->name . ' (' . auth()->user()->id . ') отключился от бота.'
            );

        return redirect()->route('profile');
    }

    public function autologin(string $email)
    {
        if (!App::environment(['local'])) {
            abort(403, 'Доступ запрещён');
        }

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            abort(404, 'Пользователь не найден');
        }

        Auth::login($user);
        return redirect('/home');
    }

    public function motivationUpdate(MotivationUpdateUsersRequest $request, User $user)
    {
        Motivation::query()->where('user_id', $user->id)->delete();

        foreach ($request->from as $key => $value) {
            if($request->to[$key]) {
                Motivation::query()->create(
                    [
                        'user_id' => $user->id,
                        'from' => $request->from[$key],
                        'to' => $request->to[$key],
                        'bonus' => $request->bonus[$key] ?? 0,
                        'not_cutter_bonus' => $request->not_cutter_bonus[$key] ?? 0,
                        'cutter_bonus' => $request->cutter_bonus[$key] ?? 0
                    ]
                );
            }
        }

        return redirect()
            ->route('users.edit', ['user' => $user->id])
            ->with('success', 'Изменения в таблице мотивации сохранены.');
    }

    public function rateUpdate(RateUpdateUsersRequest $request, User $user)
    {
        Rate::query()->where('user_id', $user->id)->delete();

        foreach ($request->width as $key => $width) {
            Rate::query()->create(
                [
                    'user_id' => $user->id,
                    'width' => $width,
                    'rate' => $request->rate[$key] ?? 0,
                    'not_cutter_rate' => $request->not_cutter_rate[$key] ?? 0,
                    'cutter_rate' => $request->cutter_rate[$key] ?? 0
                ]
            );
        }

        return redirect()
            ->route('users.edit', ['user' => $user->id])
            ->with('success', 'Изменения в таблице зарплат сохранены.');
    }

    public function getBarcode(User $user)
    {
        return view('pdf.user_barcode', [
            'user' => $user
        ]);
    }
}
