<?php

namespace Tests\Feature\Services;

use App\Jobs\SendMaxMessageJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
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
    public function it_skips_user_without_channels(): void
    {
        Bus::fake();

        $user = User::factory()->create();

        NotificationService::notify($user, 'Test message', queued: true);

        Bus::assertNothingDispatched();
    }

    #[Test]
    public function it_sends_to_both_channels_sync(): void
    {
        $user = User::factory()->create([
            'tg_id' => '111',
            'max_id' => '222',
        ]);

        Log::shouldReceive('channel')->once()->with('tg')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Отправляется сообщение... в ТГ: 111 : Hi');
        Log::shouldReceive('channel')->once()->with('max')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Отправляется сообщение... в MAX: 222 : Hi');

        NotificationService::notify($user, 'Hi', queued: false);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_sends_only_to_max_when_no_tg(): void
    {
        $user = User::factory()->create([
            'max_id' => '222',
        ]);

        Log::shouldReceive('channel')->never()->with('tg');
        Log::shouldReceive('channel')->once()->with('max')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Отправляется сообщение... в MAX: 222 : Hi');

        NotificationService::notify($user, 'Hi', queued: false);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_dispatches_both_jobs_with_delay(): void
    {
        Bus::fake([SendTelegramMessageJob::class, SendMaxMessageJob::class]);

        $user = User::factory()->create([
            'tg_id' => '111',
            'max_id' => '222',
        ]);

        NotificationService::notify($user, 'Hi', queued: true, delaySeconds: 5);

        Bus::assertDispatched(SendTelegramMessageJob::class);
        Bus::assertDispatched(SendMaxMessageJob::class);
    }

    #[Test]
    public function it_does_not_dispatch_when_queued_without_channels(): void
    {
        Bus::fake();

        $user = User::factory()->create();

        NotificationService::notify($user, 'Hi', queued: true);

        Bus::assertNothingDispatched();
    }

    #[Test]
    public function it_dispatches_both_jobs_for_admin(): void
    {
        Bus::fake([SendTelegramMessageJob::class, SendMaxMessageJob::class]);

        NotificationService::notifyAdmin('Admin message', queued: true);

        Bus::assertDispatched(SendTelegramMessageJob::class, function ($job) {
            return $job->chatId === config('telegram.admin_id');
        });
        Bus::assertDispatched(SendMaxMessageJob::class, function ($job) {
            return $job->chatId === config('services.max.admin_id');
        });
    }

    #[Test]
    public function it_dispatches_jobs_for_admin_with_delay(): void
    {
        Bus::fake([SendTelegramMessageJob::class, SendMaxMessageJob::class]);

        NotificationService::notifyAdmin('Admin message', queued: true, delaySeconds: 30);

        Bus::assertDispatched(SendTelegramMessageJob::class, function ($job) {
            return $job->delay !== null;
        });
        Bus::assertDispatched(SendMaxMessageJob::class, function ($job) {
            return $job->delay !== null;
        });
    }

    #[Test]
    public function it_sends_to_both_channels_sync_for_admin(): void
    {
        // В sync-режиме реальные сервисы вызываются напрямую.
        // Мокаем Log чтобы убедиться что логирование работает.
        Log::shouldReceive('channel')->once()->with('tg')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Отправляется сообщение... в ТГ: '.config('telegram.admin_id').' : Admin sync');
        Log::shouldReceive('channel')->once()->with('max')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Отправляется сообщение... в MAX: '.config('services.max.admin_id').' : Admin sync');

        NotificationService::notifyAdmin('Admin sync', queued: false);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_skips_tg_when_admin_id_is_empty(): void
    {
        Config::set('telegram.admin_id', null);
        Config::set('services.max.admin_id', 'max_admin_123');

        Bus::fake([SendTelegramMessageJob::class, SendMaxMessageJob::class]);

        NotificationService::notifyAdmin('Admin message', queued: true);

        Bus::assertNotDispatched(SendTelegramMessageJob::class);
        Bus::assertDispatched(SendMaxMessageJob::class, function ($job) {
            return $job->chatId === 'max_admin_123';
        });
    }
}
