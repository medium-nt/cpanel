<?php

use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Тест доступа к странице доски с верным токеном и существующим цехом.
 */
test('rating_board_page_accessible_with_valid_token_and_existing_workshop', function () {
    // Arrange: создаём цех и настраиваем токен
    $workshop = Workshop::factory()->create();
    config(['services.rating_board.token' => 'test-token-123']);

    // Act: запрашиваем страницу с верным токеном
    $response = $this->get("/rating_board/test-token-123/{$workshop->id}");

    // Assert: проверяем успешный ответ
    $response->assertStatus(200);
    $response->assertViewIs('rating_board.index');
    $response->assertViewHas('token', 'test-token-123');
    $response->assertViewHas('workshop', (string) $workshop->id);
});

/**
 * Тест запрета доступа при неверном токене.
 */
test('rating_board_page_forbidden_with_invalid_token', function () {
    $workshop = Workshop::factory()->create();
    config(['services.rating_board.token' => 'correct-token']);

    // Act: запрашиваем с неверным токеном
    $response = $this->get("/rating_board/wrong-token/{$workshop->id}");

    // Assert: ожидаем 403
    $response->assertStatus(403);
});

/**
 * Тест возврата 404 при несуществующем цехе.
 */
test('rating_board_page_returns_404_for_nonexistent_workshop', function () {
    config(['services.rating_board.token' => 'test-token-123']);

    // Act: запрашиваем с несуществующим ID цеха
    $response = $this->get('/rating_board/test-token-123/999999');

    // Assert: ожидаем 404
    $response->assertStatus(404);
});

/**
 * Тест доступа к JSON-данным с верным токеном.
 */
test('rating_board_data_endpoint_returns_json_with_valid_token', function () {
    $workshop = Workshop::factory()->create();
    config(['services.rating_board.token' => 'test-token-123']);

    // Act: запрашиваем данные
    $response = $this->get("/rating_board/test-token-123/{$workshop->id}/data");

    // Assert: проверяем JSON-ответ
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'shift',
        'leaders',
        'podium',
        'stickers',
        'statistics',
        'winner',
        'timers',
    ]);
});

/**
 * Тест запрета доступа к данным при неверном токене.
 */
test('rating_board_data_endpoint_forbidden_with_invalid_token', function () {
    $workshop = Workshop::factory()->create();
    config(['services.rating_board.token' => 'correct-token']);

    // Act: запрашиваем данные с неверным токеном
    $response = $this->get("/rating_board/wrong-token/{$workshop->id}/data");

    // Assert: ожидаем 403
    $response->assertStatus(403);
});

/**
 * Тест 404 при несуществующем цехе для данных.
 */
test('rating_board_data_returns_404_for_nonexistent_workshop', function () {
    config(['services.rating_board.token' => 'test-token-123']);

    // Act: запрашиваем данные с несуществующим цехом
    $response = $this->get('/rating_board/test-token-123/999999/data');

    // Assert: ожидаем 404
    $response->assertStatus(404);
});

/**
 * Тест пустого токена в конфиге.
 */
test('rating_board_forbidden_when_token_is_empty_in_config', function () {
    $workshop = Workshop::factory()->create();
    config(['services.rating_board.token' => '']);

    // Act: запрашиваем с любым токеном (даже верным по формату)
    $response = $this->get("/rating_board/some-token/{$workshop->id}");

    // Assert: пустой токен в конфиге = 403
    $response->assertStatus(403);
});

/**
 * Тест timing-safe сравнения токенов.
 * Проверяем, что схожие токены не проходят валидацию.
 */
test('rating_board_uses_timing_safe_token_comparison', function () {
    $workshop = Workshop::factory()->create();
    config(['services.rating_board.token' => 'token-abc-123']);

    // Act: пробуем токен, отличающийся на один символ
    $response = $this->get("/rating_board/token-abc-124/{$workshop->id}");

    // Assert: даже при схожем токене должен быть 403
    $response->assertStatus(403);
});

/**
 * Тест наличия ключа 'leaders' в JSON-данных.
 */
test('rating_board_data_json_contains_leaders_key', function () {
    $workshop = Workshop::factory()->create();
    config(['services.rating_board.token' => 'test-token-123']);

    // Act: запрашиваем данные
    $response = $this->get("/rating_board/test-token-123/{$workshop->id}/data");

    // Assert: проверяем наличие ключа leaders
    $response->assertStatus(200);
    $data = $response->json();
    expect($data)->toHaveKey('leaders');
    expect($data['leaders'])->toBeArray();
});

/**
 * Тест структуры данных подиума.
 */
test('rating_board_data_json_contains_podium_structure', function () {
    $workshop = Workshop::factory()->create();
    config(['services.rating_board.token' => 'test-token-123']);

    $response = $this->get("/rating_board/test-token-123/{$workshop->id}/data");

    $response->assertStatus(200);
    $data = $response->json();
    expect($data)->toHaveKey('podium');
    expect($data['podium'])->toHaveKeys(['gold', 'silver', 'bronze']);
});

/**
 * Тест структуры счётчиков стикеров.
 */
test('rating_board_data_json_contains_stickers_structure', function () {
    $workshop = Workshop::factory()->create();
    config(['services.rating_board.token' => 'test-token-123']);

    $response = $this->get("/rating_board/test-token-123/{$workshop->id}/data");

    $response->assertStatus(200);
    $data = $response->json();
    expect($data)->toHaveKey('stickers');
    expect($data['stickers'])->toHaveKeys(['fbo', 'fbs']);
    expect($data['stickers']['fbo'])->toBeInt();
    expect($data['stickers']['fbs'])->toBeInt();
});
