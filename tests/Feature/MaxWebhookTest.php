<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MaxWebhookTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

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
}
