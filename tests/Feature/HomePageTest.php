<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_accessible_for_authenticated_user(): void
    {
        // Arrange: создаём пользователя
        $user = User::factory()->create();

        // Act: заходим на главную страницу от имени пользователя
        $response = $this->actingAs($user)->get(route('home'));

        // Assert: проверяем успешный ответ и наличие ключевых данных
        $response->assertStatus(200);
        $response->assertViewIs('home');
        $response->assertViewHas('title', 'Дашборд');
        $response->assertViewHas('currentUserId', $user->id);
        $response->assertSee('Дашборд'); // если заголовок есть в шаблоне
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('login'));
    }
}
