# SendTelegramMessageJob

**Путь:** `app/Jobs/SendTelegramMessageJob.php`

**Дата создания документации:** 2025-12-05

## Назначение задачи

Отправка сообщений в Telegram через очередь. Job предназначен для асинхронной
отправки уведомлений пользователям через Telegram API без блокировки основного
потока выполнения.

## Параметры конструктора

### `__construct(string $chatId, string $text)`

- **$chatId** (string) - ID чата Telegram, куда будет отправлено сообщение
- **$text** (string) - Текст сообщения для отправки

Параметры передаются через PHP 8 constructor property promotion и автоматически
сериализуются trait `SerializesModels`.

## Метод handle - основная логика

```php
public function handle(): void
{
    TgService::sendMessage($this->chatId, $this->text);
    Log::channel('queue')
        ->notice('Сообщение отправлено в ТГ: ' . $this->chatId . ' с текстом: ' . $this->text);
}
```

Логика выполнения:

1. Вызывает статический метод `TgService::sendMessage()` для отправки сообщения
2. Записывает в лог успешную отправку сообщения в канал `queue`

## Метод failed - обработка ошибок

```php
public function failed(\Throwable $exception): void
{
    Log::channel('queue')
        ->error("Ошибка всех попыток отправки в ТГ ({$this->chatId}): " . $exception->getMessage());
}
```

Выполняется когда все попытки выполнения Job завершились неудачно. Логирует
ошибку в канал `queue` с указанием ID чата и сообщения об ошибке.

## Промежуток попыток (retry)

- **Количество попыток:** 3 (свойство `$tries`)
- **Интервалы между попытками:** [10, 30, 60] секунд
    - 1-я попытка → ожидание 10 секунд
    - 2-я попытка → ожидание 30 секунд
    - 3-я попытка → ожидание 60 секунд

Интервалы заданы через метод `backoff()`.

## Middleware задачи

Middleware не используются.

## Очередь для выполнения

Job не привязан к конкретной очереди и будет выполняться в очереди по умолчанию.

## Особенности реализации

### Взаимодействие с Telegram API

Job использует сервис `TgService` для отправки сообщений. В зависимости от
окружения:

**Development окружение:**

- Используется Guzzle HTTP клиент напрямую
- Отключена верификация SSL сертификатов
- Таймауты: 30 секунд на соединение и выполнение

**Production окружение:**

- Используется Laravel Telegram Facade
- Ошибки логируются в канал `tg_api`

### Логирование

- Успешная отправка: лог в канал `queue` с уровнем `notice`
- Ошибки после всех попыток: лог в канал `queue` с уровнем `error`
- Ошибки TgService (в production): лог в канал `tg_api` с уровнем `error`

### Безопасность

- Токен бота хранится в конфигурации (`config('telegram.bots.mybot.token')`)
- В development окружении отключена проверка SSL (только для разработки)
- ID чата и текст сообщения логируются - учитывайте это при работе с
  конфиденциальными данными

### Зависимости

- **App\Services\TgService** - основной сервис для отправки сообщений
- **Telegram\Bot\Laravel\Facades\Telegram** - используется в production
- **GuzzleHttp\Client** - используется в development

## Пример использования

```php
// Отправка сообщения в очередь
SendTelegramMessageJob::dispatch($chatId, 'Ваш заказ обработан');

// Отправка с задержкой
SendTelegramMessageJob::dispatch($chatId, 'Напоминание о задаче')
    ->delay(now()->addMinutes(5));
```
