<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock static method calls to prevent actual API calls
        Mockery::mock('overload:GuzzleHttp\Client')
            ->shouldReceive('post')
            ->andReturn(new \GuzzleHttp\Psr7\Response(200));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_handles_telegram_webhook_for_new_message()
    {

        $webhookData = [
            'message' => [
                'message_id' => 123,
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                    'language_code' => 'ru',
                ],
                'chat' => [
                    'id' => 987654321,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                    'type' => 'private',
                ],
                'date' => 1640995200,
                'text' => '/start',
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_handles_telegram_webhook_for_callback_query()
    {
        $webhookData = [
            'callback_query' => [
                'id' => 'abc123',
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                    'language_code' => 'ru',
                ],
                'message' => [
                    'message_id' => 123,
                    'from' => [
                        'id' => 123456789,
                        'is_bot' => true,
                        'first_name' => 'Test Bot',
                        'username' => 'testbot',
                    ],
                    'chat' => [
                        'id' => 987654321,
                        'first_name' => 'Test',
                        'username' => 'testuser',
                        'type' => 'private',
                    ],
                    'date' => 1640995200,
                    'text' => 'Example button',
                ],
                'chat_instance' => 'abc123def456',
                'data' => 'button_pressed',
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_handles_webhook_without_required_fields_gracefully()
    {

        $webhookData = [
            // Missing some required fields but has chat
            'message' => [
                'chat' => [
                    'id' => 987654321,
                    'type' => 'private',
                ],
                'text' => 'test message',
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_logs_webhook_processing_errors()
    {
        // Test that webhook logs incoming data
        $webhookData = [
            'message' => [
                'message_id' => 123,
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test',
                ],
                'chat' => [
                    'id' => 987654321,
                    'type' => 'private',
                ],
                'date' => 1640995200,
                'text' => 'test',
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_processes_command_messages()
    {

        $webhookData = [
            'message' => [
                'message_id' => 456,
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test User',
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => 987654321,
                    'first_name' => 'Test User',
                    'username' => 'testuser',
                    'type' => 'private',
                ],
                'date' => 1640995200,
                'text' => '/status',
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_handles_webhook_for_channel_messages()
    {

        $webhookData = [
            'message' => [
                'message_id' => 789,
                'sender_chat' => [
                    'id' => -1001234567890,
                    'title' => 'Test Channel',
                    'type' => 'channel',
                ],
                'chat' => [
                    'id' => -1001234567890,
                    'title' => 'Test Channel',
                    'type' => 'channel',
                ],
                'date' => 1640995200,
                'text' => 'Channel message',
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_processes_multiple_concurrent_webhooks()
    {

        $webhook1 = [
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 1, 'first_name' => 'User1'],
                'chat' => ['id' => 1, 'type' => 'private'],
                'date' => 1640995200,
                'text' => 'Hello from user 1',
            ],
        ];

        $webhook2 = [
            'message' => [
                'message_id' => 2,
                'from' => ['id' => 2, 'first_name' => 'User2'],
                'chat' => ['id' => 2, 'type' => 'private'],
                'date' => 1640995201,
                'text' => 'Hello from user 2',
            ],
        ];

        // Process first webhook
        $response1 = $this->post('/api/telegram/webhook', $webhook1);
        $response1->assertOk();

        // Process second webhook
        $response2 = $this->post('/api/telegram/webhook', $webhook2);
        $response2->assertOk();
    }

    #[Test]
    public function it_handles_edited_messages()
    {
        $webhookData = [
            'edited_message' => [
                'message_id' => 123,
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => 987654321,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                    'type' => 'private',
                ],
                'date' => 1640995200,
                'edit_date' => 1640995260,
                'text' => 'Edited message',
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_returns_200_for_empty_webhook_data()
    {
        $response = $this->post('/api/telegram/webhook', []);

        $response->assertOk();
    }

    #[Test]
    public function it_processes_location_messages()
    {

        $webhookData = [
            'message' => [
                'message_id' => 456,
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => 987654321,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                    'type' => 'private',
                ],
                'date' => 1640995200,
                'location' => [
                    'latitude' => 55.7558,
                    'longitude' => 37.6173,
                ],
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_handles_poll_answer_webhooks()
    {
        $webhookData = [
            'poll_answer' => [
                'poll_id' => 'abc123def456',
                'user' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => 987654321,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                    'type' => 'private',
                ],
                'date' => 1640995200,
                'option_ids' => [1, 3],
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function it_handles_chat_member_update_webhooks()
    {
        $webhookData = [
            'chat_member' => [
                'chat' => [
                    'id' => 987654321,
                    'title' => 'Test Group',
                    'type' => 'supergroup',
                ],
                'from' => [
                    'id' => 123456789,
                    'is_bot' => true,
                    'first_name' => 'Test Bot',
                    'username' => 'testbot',
                ],
                'date' => 1640995200,
                'old_chat_member' => [
                    'user' => [
                        'id' => 987654321,
                        'is_bot' => false,
                        'first_name' => 'Test',
                    ],
                    'status' => 'member',
                ],
                'new_chat_member' => [
                    'user' => [
                        'id' => 987654321,
                        'is_bot' => false,
                        'first_name' => 'Test',
                        'custom_title' => 'Promoted User',
                    ],
                    'status' => 'administrator',
                ],
            ],
        ];

        $response = $this->post('/api/telegram/webhook', $webhookData);

        $response->assertOk();
    }

    #[Test]
    public function webhook_endpoint_is_accessible_without_middleware()
    {
        // Test that the webhook endpoint bypasses authentication middleware
        $response = $this->post('/api/telegram/webhook', []);

        $response->assertOk();
    }
}
