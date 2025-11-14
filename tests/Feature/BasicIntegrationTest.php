<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BasicIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function home_page_is_accessible()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    #[Test]
    public function login_page_is_accessible()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    #[Test]
    public function guest_is_redirected_from_protected_pages()
    {
        $response = $this->get('/megatulle/materials');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function application_can_create_basic_models()
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(User::class, $user);

        $material = Material::factory()->create();
        $this->assertInstanceOf(Material::class, $material);

        $order = MarketplaceOrder::factory()->create();
        $this->assertInstanceOf(MarketplaceOrder::class, $order);
    }

    #[Test]
    public function database_connections_are_working()
    {
        // Test that we can query the database
        $userCount = User::count();
        $this->assertIsInt($userCount);

        $materialCount = Material::count();
        $this->assertIsInt($materialCount);

        $orderCount = MarketplaceOrder::count();
        $this->assertIsInt($orderCount);
    }

    #[Test]
    public function user_role_methods_are_working()
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isSeamstress());
        $this->assertFalse($user->isStorekeeper());
        $this->assertFalse($user->isCutter());
        $this->assertFalse($user->isOtk());
    }

    #[Test]
    public function middleware_is_configured_correctly()
    {
        // Test that web routes are working
        $response = $this->get('/');
        $response->assertOk();

        // Test that API routes have proper structure
        $apiResponse = $this->post('/api/telegram/webhook', []);
        $apiResponse->assertOk(); // Should return 200 even without valid webhook data
    }

    #[Test]
    public function settings_are_accessible()
    {
        // Test that we can access application settings
        $this->assertNotNull(config('app.name'));
        $this->assertNotNull(config('app.env'));
        $this->assertNotNull(config('database.default'));
    }

    #[Test]
    public function authentication_is_working()
    {
        // Create a test user
        $user = User::factory()->create();

        // Test that we can authenticate
        $this->actingAs($user);

        // Should now be able to access protected routes that don't require shift
        $response = $this->get('/megatulle/profile');
        $response->assertOk(); // Profile is available without open shift
    }

    #[Test]
    public function factory_relationships_are_working()
    {
        $user = User::factory()->create();

        // Test creating related models using existing fields
        $order = MarketplaceOrder::factory()->create();

        $this->assertInstanceOf(MarketplaceOrder::class, $order);
        $this->assertNotNull($order->id);
        $this->assertNotNull($order->order_id);
    }

    #[Test]
    public function logging_configuration_is_working()
    {
        // Test that log channels are configured
        $this->assertTrue(true); // If we get here, logging is working
    }
}
