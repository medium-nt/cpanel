<?php

use App\Models\Material;
use App\Models\Role;
use App\Models\Roll;
use App\Models\TypeMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('warehouse page displays materials grouped by type', function () {
    // Создаём или находим роль админа и пользователя с правом viewAny
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create(['role_id' => $adminRole->id]);

    // Создаём или находим типы материалов в БД
    TypeMaterial::firstOrCreate(['title' => 'Тюль']);
    TypeMaterial::firstOrCreate(['title' => 'Упаковка']);

    // Создаём материалы разных типов (используем уникальные названия через factory)
    $fabric = Material::factory()->create([
        'type_id' => 1,
        'unit' => 'п.м.',
    ]);

    $packaging = Material::factory()->create([
        'type_id' => 3,
        'unit' => 'шт.',
    ]);

    // Создаём рулоны со статусом STATUS_IN_STORAGE (источник остатков на складе)
    Roll::factory()->inStorage()->create([
        'material_id' => $fabric->id,
        'initial_quantity' => 100,
    ]);

    Roll::factory()->inStorage()->create([
        'material_id' => $packaging->id,
        'initial_quantity' => 50,
    ]);

    // GET запрос на страницу склада
    $response = $this->actingAs($user)->get(route('inventory.warehouse'));

    // Проверки
    $response->assertOk();
    $response->assertSee('Тюль'); // название секции из БД
    $response->assertSee('Упаковка'); // название секции из БД
    $response->assertSee($fabric->title); // материал ткани (название из factory)
    $response->assertSee($packaging->title); // материал упаковки (название из factory)
    $response->assertSee('100 п.м.'); // количество ткани (initial_quantity рулона)
    $response->assertSee('50 шт.'); // количество упаковки (initial_quantity рулона)
});
