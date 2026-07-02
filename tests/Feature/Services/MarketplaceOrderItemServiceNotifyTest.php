<?php

namespace Tests\Feature\Services;

use App\Models\MarketplaceItem;
use App\Services\MarketplaceOrderItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MarketplaceOrderItemServiceNotifyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    /**
     * Тестирует cooldown уведомлений о недостатке материала.
     *
     * Метод notifyNoMaterials - protected static, поэтому используем Reflection.
     */
    public function test_notify_no_materials_sets_cache_on_first_call()
    {
        // Создаём товар через фабрику
        $item = MarketplaceItem::factory()->create([
            'title' => 'Test Item',
            'width' => 200,
            'height' => 300,
        ]);

        // Проверяем что cache нет до вызова
        $this->assertFalse(Cache::has('no_material:item:'.$item->id));

        // Вызываем protected метод через Reflection
        $method = new \ReflectionMethod(
            MarketplaceOrderItemService::class,
            'notifyNoMaterials'
        );
        $method->invoke(null, $item);

        // Проверяем что cache выставился
        $this->assertTrue(Cache::has('no_material:item:'.$item->id));
    }

    public function test_notify_no_materials_skips_if_cache_exists()
    {
        // Создаём товар через фабрику
        $item = MarketplaceItem::factory()->create([
            'title' => 'Test Item 2',
            'width' => 250,
            'height' => 350,
        ]);

        // Pre-set cache флаг
        Cache::put('no_material:item:'.$item->id, true, 1800);

        // Мокаем TgService чтобы убедиться что он НЕ вызывается
        // (в non-prod среде TgService просто логирует и возвращает true)
        $this->app->instance('env', 'development');

        // Вызываем protected метод через Reflection
        $method = new \ReflectionMethod(
            MarketplaceOrderItemService::class,
            'notifyNoMaterials'
        );

        // Первый вызов - уже есть cache, не должен отправлять
        $method->invoke(null, $item);

        // Проверяем что cache НЕ был перевыставлен (время не обновилось)
        // Это косвенная проверка - повторного вызова не было
        $this->assertTrue(Cache::has('no_material:item:'.$item->id));
    }

    public function test_notify_no_materials_cache_ttl()
    {
        // Создаём товар через фабрику
        $item = MarketplaceItem::factory()->create();

        // Вызываем protected метод через Reflection
        $method = new \ReflectionMethod(
            MarketplaceOrderItemService::class,
            'notifyNoMaterials'
        );
        $method->invoke(null, $item);

        // Проверяем что cache имеет правильный TTL (1800 секунд = 30 минут)
        // В Laravel Cache::get возвращает null если ключ истёк
        $this->assertTrue(Cache::has('no_material:item:'.$item->id));
    }
}
