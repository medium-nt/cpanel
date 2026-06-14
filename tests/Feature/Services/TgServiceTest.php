<?php

namespace Tests\Feature\Services;

use App\Services\TgService;
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
        // Сначала откатываем транзакцию RefreshDatabase, затем чистим моки.
        // Если Mockery::close() бросает невыполненные expectations ДО parent::tearDown(),
        // rollBack пропускается → транзакция течёт в следующий тест
        // ("There is already an active transaction").
        parent::tearDown();
        \Mockery::close();
    }

    public function test_send_message_does_not_send_if_chat_id_is_null()
    {
        // chatId null → ранний return: логирование не выполняется.
        // Telegram::shouldReceive() не используем — BotsManager объявлен final,
        // Mockery не может его заменить (нужен swap с partial mock, см. тесты production ниже).
        Log::shouldReceive('channel')->never();

        TgService::sendMessage(null, 'Test message');

        $this->assertTrue(true);
    }

    public function test_send_message_logs_in_non_production_environment()
    {
        $this->app->instance('env', 'development');

        // В non-production ветке сервис только логирует (Guzzle больше не используется).
        // Telegram не вызывается — BotsManager final, мокаем только Log.
        Log::shouldReceive('channel')->once()->with('tg')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Отправляется сообщение... в ТГ: 12345 : Test message');

        TgService::sendMessage('12345', 'Test message');

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
                'text' => $message,
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
                'text' => $message,
            ])
            ->andThrow(new TelegramSDKException($exceptionMessage));

        // Подменяем реализацию в фасаде
        Telegram::swap($partialMock);

        Log::shouldReceive('channel')->with('tg')->andReturnSelf();
        Log::shouldReceive('error')->once()->with('chat_id: '.$chatId);
        Log::shouldReceive('error')->once()->with('Ошибка Telegram: '.$exceptionMessage);

        TgService::sendMessage($chatId, $message);
        $this->assertTrue(true);
    }
}
