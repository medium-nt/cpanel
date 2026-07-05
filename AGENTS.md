<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for
this application. These guidelines should be followed closely to ensure the best
experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems
package & versions are below. You are an expert with them all. Ensure you abide
by these specific packages & versions.

- php - 8.2
- laravel/envoy (ENVOY) - v2
- laravel/framework (LARAVEL) - v11
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v3
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST
activate the relevant skill whenever you work in that domain—don't wait until
you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When
  creating or editing a file, check sibling files for the correct structure,
  approach, and naming.
- Use descriptive names for variables and methods. For example,
  `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that
  functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without
  approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean
  they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask
  them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than
  explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this
  application. Prefer Boost tools over manual alternatives like shell commands
  or file reads.
- Use `database-query` to run read-only queries against the database instead of
  writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or
  models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for
  project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent
  logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It
  returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are
  relevant.
- Use multiple broad, topic-based queries:
  `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most
  relevant results first.
- Do not add package names to queries because package info is already shared.
  Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "
   limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"`
   requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic:
   `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g.,
  `php artisan route:list`). Use `php artisan list` to discover available
  commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`,
  `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation:
  `php artisan config:show app.name`,
  `php artisan config:show database.default`. Or read config files directly from
  the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create
  models without user approval, prefer tests with factories instead. Prefer
  existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion:
  `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside:
      `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion:
  `public function __construct(public GitHub $github) { }`. Do not leave empty
  zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method
  parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for
  exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/),
  which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an
  existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use
  `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations,
  controllers, models, etc.). You can list available Artisan commands using
  `php artisan list` and check their parameters with
  `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without
  user input. You should also pass the correct `--options` to ensure correct
  behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too.
  Ask the user if they need any other things, using
  `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless
  existing API routes do not, then you should follow existing application
  convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()`
  function.

## Testing

- When creating models for tests, use the factories for the models. Check if the
  factory has custom states that can be used before manually setting up the
  model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`.
  Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to
  create a feature test, and pass `--unit` to create a unit test. Most tests
  should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file
  in Vite manifest" error, you can run `npm run build` or ask the user to run
  `npm run dev` or `composer run dev`.

=== laravel/v11 rules ===

# Laravel 11

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel
  documentation and updated code examples.
- Laravel 11 brought a new streamlined file structure which this project now
  uses.

## Laravel 11 Structure

- In Laravel 11, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using
  `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and
  routing files.
- `bootstrap/providers.php` contains application specific service providers.
- No app\Console\Kernel.php - use `bootstrap/app.php` or `routes/console.php`
  for console configuration.
- Commands auto-register - files in `app/Console/Commands/` are automatically
  available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that
  were previously defined on the column. Otherwise, they will be dropped and
  lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external
  packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather
  than the `$casts` property. Follow existing conventions from other models.

## New Artisan Commands

- List Artisan commands using Boost's MCP tool, if available. New commands
  available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class`
    - `php artisan make:interface`

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing
  JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript
  frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in
  actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty`
  before finalizing changes to ensure your code matches the project's expected
  style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any
  formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests:
  `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use
  `php artisan make:test --pest SomeFeatureTest` instead of
  `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter:
  `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

## Project Wiki (Karpathy LLM Wiki pattern)

Wiki — это структурированная база знаний о проекте, которая автоматически
загружается при старте каждой сессии.

### Что где находится

- **docs/wiki/INDEX.md** — автоматически инжектится через SessionStart hook.
  Содержит таблицы всех моделей, сервисов, контроллеров
