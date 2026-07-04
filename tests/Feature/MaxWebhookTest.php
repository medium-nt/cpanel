<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MaxWebhookTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    protected function tearDown(): void
    {
        // Сначала откатываем транзакцию RefreshDatabase, затем чистим моки.
        parent::tearDown();
        \Mockery::close();
    }

    // В testing-окружении MaxService::sendMessage только пишет в лог (без HTTP),
    // поэтому webhook безопасно вызывает его для незарегистрированного chat_id.

    #[Test]
    public function it_handles_max_webhook_for_single_message_update()
    {
        $webhookData = [
            'message' => [
                'recipient' => [
                    'chat_id' => 339334002,
                    'chat_type' => 'dialog',
                    'user_id' => 343778150,
                ],
                'body' => [
                    'mid' => 'mid.test',
                    'seq' => 116825992528887563,
                    'text' => 'привет',
                ],
                'sender' => [
                    'user_id' => 4485140,
                    'first_name' => 'Сергей',
                    'is_bot' => false,
                ],
            ],
            'update_type' => 'message_created',
        ];

        $response = $this->post('/api/max/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_handles_max_webhook_with_updates_array()
    {
        $webhookData = [
            'updates' => [
                [
                    'message' => [
                        'recipient' => ['chat_id' => 339334002, 'chat_type' => 'dialog'],
                        'body' => ['text' => '/start'],
                    ],
                    'update_type' => 'message_created',
                ],
            ],
            'marker' => 12169,
        ];

        $response = $this->post('/api/max/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_handles_max_webhook_without_message_gracefully()
    {
        // Событие без message (например, bot_added) — пропускается без ошибок.
        $response = $this->post('/api/max/webhook', [
            'update_type' => 'bot_added',
            'chat_id' => 339334002,
        ]);

        $response->assertOk();
    }

    #[Test]
    public function it_returns_200_for_empty_webhook_data()
    {
        $response = $this->post('/api/max/webhook', []);

        $response->assertOk();
    }

    #[Test]
    public function webhook_endpoint_is_accessible_without_middleware()
    {
        $response = $this->post('/api/max/webhook', []);

        $response->assertOk();
    }

    #[Test]
    public function users_command_from_admin_returns_connected_list()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);

        User::factory()->create([
            'role_id' => $adminRole->id,
            'max_id' => '100',
            'name' => 'Иванов Иван Иванович',
        ]);
        User::factory()->create([
            'role_id' => $seamstressRole->id,
            'max_id' => '200',
            'name' => 'Петров Пётр Петрович',
        ]);

        // Контроллер логирует payload вебхука и затем результат sendMessage
        // (в testing MaxService пишет в лог). Собираем все info-сообщения.
        $captured = [];
        Log::shouldReceive('channel')->with('max')->andReturnSelf();
        Log::shouldReceive('info')->andReturnUsing(function ($message) use (&$captured) {
            $captured[] = $message;

            return true;
        });

        $response = $this->post('/api/max/webhook', [
            'message' => [
                'recipient' => ['chat_id' => 100],
                'body' => ['text' => '/users'],
            ],
            'update_type' => 'message_created',
        ]);

        $response->assertOk();

        $payload = implode("\n", $captured);
        $this->assertStringContainsString('Подключённые пользователи:', $payload);
        $this->assertStringContainsString('Иванов И.И. — Руководитель', $payload);
        $this->assertStringContainsString('Петров П.П. — Швея', $payload);
    }

    #[Test]
    public function users_command_from_non_admin_does_not_send_list()
    {
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
        User::factory()->create([
            'role_id' => $seamstressRole->id,
            'max_id' => '300',
        ]);

        // sendMessage не вызывается — в captured не должно быть строки списка.
        $captured = [];
        Log::shouldReceive('channel')->with('max')->andReturnSelf();
        Log::shouldReceive('info')->andReturnUsing(function ($message) use (&$captured) {
            $captured[] = $message;

            return true;
        });

        $response = $this->post('/api/max/webhook', [
            'message' => [
                'recipient' => ['chat_id' => 300],
                'body' => ['text' => '/users'],
            ],
            'update_type' => 'message_created',
        ]);

        $response->assertOk();

        $payload = implode("\n", $captured);
        $this->assertStringNotContainsString('Подключённые пользователи:', $payload);
    }

    #[Test]
    public function users_command_from_unregistered_user_sends_auth_link_instead_of_list()
    {
        // Незарегистрированный chat_id — даже при /users должна идти ссылка
        // на авторизацию, а не список пользователей.
        $captured = [];
        Log::shouldReceive('channel')->with('max')->andReturnSelf();
        Log::shouldReceive('info')->andReturnUsing(function ($message) use (&$captured) {
            $captured[] = $message;

            return true;
        });

        $response = $this->post('/api/max/webhook', [
            'message' => [
                'recipient' => ['chat_id' => 999999],
                'body' => ['text' => '/users'],
            ],
            'update_type' => 'message_created',
        ]);

        $response->assertOk();

        $payload = implode("\n", $captured);
        $this->assertStringContainsString('авторизоваться', $payload);
        $this->assertStringNotContainsString('Подключённые пользователи:', $payload);
    }

    #[Test]
    public function users_command_with_only_admin_connected_sends_list_with_admin()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        User::factory()->create([
            'role_id' => $adminRole->id,
            'max_id' => '100',
            'name' => 'Админов Админ Админович',
        ]);

        $captured = [];
        Log::shouldReceive('channel')->with('max')->andReturnSelf();
        Log::shouldReceive('info')->andReturnUsing(function ($message) use (&$captured) {
            $captured[] = $message;

            return true;
        });

        $response = $this->post('/api/max/webhook', [
            'message' => [
                'recipient' => ['chat_id' => 100],
                'body' => ['text' => '/users'],
            ],
            'update_type' => 'message_created',
        ]);

        $response->assertOk();

        $payload = implode("\n", $captured);
        $this->assertStringContainsString('Подключённые пользователи:', $payload);
        $this->assertStringContainsString('Админов А.А.', $payload);
    }
}
