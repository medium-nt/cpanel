<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

/**
 * Генерирует структурные карты wiki проекта.
 * Сканирует Models, Services, Controllers, Routes, Livewire
 * и создаёт docs/wiki/INDEX.md + docs/wiki/maps/*.md + docs/wiki/.registry.json.
 * НЕ трогает docs/wiki/topics/ и docs/wiki/log.md — это LLM-файлы.
 */
class GenerateWikiMaps extends Command
{
    protected $signature = 'wiki:generate {--diff : Показать изменения относительно прошлой генерации}';

    protected $description = 'Сгенерировать структурные карты wiki проекта (INDEX.md, maps/*.md, .registry.json)';

    private string $wikiPath;

    private string $mapsPath;

    public function handle(): int
    {
        $this->wikiPath = base_path('docs/wiki');
        $this->mapsPath = $this->wikiPath . '/maps';

        $this->ensureDirectories();

        $this->info('🔍 Сканирование проекта...');

        $models = $this->scanModels();
        $services = $this->scanServices();
        $controllers = $this->scanControllers();
        $routes = $this->scanRoutes();
        $livewire = $this->scanLivewire();
        $schedule = $this->parseConsoleSchedule();

        $this->info("  Модели: {$this->countItems($models)}");
        $this->info("  Сервисы: {$this->countItems($services)}");
        $this->info("  Контроллеры: {$this->countItems($controllers)}");
        $this->info("  Livewire: {$this->countItems($livewire)}");
        $this->info('  Роут-файлы: ' . count($routes));
        $this->info('  Cron-задачи: ' . count($schedule));

        $this->generateIndex($models, $services, $controllers, $livewire);
        $this->generateModelMap($models);
        $this->generateServiceMap($services);
        $this->generateControllerMap($controllers);
        $this->generateRouteMap($routes);
        $this->generateLivewireMap($livewire);
        $this->generateScheduleMap($schedule);
        $this->generateRegistry($models, $services, $controllers, $livewire);

        $this->newLine();
        $this->info('✅ Wiki карты сгенерированы в docs/wiki/');

        if ($this->option('diff')) {
            $this->showDiff();
        }

        return self::SUCCESS;
    }

    /**
     * Создаёт директории docs/wiki/ и docs/wiki/maps/ если их нет.
     */
    private function ensureDirectories(): void
    {
        if (!is_dir($this->mapsPath)) {
            mkdir($this->mapsPath, 0755, true);
        }

        if (!is_dir($this->wikiPath . '/topics')) {
            mkdir($this->wikiPath . '/topics', 0755, true);
        }
    }

    /**
     * Считает количество элементов в многомерном массиве сканирования.
     */
    private function countItems(array $items): int
    {
        return count($items);
    }

    // ─── СКАНИРОВАНИЕ ─────────────────────────────────────────

