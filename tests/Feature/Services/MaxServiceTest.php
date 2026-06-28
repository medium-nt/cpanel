<?php

namespace Tests\Feature\Services;

use App\Services\MaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MaxServiceTest extends TestCase
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

    #[Test]
    public function it_does_not_send_if_chat_id_is_null()
    {
        // chatId null → ранний return: логирование не выполняется.
        Log::shouldReceive('channel')->never();

        MaxService::sendMessage(null, 'Test message');

        $this->assertTrue(true);
    }

    #[Test]
    public function it_logs_in_non_production_environment()
    {
        $this->app->instance('env', 'development');

        // В non-production ветке сервис только логирует.
        Log::shouldReceive('channel')->once()->with('max')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Отправляется сообщение... в MAX: 12345 : Test message');

        MaxService::sendMessage('12345', 'Test message');

        $this->assertTrue(true);
    }

    #[Test]
    public function it_uses_http_in_production()
    {
        $this->app->instance('env', 'production');

        $chatId = '12345';
        $message = 'Test message';
        $apiUrl = config('services.max.api_url');
        $testToken = 'test-token-12345';

        // Set test token for this test
        config(['services.max.token' => $testToken]);

        // MAX ждёт raw токен в заголовке Authorization (без префикса 'Bearer ').
        Http::fake(["{$apiUrl}/messages*" => Http::response([], 200)]);

        MaxService::sendMessage($chatId, $message);

        Http::assertSent(function ($request) use ($apiUrl, $testToken, $chatId, $message) {
            return $request->url() === "{$apiUrl}/messages?chat_id={$chatId}"
                && $request->hasHeader('Authorization', $testToken)
                && isset($request['text']) && $request['text'] === $message;
        });

        $this->assertTrue(true);
    }

    #[Test]
    public function it_logs_error_on_http_failure_in_production()
    {
        $this->app->instance('env', 'production');

        $chatId = '12345';
        $message = 'Test message';
        $apiUrl = config('services.max.api_url');

        Http::fake(["{$apiUrl}/messages*" => Http::response(['err' => 1], 500)]);

        Log::shouldReceive('channel')->with('max')->andReturnSelf();
        Log::shouldReceive('error')->once()->with('chat_id: '.$chatId);
        Log::shouldReceive('error')->once();

        MaxService::sendMessage($chatId, $message);

        $this->assertTrue(true);
    }
}
