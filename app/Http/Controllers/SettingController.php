<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveSettingRequest;
use App\Models\Setting;
use GuzzleHttp\Client;
use Telegram\Bot\Laravel\Facades\Telegram;

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

            $chatId = 6523232418;

//            Telegram::sendMessage([
//                'chat_id' => $chatId,
//                'text' => 'Привет! Я работаю!'
//            ]);

            $client = new Client([
                'verify' => false,
                'timeout' => 30,
                'connect_timeout' => 30,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]
            ]);

            $client->post('https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/sendMessage', [
                'form_params' => [
                    'chat_id' => $chatId,
                    'text' => 'Привет! Я работаю!'
                ]
            ]);

            dd('Is test server');
        }
        dd('Is no development server');
    }
}
