<?php

namespace Tests\Feature\Services;

use App\Services\MaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    #[Test]
    public function it_returns_false_when_chat_suspended_via_cache()
    {
        $chatId = '12345';

        // Pre-set banned cache flag
        Cache::put('max:banned:'.$chatId, true, 21600);

        $result = MaxService::sendMessage($chatId, 'Test message');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_when_rate_limited_via_cache()
    {
        $chatId = '12345';

        // Pre-set rate limited cache flag
        Cache::put('max:rate_limited:'.$chatId, true, 1800);

        $result = MaxService::sendMessage($chatId, 'Test message');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_sets_rate_limit_cache_on_429_response()
    {
        $this->app->instance('env', 'production');

        $chatId = '12345';
        $message = 'Test message';
        $apiUrl = config('services.max.api_url');

        Http::fake([
            "{$apiUrl}/messages*" => Http::response([
                'code' => 'too.many.requests',
            ], 429),
        ]);

        $result = MaxService::sendMessage($chatId, $message);

        $this->assertFalse($result);
        $this->assertTrue(Cache::has('max:rate_limited:'.$chatId));
    }

    #[Test]
    public function it_sets_banned_cache_on_403_suspended_response()
    {
        $this->app->instance('env', 'production');

        $chatId = '12345';
        $message = 'Test message';
        $apiUrl = config('services.max.api_url');

        Http::fake([
            "{$apiUrl}/messages*" => Http::response([
                'code' => 'chat.denied',
                'message' => 'dialog.suspended: user blocked or chat not found',
            ], 403),
        ]);

        $result = MaxService::sendMessage($chatId, $message);

        $this->assertFalse($result);
        $this->assertTrue(Cache::has('max:banned:'.$chatId));
    }

    #[Test]
    public function it_does_not_set_cache_on_500_error()
    {
        $this->app->instance('env', 'production');

        $chatId = '12345';
        $message = 'Test message';
        $apiUrl = config('services.max.api_url');

        Http::fake([
            "{$apiUrl}/messages*" => Http::response(['error' => 'internal'], 500),
        ]);

        $result = MaxService::sendMessage($chatId, $message);

        $this->assertFalse($result);
        $this->assertFalse(Cache::has('max:rate_limited:'.$chatId));
        $this->assertFalse(Cache::has('max:banned'.$chatId));
    }
}
