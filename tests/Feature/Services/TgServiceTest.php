<?php

namespace Tests\Feature\Services;

use App\Services\TgService;
use GuzzleHttp\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;
use Tests\TestCase;

class TgServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_send_message_does_not_send_if_chat_id_is_null()
    {
        $this->app->instance('env', 'development');

        $mock = \Mockery::mock('overload:'.Client::class);
        $mock->shouldNotReceive('post');

        TgService::sendMessage(null, 'Test message');

        $this->assertTrue(true);
    }

    public function test_send_message_uses_guzzle_in_development()
    {
        $this->app->instance('env', 'development');

        $chatId = '12345';
        $message = 'Test message';
        $token = config('telegram.bots.mybot.token');
        $expectedUrl = 'https://api.telegram.org/bot' . $token . '/sendMessage';
        $expectedFormParams = [
            'form_params' => [
                'chat_id' => $chatId,
                'text' => $message
            ]
        ];

        $mock = \Mockery::mock('overload:'.Client::class);
        $mock->shouldReceive('post')
             ->once()
             ->with($expectedUrl, $expectedFormParams);

        TgService::sendMessage($chatId, $message);

        $this->assertTrue(true);
    }

    public function test_send_message_uses_telegram_facade_in_production()
    {
        $this->app->instance('env', 'production');

        $chatId = '12345';
        $message = 'Test message';

        // Создаем частичный мок из реального объекта final класса
        $partialMock = \Mockery::mock($this->app['telegram.bot'])->makePartial();
        $partialMock->shouldReceive('sendMessage')
            ->once()
            ->with([
                'chat_id' => $chatId,
                'text' => $message
            ]);

        // Подменяем реализацию в фасаде
        Telegram::swap($partialMock);

        TgService::sendMessage($chatId, $message);
        $this->assertTrue(true);
    }

    public function test_send_message_logs_error_on_sdk_exception_in_production()
    {
        $this->app->instance('env', 'production');

        $chatId = '12345';
        $message = 'Test message';
        $exceptionMessage = 'Telegram error';

        // Создаем частичный мок и заставляем его выбросить исключение
        $partialMock = \Mockery::mock($this->app['telegram.bot'])->makePartial();
        $partialMock->shouldReceive('sendMessage')
            ->once()
            ->with([
                'chat_id' => $chatId,
                'text' => $message
            ])
            ->andThrow(new TelegramSDKException($exceptionMessage));

        // Подменяем реализацию в фасаде
        Telegram::swap($partialMock);

        Log::shouldReceive('channel')->with('tg_api')->andReturnSelf();
        Log::shouldReceive('error')->once()->with('chat_id: ' . $chatId);
        Log::shouldReceive('error')->once()->with('Ошибка Telegram: ' . $exceptionMessage);

        TgService::sendMessage($chatId, $message);
        $this->assertTrue(true);
    }
}
