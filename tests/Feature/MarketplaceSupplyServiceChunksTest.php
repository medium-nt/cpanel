<?php

use App\Services\MarketplaceSupplyService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Тесты для метода MarketplaceSupplyService::deleteOldChunks().
 *
 * Проверяет корректность удаления устаревших папок с частями
 * незавершённых chunked-загрузок видео.
 *
 * ВАЖНО: тест не использует БД, поэтому setUp() переопределён без вызова seed()
 * (вызываем только setUpTheTestEnvironment() для создания приложения).
 */
class MarketplaceSupplyServiceChunksTest extends TestCase
{
    protected function setUp(): void
    {
        // Вызываем только setUpTheTestEnvironment() для создания приложения
        // но НЕ вызываем parent::setUp() из проектного TestCase (чтобы избежать seed())
        $this->setUpTheTestEnvironment();
    }

    public function test_deletes_old_chunk_directories()
    {
        // Фейкаем диск local, чтобы не трогать реальные файлы в storage/app/private/
        Storage::fake('local');

        // Создаём "старую" папку с чанками
        $oldUuid = 'old-uuid-1234';
        Storage::disk('local')->put("chunks/{$oldUuid}/0.part", 'x');

        // Storage::fake() создаёт файлы во временной директории,
        // получаем реальный путь через path() метод адаптера
        $oldPath = Storage::disk('local')->path("chunks/{$oldUuid}");

        // Модифицируем mtime: делаем папку "старой" (3 дня назад)
        // touch($path, $timestamp) устанавливает время модификации файла/директории
        $oldTimestamp = now()->subDays(3)->getTimestamp();
        touch($oldPath, $oldTimestamp);

        // Проверяем что папка существует до удаления
        Storage::disk('local')->assertExists("chunks/{$oldUuid}");

        // Запускаем очистку чанков (порог = 1 день)
        MarketplaceSupplyService::deleteOldChunks(1);

        // Папка старше 1 дня → должна быть удалена
        Storage::disk('local')->assertMissing("chunks/{$oldUuid}");
    }

    public function test_keeps_fresh_chunk_directories()
    {
        // Фейкаем диск local
        Storage::fake('local');

        // Создаём "свежую" папку с чанками
        $freshUuid = 'fresh-uuid-5678';
        Storage::disk('local')->put("chunks/{$freshUuid}/0.part", 'x');

        // mtime не трогаем — папка свежая (создана только что)

        // Проверяем что папка существует
        Storage::disk('local')->assertExists("chunks/{$freshUuid}");

        // Запускаем очистку чанков (порог = 1 день)
        MarketplaceSupplyService::deleteOldChunks(1);

        // Папка свежая (сегодня создана) → должна остаться
        Storage::disk('local')->assertExists("chunks/{$freshUuid}");
    }

    public function test_deletes_chunk_directories_older_than_threshold()
    {
        Storage::fake('local');

        $boundaryUuid = 'boundary-uuid-9012';
        Storage::disk('local')->put("chunks/{$boundaryUuid}/0.part", 'x');

        $boundaryPath = Storage::disk('local')->path("chunks/{$boundaryUuid}");

        // Делаем папку СТАРШЕ 2 дней (2 дня + 1 секунда)
        // В коде используется строгое сравнение <, поэтому "ровно 2" не удалится
        $boundaryTimestamp = now()->subDays(2)->subSecond()->getTimestamp();
        touch($boundaryPath, $boundaryTimestamp);

        Storage::disk('local')->assertExists("chunks/{$boundaryUuid}");

        // Порог = 2 дня → папка возрастом старше 2 дней должна быть удалена
        MarketplaceSupplyService::deleteOldChunks(2);

        Storage::disk('local')->assertMissing("chunks/{$boundaryUuid}");
    }

    public function test_keeps_chunk_directories_exactly_at_threshold()
    {
        Storage::fake('local');

        $exactUuid = 'exact-uuid-3456';
        Storage::disk('local')->put("chunks/{$exactUuid}/0.part", 'x');

        $exactPath = Storage::disk('local')->path("chunks/{$exactUuid}");

        // Делаем папку РОВНО 2 дня назад
        // В коде используется строгое сравнение <, поэтому "ровно 2" НЕ удалится
        $exactTimestamp = now()->subDays(2)->getTimestamp();
        touch($exactPath, $exactTimestamp);

        Storage::disk('local')->assertExists("chunks/{$exactUuid}");

        // Порог = 2 дня → папка возрастом ровно 2 дня должна ОСТАТЬСЯ
        MarketplaceSupplyService::deleteOldChunks(2);

        Storage::disk('local')->assertExists("chunks/{$exactUuid}");
    }

    public function test_handles_empty_chunks_directory()
    {
        Storage::fake('local');

        // Не создаём никаких папок в chunks/

        // Не должно выбрасывать исключений
        MarketplaceSupplyService::deleteOldChunks(1);

        // Проверка успешного прохождения (без exceptions)
        $this->assertTrue(true);
    }
}
