<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsersRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function show(string $id)
    {
        //
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'title' => 'Изменить пользователя',
            'user' => User::query()->findOrFail($user->id),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->saved($request, $user);

        return redirect()->route('users.index')->with('success', 'Изменения сохранены.');
    }

    public function destroy(User $user): RedirectResponse
    {
        User::query()->findOrFail($user->id)->delete();

        return redirect()->route('users.index')->with('success', 'Пользователь удален');
    }

    public function profile()
    {
        return view('users.profile', [
            'title' => 'Профиль',
            'user' => auth()->user()
        ]);
    }

    public function profileUpdate(Request $request)
    {
        $this->saved($request, auth()->user());

        return redirect()->route('profile')->with('success', 'Изменения сохранены.');
    }

    private function saved(Request $request, User $user): void
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'required|confirmed|string|min:6';
        }

        $validatedData = $request->validate($rules);
        $user->update($validatedData);

        // Проверяем, был ли передан пароль, и если да, то сохраняем его
        if ($request->filled('password')) {
            $user->password = bcrypt($validatedData['password']);
            $user->save();
        }
    }
}