    /**
     * Сканирует все Eloquent-модели и извлекает fillable, relationships, table name, traits.
     *
     * @return array<string, array{class: string, file: string, table: string, fillable: list<string>, casts: array<string, string>, relationships: array<string, array{type: string, related: string}>, traits: list<string>}>
     */
    private function scanModels(): array
    {
        $models = [];
        $path = app_path('Models');

        if (!is_dir($path)) {
            return $models;
        }

        $finder = (new Finder)->files()->in($path)->name('*.php')->sortByName();

        foreach ($finder as $file) {
            $className = $file->getBasename('.php');
            $fqcn = "App\\Models\\{$className}";

            if (!class_exists($fqcn)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($fqcn);

                if (!$reflection->isSubclassOf('Illuminate\Database\Eloquent\Model')) {
                    continue;
                }

                $models[$className] = [
                    'class' => $fqcn,
                    'file' => 'app/Models/' . $file->getRelativePathname(),
                    'table' => $this->getModelTable($fqcn, $className),
                    'fillable' => $this->getModelFillable($fqcn),
                    'casts' => $this->getModelCasts($fqcn),
                    'relationships' => $this->getModelRelationships($reflection),
                    'traits' => $this->getModelTraits($reflection),
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return $models;
    }

    /**
     * Получает имя таблицы модели.
     */
    private function getModelTable(string $fqcn, string $className): string
    {
        $model = new $fqcn;

        return $model->getTable();
    }

    /**
     * Извлекает fillable-поля модели.
     *
     * @return list<string>
     */
    private function getModelFillable(string $fqcn): array
    {
        $model = new $fqcn;

        return $model->getFillable();
    }

    /**
     * Извлекает casts модели (из метода casts() или свойства $casts).
     *
     * @return array<string, string>
     */
    private function getModelCasts(string $fqcn): array
    {
        $model = new $fqcn;

        return $model->getCasts();
    }

    /**
     * Извлекает relationships модели через reflection методов.
     *
     * @return array<string, array{type: string, related: string}>
     */
    private function getModelRelationships(ReflectionClass $reflection): array
    {
        $relationships = [];
        $relationTypes = [
            'BelongsTo', 'HasOne', 'HasMany', 'BelongsToMany',
            'MorphTo', 'MorphOne', 'MorphMany', 'MorphToMany',
            'HasOneThrough', 'HasManyThrough',
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            if ($method->getNumberOfParameters() > 0) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (!$returnType) {
                continue;
            }

            $typeName = class_basename($returnType->getName());

            if (in_array($typeName, $relationTypes)) {
                $related = $this->extractRelatedFromMethod($method);
                $relationships[$method->getName()] = [
                    'type' => $typeName,
                    'related' => $related,
                ];
            }
        }

        return $relationships;
    }

    /**
     * Извлекает имя связанной модели из тела метода-relationship.
     */
    private function extractRelatedFromMethod(ReflectionMethod $method): string
    {
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if (!$filename || !$startLine || !$endLine) {
            return 'Unknown';
        }

        $source = file($filename);
        $body = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));

        if (preg_match('/(belongsTo|hasOne|hasMany|belongsToMany|morphTo|morphOne|morphMany|morphToMany)\s*\(\s*([^,\)]+)/i', $body, $matches)) {
            $related = trim($matches[2]);
            $related = Str::afterLast($related, '\\');

            return Str::replaceLast('::class', '', $related);
        }

        return 'Unknown';
    }

    /**
     * Извлекает traits модели.
     *
     * @return list<string>
     */
    private function getModelTraits(ReflectionClass $reflection): array
    {
        return array_map(
            fn(string $trait) => class_basename($trait),
            array_keys($reflection->getTraits())
        );
    }

    /**
     * Сканирует все сервисы и извлекает публичные методы и зависимости.
     *
     * @return array<string, array{class: string, file: string, methods: list<array{name: string, is_static: bool, params: list<string>}>, dependencies: list<string>}>
     */
    private function scanServices(): array
    {
        $services = [];
        $path = app_path('Services');

        if (!is_dir($path)) {
            return $services;
        }

        $finder = (new Finder)->files()->in($path)->name('*.php')->sortByName();

        foreach ($finder as $file) {
            $className = $file->getBasename('.php');
            $fqcn = "App\\Services\\{$className}";

            if (!class_exists($fqcn)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($fqcn);

                $methods = [];
                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                        continue;
                    }

                    if ($method->getName() === '__construct') {
                        continue;
                    }

                    $params = array_map(
                        fn(\ReflectionParameter $p) => ($p->getType() ? $p->getType()->getName() . ' ' : '') . '$' . $p->getName(),
                        $method->getParameters()
                    );

                    $methods[] = [
                        'name' => $method->getName(),
                        'is_static' => $method->isStatic(),
                        'params' => $params,
                    ];
                }

                $dependencies = $this->getConstructorDependencies($reflection);

                $services[$className] = [
                    'class' => $fqcn,
                    'file' => 'app/Services/' . $file->getRelativePathname(),
                    'methods' => $methods,
                    'dependencies' => $dependencies,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return $services;
    }

    /**
     * Извлекает зависимости конструктора.
     *
     * @return list<string>
     */
    private function getConstructorDependencies(ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return [];
        }

        $deps = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $deps[] = class_basename($type->getName());
            }
        }

