<?php

// Мониторинг медленных SQL-запросов. См. AppServiceProvider::registerSlowQueryLogger().

return [

    // Главный выключатель. OFF по умолчанию — включается точечно на проде без деплоя кода.
    'enabled' => env('SLOW_QUERY_DB_LOG', false),

    // Порог длительности одного запроса в миллисекундах.
    'threshold_ms' => env('SLOW_QUERY_DB_THRESHOLD', 500),

    // Сколько bindings класть в лог (защита от огромных whereIn([...])).
    'bindings_limit' => env('SLOW_QUERY_DB_BINDINGS_LIMIT', 20),

];
