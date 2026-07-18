<?php

namespace Tests\Feature\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketServiceTest extends TestCase
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
    public function create_creates_ticket_with_description_only(): void
    {
        $author = User::factory()->create();
        $data = [
            'description' => 'Test ticket description',
        ];

        $ticket = (new TicketService)->create($author, $data);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'user_id' => $author->id,
            'description' => 'Test ticket description',
            'page_url' => null,
            'screenshot' => null,
            'status' => Ticket::STATUS_NEW,
        ]);
    }

    #[Test]
    public function create_creates_ticket_with_page_url(): void
    {
        $author = User::factory()->create();
        $data = [
            'description' => 'Test ticket description',
            'page_url' => 'https://example.com/page',
        ];

        $ticket = (new TicketService)->create($author, $data);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'user_id' => $author->id,
            'description' => 'Test ticket description',
            'page_url' => 'https://example.com/page',
            'status' => Ticket::STATUS_NEW,
        ]);
    }

    #[Test]
    public function create_creates_ticket_with_screenshot(): void
    {
        Storage::fake('public');

        $author = User::factory()->create();
        $screenshot = UploadedFile::fake()->image('screenshot.jpg', 1920, 1080);
        $data = [
            'description' => 'Test ticket with screenshot',
        ];

        $ticket = (new TicketService)->create($author, $data, $screenshot);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'user_id' => $author->id,
            'description' => 'Test ticket with screenshot',
            'status' => Ticket::STATUS_NEW,
        ]);

        $this->assertNotNull($ticket->screenshot);
        $this->assertStringStartsWith('tickets/', $ticket->screenshot);
        Storage::disk('public')->assertExists($ticket->screenshot);
    }

    #[Test]
    public function create_creates_ticket_with_all_fields(): void
    {
        Storage::fake('public');

        $author = User::factory()->create();
        $screenshot = UploadedFile::fake()->image('screenshot.png');
        $data = [
            'description' => 'Full ticket',
            'page_url' => 'https://example.com/issue',
        ];

        $ticket = (new TicketService)->create($author, $data, $screenshot);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'user_id' => $author->id,
            'description' => 'Full ticket',
            'page_url' => 'https://example.com/issue',
            'status' => Ticket::STATUS_NEW,
        ]);

        $this->assertNotNull($ticket->screenshot);
        Storage::disk('public')->assertExists($ticket->screenshot);
    }

    #[Test]
    public function start_marks_new_ticket_as_in_progress(): void
    {
        $ticket = Ticket::factory()->create(['status' => Ticket::STATUS_NEW]);
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Log::spy();

        $result = (new TicketService)->start($ticket);

        $this->assertTrue($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Тикет взят в работу администратором', [
                'ticket_id' => $ticket->id,
                'admin_id' => $admin->id,
            ]);
    }

    #[Test]
    public function start_returns_false_for_in_progress_ticket(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();
        User::factory()->create();
        $this->actingAs(User::first());

        Log::spy();

        $result = (new TicketService)->start($ticket);

        $this->assertFalse($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);

        Log::shouldNotHaveReceived('info');
    }

    #[Test]
    public function start_returns_false_for_closed_ticket(): void
    {
        $ticket = Ticket::factory()->closed()->create();
        User::factory()->create();
        $this->actingAs(User::first());

        Log::spy();

        $result = (new TicketService)->start($ticket);

        $this->assertFalse($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_CLOSED,
        ]);

        Log::shouldNotHaveReceived('info');
    }

    #[Test]
    public function start_returns_false_for_deleted_ticket(): void
    {
        $ticket = Ticket::factory()->deleted()->create();
        User::factory()->create();
        $this->actingAs(User::first());

        Log::spy();

        $result = (new TicketService)->start($ticket);

        $this->assertFalse($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_DELETED,
        ]);

        Log::shouldNotHaveReceived('info');
    }

    #[Test]
    public function close_closes_in_progress_ticket_with_comment(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Log::spy();

        $result = (new TicketService)->close($ticket, 'Issue resolved');

        $this->assertTrue($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_CLOSED,
            'admin_comment' => 'Issue resolved',
        ]);

        $this->assertNotNull($ticket->fresh()->closed_at);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Тикет закрыт администратором', [
                'ticket_id' => $ticket->id,
                'admin_id' => $admin->id,
            ]);
    }

    #[Test]
    public function close_returns_false_for_new_ticket(): void
    {
        $ticket = Ticket::factory()->create(['status' => Ticket::STATUS_NEW]);
        User::factory()->create();
        $this->actingAs(User::first());

        Log::spy();

        $result = (new TicketService)->close($ticket, 'Trying to close');

        $this->assertFalse($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_NEW,
        ]);

        Log::shouldNotHaveReceived('info');
    }

    #[Test]
    public function close_returns_false_for_closed_ticket(): void
    {
        $ticket = Ticket::factory()->closed()->create();
        User::factory()->create();
        $this->actingAs(User::first());

        Log::spy();

        $result = (new TicketService)->close($ticket, 'Already closed');

        $this->assertFalse($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_CLOSED,
        ]);

        Log::shouldNotHaveReceived('info');
    }

    #[Test]
    public function close_returns_false_for_deleted_ticket(): void
    {
        $ticket = Ticket::factory()->deleted()->create();
        User::factory()->create();
        $this->actingAs(User::first());

        Log::spy();

        $result = (new TicketService)->close($ticket, 'Cannot close deleted');

        $this->assertFalse($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_DELETED,
        ]);

        Log::shouldNotHaveReceived('info');
    }

    #[Test]
    public function close_returns_false_for_empty_comment(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();
        User::factory()->create();
        $this->actingAs(User::first());

        Log::spy();

        $result = (new TicketService)->close($ticket, '');

        $this->assertFalse($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);

        Log::shouldNotHaveReceived('info');
    }

    #[Test]
    public function close_returns_false_for_whitespace_only_comment(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();
        User::factory()->create();
        $this->actingAs(User::first());

        Log::spy();

        $result = (new TicketService)->close($ticket, '   ');

        $this->assertFalse($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);

        Log::shouldNotHaveReceived('info');
    }

    #[Test]
    public function close_accepts_comment_with_text(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Log::spy();

        $result = (new TicketService)->close($ticket, '   Valid comment with spaces   ');

        $this->assertTrue($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_CLOSED,
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Тикет закрыт администратором', [
                'ticket_id' => $ticket->id,
                'admin_id' => $admin->id,
            ]);
    }

    #[Test]
    public function delete_marks_new_ticket_as_deleted(): void
    {
        $ticket = Ticket::factory()->create(['status' => Ticket::STATUS_NEW]);
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Log::spy();

        $result = (new TicketService)->delete($ticket);

        $this->assertTrue($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_DELETED,
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Тикет отправлен в корзину администратором', [
                'ticket_id' => $ticket->id,
                'admin_id' => $admin->id,
            ]);
    }

    #[Test]
    public function delete_marks_in_progress_ticket_as_deleted(): void
    {
        $ticket = Ticket::factory()->inProgress()->create();
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Log::spy();

        $result = (new TicketService)->delete($ticket);

        $this->assertTrue($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_DELETED,
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Тикет отправлен в корзину администратором', [
                'ticket_id' => $ticket->id,
                'admin_id' => $admin->id,
            ]);
    }

    #[Test]
    public function delete_marks_closed_ticket_as_deleted(): void
    {
        $ticket = Ticket::factory()->closed()->create();
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Log::spy();

        $result = (new TicketService)->delete($ticket);

        $this->assertTrue($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_DELETED,
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Тикет отправлен в корзину администратором', [
                'ticket_id' => $ticket->id,
                'admin_id' => $admin->id,
            ]);
    }

    #[Test]
    public function delete_returns_false_for_already_deleted_ticket(): void
    {
        $ticket = Ticket::factory()->deleted()->create();
        User::factory()->create();
        $this->actingAs(User::first());

        Log::spy();

        $result = (new TicketService)->delete($ticket);

        $this->assertFalse($result);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_DELETED,
        ]);

        Log::shouldNotHaveReceived('info');
    }
}