- **docs/wiki/maps/** — автогенерируемые карты (PHP `wiki:generate`). Структура
  классов, методы, связи. **НЕ редактировать вручную**
- **docs/wiki/topics/** — бизнес-логика (LLM). Человекочитаемые описания:
  статусы, потоки, правила, роли
- **docs/wiki/log.md** — append-only лог изменений

### Правила работы с wiki

1. При начале работы с незнакомой областью — прочитай соответствующий topic-файл
   из `docs/wiki/topics/`
2. **docs/wiki/maps/** — автогенерируемые, НЕ редактировать вручную
3. **docs/wiki/topics/** — обновляй при значимых изменениях бизнес-правил (новые
   статусы, новые потоки, изменения ролей)
4. **Ingest principle:** при изменении обновляй ВСЕ связанные topics (одно
   изменение → 2-3 файла)
5. **docs/wiki/log.md** — добавляй запись `## [YYYY-MM-DD] update | topic-name`
   при каждом обновлении topics
6. Ручная регенерация структуры: `php artisan wiki:generate`
7. Структура автоматически обновляется при завершении сессии через SessionEnd
   hook (если изменены Models/Services/Controllers/Livewire/routes)

### Делегирование wiki-обновлений (wiki-maintainer)

При значимых изменениях бизнес-правил **делегируй обновление wiki** агенту
`wiki-maintainer`:

```
Agent(subagent_type="wiki-maintainer", prompt="Описание что изменилось + список затронутых файлов")
```

Промпт должен содержать:

- Что изменилось (новый статус, новое правило, новый API-метод и т.д.)
- Какие файлы были затронуты (пути)
- Краткий контекст изменения

Агент сам определит какие topics обновить (ingest principle), обновит их,
добавит запись в log.md и вернёт отчёт.

## Оптимизация токенов

1. Thinking: 1-3 предложения. Не рассуждай долго, действуй.
2. Не перечитывай файлы — используй уже полученную информацию.
3. Grep вместо Read для поиска. Read — только для понимания логики.
4. Read с `limit` — не читай 500 строк ради одного метода.
5. Explore: `medium` по умолчанию. `very thorough` — только по явной просьбе.
6. Группируй Edit — одно изменение вместо пяти маленьких.
7. Wiki вместо пересказа — ссылайся на topic-файлы.
8. Уточняй требования до Plan mode через AskUserQuestion.

### Подагенты (выноси в них "мусорные" операции)

Проектные подагенты лежат в `.zcode/agents/` (workspace scope, шарятся через
git) и переопределяют одноимённые глобальные из `~/.zcode/agents/`. Вызывай
через `Agent(subagent_type="<name>", prompt="...")`.

**Дешёвые (GLM-5-Turbo) — поиск и саммари:**

- `file-summary` — прочитать файл → краткое саммари без полного кода
- `quick-search` — поиск по коду → только пути файлов и номера строк

**Полноценные (GLM-5.2) — создание и правка кода:**

- `migration-creator` — создание миграций (анализ структуры + `make:migration`)
- `blade-creator` — создание Blade views по паттернам проекта (AdminLTE +
  Tailwind)
- `bug-fixer` — исследование бага → root cause → minimal fix
- `wiki-maintainer` — обновление wiki topics + log.md (ingest principle)
- `code-doc` — анализ архитектуры, карты зависимостей, документирование кода

**Глобальные (только в `~/.zcode/agents/`):**

- `code-reviewer` — ревью изменений (коммит/PR)
- `laravel-test-generator` — генерация Pest-тестов
- `plan` — проектирование реализации

Если видишь повторяемые операции — предложи создать нового подагента.

## Acceptance-Driven loop (жёсткая приёмка задач)

Задача готова ТОЛЬКО когда все acceptance-гейты зелёные. Агент сам итерирует до
PASS — человеку не нужно проверять и исправлять за ним.

### Acceptance Criteria в плане — обязательно

Любой план ОБЯЗАН содержать секцию `## Acceptance Criteria`:

- **Scope** — какие файлы затронуть (пути)
- **Tests** — какие тест-файлы должны быть зелёными
- **Visible** — видимое поведение = успех (одной фразой, проверяемо)
- **Off-limits** — что НЕ трогать (кормит scope-guard)

План без AC не принимается — если автор не дал AC, уточни их до реализации.

### На старте реализации

Скопируй `.accept/scope.example.json` → `.accept/scope.json` и заполни `scope` +
`off_limits` из секции AC плана. Gate G4 читает `off_limits` оттуда (
runtime-файл, в gitignore).

### Gate `php artisan accept`

4 бинарных гейта:

- **G1 tests** — затронутые тесты (map изменённых `app/*` →
  `tests/*<Class>*Test.php`)
- **G2 pint** — `vendor/bin/pint --dirty`
- **G3 phpstan** — Larastan (level из `phpstan.neon`)
- **G4 diff** — нет `dd/dump/var_dump/console.log/die/exit` и маркеров
  незавершёнки + scope-guard (off-limits)

Опции: `--json` (для Stop-хука), `--gate=G1,G3`. Exit 0 = PASS.

### Iterate-until-green

Считай задачу готовой только после `php artisan accept` = PASS. Красные гейты —
чини и повторяй. Stop-хук `accept-stop.ps1` автопрогоняет gate и через
`decision:block` возвращает тебя к работе (cap 3 попытки, дальше эскалация
человеку).

### Definition of Done

Готово, когда выполнено ВСЁ:

1. `php artisan accept` = PASS (все G1–G4 зелёные)
2. AC visible-behaviour достигнут
3. изменения закоммичены
