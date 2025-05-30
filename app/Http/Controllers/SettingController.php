<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveSettingRequest;
use App\Models\Setting;
use App\Services\StackService;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('settings.index', [
            'title' => 'Настройки системы',
            'settings' => (object)Setting::query()->pluck('value', 'name')->toArray()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function save(SaveSettingRequest $request)
    {
        $data = $request->except(['_method', '_token']);

        foreach ($data as $name => $value) {
            if (is_null($value)) {
                $value = '';
            }
            Setting::query()->where('name', $name)->update(['value' => $value]);
        }

        return redirect()->route('setting.index')->with('success', 'Изменения сохранены');
    }

    public function test()
    {
        //  тестовая функция для запуска других методов только на продакшн сервере.
        if (!app()->environment('production')) {
            dd('Is test server');
        }
        dd('Is no development server');
    }
}
