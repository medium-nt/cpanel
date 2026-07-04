<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Таблицы, очищаемые после глобального seed().
     * Переопределить в дочерних тестах, которые проверяют счётчики БД
     * и хотят видеть только созданные ими данные (без бизнес-данных сидеров).
     *
     * @var list<string>
     */
    protected array $cleanTables = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->cleanTables();
    }

    /**
     * Очищает таблицы из $cleanTables после глобального seed().
     *
     * ВАЖНО: вызывается внутри транзакции RefreshDatabase, где PRAGMA foreign_keys
     * — это no-op (FK реально остаются включёнными). Метод безопасен ТОЛЬКО для
     * leaf/child-таблиц, на которые нет внешних ссылок (marketplace_order_items,
     * movement_materials, inventory_check_items, marketplace_orders).
     * НЕ добавлять сюда родительские таблицы (orders, materials, users) — упадёт с FK-constraint.
     */
    protected function cleanTables(): void
    {
        if ($this->cleanTables === []) {
            return;
        }

        foreach ($this->cleanTables as $table) {
            DB::table($table)->delete();
        }
    }
}