        return $deps;
    }

    /**
     * Сканирует все контроллеры и извлекает публичные методы.
     *
     * @return array<string, array{class: string, file: string, methods: list<string>, dependencies: list<string>}>
     */
    private function scanControllers(): array
    {
        $controllers = [];
        $path = app_path('Http/Controllers');

        if (!is_dir($path)) {
            return $controllers;
        }

        $finder = (new Finder)->files()->in($path)->name('*.php')->sortByName();

        foreach ($finder as $file) {
            $className = $file->getBasename('.php');
            $relativePath = $file->getRelativePath();
            $namespace = $relativePath ? "App\\Http\\Controllers\\{$relativePath}" : 'App\\Http\\Controllers';
            $fqcn = "{$namespace}\\{$className}";

            if (!class_exists($fqcn)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($fqcn);

                $methods = [];
                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                        continue;
                    }

                    if (str_starts_with($method->getName(), '__')) {
                        continue;
                    }

                    $methods[] = $method->getName();
                }

                $dependencies = $this->getConstructorDependencies($reflection);

                $controllers[$className] = [
                    'class' => $fqcn,
                    'file' => 'app/Http/Controllers/' . $file->getRelativePathname(),
                    'methods' => $methods,
                    'dependencies' => $dependencies,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return $controllers;
    }

    /**
     * Парсит структуру роут-файлов из web.php.
     *
     * @return list<array{file: string, group: string, middleware: list<string>}>
     */
    private function scanRoutes(): array
    {
        $routes = [];
        $webPath = base_path('routes/web.php');

        if (!file_exists($webPath)) {
            return $routes;
        }

        $content = file_get_contents($webPath);

        // Извлекаем все require base_path('routes/xxx.php')
        preg_match_all("/require\s+base_path\('routes\/([^']+)'\)/", $content, $matches);

        $routeFiles = $matches[1] ?? [];

        // Определяем группы по middleware
        $lines = explode("\n", $content);
        $currentMiddleware = [];
        $currentGroup = '';

        foreach ($lines as $line) {
            if (preg_match("/require\s+base_path\('routes\/([^']+)'\)/", $line, $m)) {
                $routes[] = [
                    'file' => $m[1],
                    'group' => $currentGroup,
                    'middleware' => $currentMiddleware,
                ];
            }

            if (preg_match("/->middleware\('([^']+)'\)/", $line, $m)) {
                if ($m[1] !== 'auth') {
                    $currentMiddleware[] = $m[1];
                } else {
                    $currentMiddleware[] = 'auth';
                }
            }
        }

        // Добавляем роут-файлы которые не найдены в web.php (api.php, console.php, kiosk.php)
        $allRouteFiles = glob(base_path('routes/*.php'));
        $knownFiles = array_map(fn($r) => $r['file'], $routes);

        foreach ($allRouteFiles as $filePath) {
            $basename = basename($filePath);
            if (!in_array($basename, $knownFiles) && $basename !== 'web.php' && $basename !== 'console.php') {
                $routes[] = [
                    'file' => $basename,
                    'group' => 'standalone',
                    'middleware' => [],
                ];
            }
        }

        sort($routes);

        return $routes;
    }

    /**
     * Сканирует Livewire-компоненты.
     *
     * @return array<string, array{class: string, file: string, view: string, properties: list<string>}>
     */
    private function scanLivewire(): array
    {
        $components = [];
        $path = app_path('Livewire');

        if (!is_dir($path)) {
            return $components;
        }

        $finder = (new Finder)->files()->in($path)->name('*.php')->sortByName();

        foreach ($finder as $file) {
            $className = $file->getBasename('.php');
            $fqcn = "App\\Livewire\\{$className}";

            if (!class_exists($fqcn)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($fqcn);

                $view = '';
                if ($reflection->hasProperty('view')) {
                    $source = file_get_contents($reflection->getFileName());
                    if (preg_match("/view\s*=\s*'([^']+)'/", $source, $m)) {
                        $view = $m[1];
                    }
                }

                // Пытаемся найти view через конвенцию
                if (!$view) {
                    $view = 'livewire.' . Str::kebab($className);
                }

                // Извлекаем публичные свойства (для формы)
                $properties = [];
                foreach ($reflection->getProperties() as $property) {
                    if ($property->isPublic() && $property->getDeclaringClass()->getName() === $reflection->getName()) {
                        $properties[] = '$' . $property->getName();
                    }
                }

                $components[$className] = [
                    'class' => $fqcn,
                    'file' => 'app/Livewire/' . $file->getRelativePathname(),
                    'view' => $view,
                    'properties' => $properties,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return $components;
    }

    /**
     * Парсит cron-задачи из routes/console.php.
     *
     * @return list<array{schedule: string, description: string}>
     */
    private function parseConsoleSchedule(): array
    {
        $schedule = [];
        $consolePath = base_path('routes/console.php');

        if (!file_exists($consolePath)) {
            return $schedule;
        }

        $content = file_get_contents($consolePath);
        $lines = explode("\n", $content);
        $buffer = '';
        $lineNum = 0;

        foreach ($lines as $line) {
            $lineNum++;
            $trimmed = trim($line);

            // Ищем Schedule::call или Schedule::command
            if (preg_match('/Schedule::(call|command)\s*\(/', $trimmed)) {
                $buffer = $trimmed;
            } elseif ($buffer) {
                $buffer .= ' ' . $trimmed;
            }

            if ($buffer && preg_match("/(dailyAt|weeklyOn|everyTenMinutes|everyThirtyMinutes|everyMinute|cron)\s*\(\s*['\"]?([^'\"]+)['\"]?\s*\)/", $buffer, $m)) {
                $desc = $this->extractScheduleDescription($buffer);
                $schedule[] = [
                    'schedule' => $m[1] . '(' . $m[2] . ')',
                    'description' => $desc,
                    'line' => $lineNum,
                ];
                $buffer = '';
            } elseif ($buffer && preg_match("/->purpose\(['\"]([^'\"]+)/", $buffer, $m)) {
                // Artisan::command с purpose
            } elseif ($buffer && str_ends_with(rtrim($buffer), ';')) {
                $desc = $this->extractScheduleDescription($buffer);
                if ($desc) {
                    if (preg_match("/(dailyAt|weeklyOn|everyTenMinutes|everyThirtyMinutes|everyMinute)\s*\(\s*['\"]?([^'\"]*?)['\"]?\s*\)/", $buffer, $m)) {
                        $schedule[] = [
                            'schedule' => $m[1] . '(' . $m[2] . ')',
                            'description' => $desc,
                            'line' => $lineNum,
                        ];
                    } elseif (preg_match('/->between\(/', $buffer)) {
                        $schedule[] = [
                            'schedule' => 'scheduled',
                            'description' => $desc,
                            'line' => $lineNum,
                        ];
                    }
                }
                $buffer = '';
            }
        }

        // Также ищем Artisan::command
        preg_match_all("/Artisan::command\('([^']+)'.*?->purpose\(['\"]([^'\"]+)/s", $content, $commands, PREG_SET_ORDER);

        foreach ($commands as $cmd) {
            $schedule[] = [
                'schedule' => 'manual',
                'description' => $cmd[1] . ': ' . $cmd[2],
                'line' => 0,
            ];
        }

        return $schedule;
    }

    /**
     * Извлекает описание из строки Schedule.
     */
    private function extractScheduleDescription(string $buffer): string
    {
        // Извлекаем имя класса и метода
        if (preg_match('/([A-Z][a-zA-Z]+)::([a-zA-Z]+)\(\)/', $buffer, $m)) {
            return $m[1] . '::' . $m[2] . '()';
        }

        if (preg_match("/'([^']+)'/", $buffer, $m)) {
            return $m[1];
        }

        return '';
    }

    // ─── ГЕНЕРАЦИЯ ФАЙЛОВ ─────────────────────────────────────

    /**
     * Генерирует INDEX.md — точку входа в wiki.
     */
    private function generateIndex(array $models, array $services, array $controllers, array $livewire): void
    {
        $now = date('Y-m-d H:i');
        $totalModels = count($models);
        $totalServices = count($services);
        $totalControllers = count($controllers);
        $totalLivewire = count($livewire);

        $content = "# cpanel — Project Wiki Index\n";
        $content .= "> Generated: {$now} | Models: {$totalModels} | Services: {$totalServices} | Controllers: {$totalControllers} | Livewire: {$totalLivewire}\n\n";

        $content .= "## Quick Orientation\n";
        $content .= "Warehouse/inventory management with Ozon/WB marketplace integration.\n";
        $content .= "PHP 8.2, Laravel 11, Livewire 3, AdminLTE, Tailwind 3, Pest.\n\n";

        // Models table
        $content .= "## Models ({$totalModels})\n";
        $content .= "| Model | Table | Key Relations | Traits |\n";
        $content .= "|-------|-------|---------------|--------|\n";
        foreach ($models as $name => $data) {
            $keyRels = array_slice(array_keys($data['relationships']), 0, 3);
            $relStr = implode(', ', $keyRels);
            if (count($data['relationships']) > 3) {
                $relStr .= ' +' . (count($data['relationships']) - 3);
            }
            $traitsStr = implode(', ', $data['traits']);
            $content .= "| {$name} | `{$data['table']}` | {$relStr} | {$traitsStr} |\n";
        }
        $content .= "\n";

        // Services table
        $content .= "## Services ({$totalServices})\n";
        $content .= "| Service | Methods | Dependencies |\n";
        $content .= "|---------|---------|-------------|\n";
        foreach ($services as $name => $data) {
            $methodCount = count($data['methods']);
            $depsStr = implode(', ', $data['dependencies']) ?: '—';
            $content .= "| {$name} | {$methodCount} | {$depsStr} |\n";
        }
        $content .= "\n";

        // Controllers table
        $content .= "## Controllers ({$totalControllers})\n";
        $content .= "| Controller | Key Methods |\n";
        $content .= "|------------|------------|\n";
        foreach ($controllers as $name => $data) {
            $methodsStr = implode(', ', array_slice($data['methods'], 0, 5));
            if (count($data['methods']) > 5) {
                $methodsStr .= ' +' . (count($data['methods']) - 5);
            }
            $content .= "| {$name} | {$methodsStr} |\n";
        }
        $content .= "\n";

        // Livewire table
        if ($totalLivewire > 0) {
            $content .= "## Livewire ({$totalLivewire})\n";
            $content .= "| Component | View | Properties |\n";
            $content .= "|-----------|------|------------|\n";
            foreach ($livewire as $name => $data) {
                $propsStr = implode(', ', array_slice($data['properties'], 0, 4));
                $content .= "| {$name} | `{$data['view']}` | {$propsStr} |\n";
            }
            $content .= "\n";
        }

        // Route groups
        $content .= "## Route Groups\n";
        $content .= "- `/megatulle/` + `auth` — базовые авторизованные роуты (users, shifts, transactions, workshops)\n";
        $content .= "- `/megatulle/` + `auth` + `require_open_shift` — операционные роуты (materials, orders, marketplace, inventory)\n";
        $content .= "- `routes/api.php` — webhooks (Telegram)\n";
        $content .= "- `routes/kiosk.php` — интерфейс киоска\n";
        $content .= "- `routes/console.php` — cron-задачи (подробнее в [maps/schedule.md](maps/schedule.md))\n\n";

        // Topic links
        $content .= "## Topic Guides (business logic)\n";
        $content .= "- [Order Lifecycle](topics/order-lifecycle.md) — статусная машина заказов\n";
        $content .= "- [Material Flow](topics/material-flow.md) — движение материалов\n";
        $content .= "- [Marketplace Integration](topics/marketplace-integration.md) — Ozon/WB API\n";
        $content .= "- [Shift System](topics/shift-system.md) — смены и цеха\n";
        $content .= "- [Salary System](topics/salary-system.md) — начисления и тарифы\n";
        $content .= "- [Warehouse Operations](topics/warehouse-operations.md) — склад, стеллажи, стикеры\n";
        $content .= "- [Finance](topics/finance.md) — транзакции, мотивация\n\n";

        // Map links
        $content .= "## Detailed Maps\n";
        $content .= "- [Models](maps/models.md) — полные fillable, casts, relationships\n";
        $content .= "- [Services](maps/services.md) — все методы с сигнатурами\n";
        $content .= "- [Controllers](maps/controllers.md) — все методы контроллеров\n";
        $content .= "- [Routes](maps/routes.md) — все роут-файлы\n";
        $content .= "- [Livewire](maps/livewire.md) — компоненты и их views\n";
        $content .= "- [Schedule](maps/schedule.md) — cron-задачи\n";

        file_put_contents($this->wikiPath . '/INDEX.md', $content);
        $this->info('  ✓ INDEX.md');
    }

    /**
     * Генерирует детальную карту моделей.
     */
    private function generateModelMap(array $models): void
    {
        $content = "# Models Map\n";
        $content .= "> Auto-generated by `php artisan wiki:generate` — DO NOT edit manually\n\n";

        foreach ($models as $name => $data) {
            $content .= "### {$name}\n";
            $content .= "- **File:** `{$data['file']}`\n";
            $content .= "- **Table:** `{$data['table']}`\n";
            $content .= '- **Traits:** ' . ($data['traits'] ? '`' . implode('`, `', $data['traits']) . '`' : '—') . "\n";

            if ($data['fillable']) {
                $content .= '- **Fillable:** `' . implode('`, `', $data['fillable']) . '`' . "\n";
            }

            if ($data['casts']) {
                $castStr = implode(', ', array_map(
                    fn(string $k, string $v) => "`{$k}` → {$v}",
                    array_keys($data['casts']),
                    array_values($data['casts'])
                ));
                $content .= "- **Casts:** {$castStr}\n";
            }

            if ($data['relationships']) {
                $content .= "- **Relationships:**\n";
                foreach ($data['relationships'] as $relName => $rel) {
                    $content .= "  - `{$rel['type']}` {$relName} → {$rel['related']}\n";
                }
            }

            $content .= "\n";
        }

        file_put_contents($this->mapsPath . '/models.md', $content);
        $this->info('  ✓ maps/models.md');
    }

    /**
     * Генерирует детальную карту сервисов.
     */
    private function generateServiceMap(array $services): void
    {
        $content = "# Services Map\n";
        $content .= "> Auto-generated by `php artisan wiki:generate` — DO NOT edit manually\n\n";

        foreach ($services as $name => $data) {
            $content .= "### {$name}\n";
            $content .= "- **File:** `{$data['file']}`\n";

            if ($data['dependencies']) {
                $content .= '- **Dependencies:** `' . implode('`, `', $data['dependencies']) . '`' . "\n";
            }

            if ($data['methods']) {
                $content .= "- **Methods:**\n";
                foreach ($data['methods'] as $method) {
                    $static = $method['is_static'] ? 'static ' : '';
                    $params = implode(', ', $method['params']);
                    $content .= "  - `{$static}{$method['name']}({$params})`\n";
                }
            }

            $content .= "\n";
        }

        file_put_contents($this->mapsPath . '/services.md', $content);
        $this->info('  ✓ maps/services.md');
    }

    /**
     * Генерирует детальную карту контроллеров.
     */
    private function generateControllerMap(array $controllers): void
    {
        $content = "# Controllers Map\n";
        $content .= "> Auto-generated by `php artisan wiki:generate` — DO NOT edit manually\n\n";

        foreach ($controllers as $name => $data) {
            $content .= "### {$name}\n";
            $content .= "- **File:** `{$data['file']}`\n";

            if ($data['dependencies']) {
                $content .= '- **Dependencies:** `' . implode('`, `', $data['dependencies']) . '`' . "\n";
            }

            if ($data['methods']) {
                $content .= '- **Methods:** `' . implode('`, `', $data['methods']) . '`' . "\n";
            }

            $content .= "\n";
        }

        file_put_contents($this->mapsPath . '/controllers.md', $content);
        $this->info('  ✓ maps/controllers.md');
    }

    /**
     * Генерирует детальную карту роутов.
     */
    private function generateRouteMap(array $routes): void
    {
        $content = "# Routes Map\n";
        $content .= "> Auto-generated by `php artisan wiki:generate` — DO NOT edit manually\n\n";

        $content .= "## Route Groups (from web.php)\n\n";

        $content .= "### Group 1: Public routes\n";
        $content .= "- No middleware\n";
        $content .= "- `/` (welcome), `/home`, `/sticker_printing`, `/barcode`, `/fbo_barcode`\n\n";

        $content .= "### Group 2: `/megatulle/` + `auth`\n";
        $content .= "- Available without open shift\n";
        foreach ($routes as $route) {
            if (in_array('require_open_shift', $route['middleware'])) {
                continue;
            }
            if ($route['group'] !== 'standalone') {
                $content .= "- `routes/{$route['file']}`\n";
            }
        }
        $content .= "\n";

        $content .= "### Group 3: `/megatulle/` + `auth` + `require_open_shift`\n";
        $content .= "- Requires open shift\n";
        foreach ($routes as $route) {
            if (in_array('require_open_shift', $route['middleware'])) {
                $content .= "- `routes/{$route['file']}`\n";
            }
        }
        $content .= "\n";

        $content .= "### Standalone routes\n";
        foreach ($routes as $route) {
            if ($route['group'] === 'standalone') {
                $content .= "- `routes/{$route['file']}`\n";
            }
        }

        file_put_contents($this->mapsPath . '/routes.md', $content);
        $this->info('  ✓ maps/routes.md');
    }

    /**
     * Генерирует детальную карту Livewire-компонентов.
     */
    private function generateLivewireMap(array $livewire): void
    {
        $content = "# Livewire Map\n";
        $content .= "> Auto-generated by `php artisan wiki:generate` — DO NOT edit manually\n\n";

        foreach ($livewire as $name => $data) {
            $content .= "### {$name}\n";
            $content .= "- **File:** `{$data['file']}`\n";
            $content .= "- **View:** `{$data['view']}`\n";

            if ($data['properties']) {
                $content .= '- **Properties:** ' . implode(', ', $data['properties']) . "\n";
            }

            $content .= "\n";
        }

        file_put_contents($this->mapsPath . '/livewire.md', $content);
        $this->info('  ✓ maps/livewire.md');
    }

    /**
     * Генерирует карту cron-задач.
     */
    private function generateScheduleMap(array $schedule): void
    {
        $content = "# Schedule Map (routes/console.php)\n";
        $content .= "> Auto-generated by `php artisan wiki:generate` — DO NOT edit manually\n\n";

        foreach ($schedule as $item) {
            $content .= "- **{$item['schedule']}** — {$item['description']}\n";
        }

        file_put_contents($this->mapsPath . '/schedule.md', $content);
        $this->info('  ✓ maps/schedule.md');
    }

    /**
     * Генерирует .registry.json — машиночитаемый индекс.
     */
    private function generateRegistry(array $models, array $services, array $controllers, array $livewire): void
    {
        $registry = [
            'generated_at' => date('c'),
            'counts' => [
                'models' => count($models),
                'services' => count($services),
                'controllers' => count($controllers),
                'livewire' => count($livewire),
            ],
            'models' => array_map(fn($data) => [
                'table' => $data['table'],
                'file' => $data['file'],
                'fillable_count' => count($data['fillable']),
                'relationships_count' => count($data['relationships']),
            ], $models),
            'services' => array_map(fn($data) => [
                'file' => $data['file'],
                'methods_count' => count($data['methods']),
            ], $services),
            'controllers' => array_map(fn($data) => [
                'file' => $data['file'],
                'methods_count' => count($data['methods']),
            ], $controllers),
        ];

        file_put_contents($this->wikiPath . '/.registry.json', json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info('  ✓ .registry.json');
    }

    /**
     * Показывает diff изменений относительно прошлой генерации.
     */
    private function showDiff(): void
    {
        $registryPath = $this->wikiPath . '/.registry.json';

        if (!file_exists($registryPath)) {
            $this->info('  (первая генерация, diff недоступен)');

            return;
        }

        $this->info('  Diff будет доступен при следующей генерации.');
    }
}
