<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Gate engine системы приёмки (Acceptance-Driven loop).
 *
 * Прогоняет бинарные гейты готовности задачи:
 *  G1 — затронутые тесты зелёные (map изменённых app/* → tests/*Test.php + тесты из диффа)
 *  G2 — Laravel Pint --dirty чистый
 *  G3 — PHPStan (Larastan) без ошибок (уровень из phpstan.neon)
 *  G4 — diff-cleanliness (отладочные вызовы и маркеры незавершёнки) + scope-guard (off-limits)
 *
 * Exit code: 0 — все выбранные гейты PASS, !=0 — хотя бы один FAIL/SKIP трактуется как FAIL только при наличии FAIL.
 * Вывод: человекочитаемая сводка (без --json) или JSON-объект для Stop-хука (с --json).
 */
class AcceptCommand extends Command
{
    /**
     * Сигнатура команды.
     *
     * @var string
     */
    protected $signature = 'accept
        {--json : JSON-вывод для Stop-хука (overall/hash/gates)}
        {--gate= : Список гейтов через запятую, например G1,G3}';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Прогон acceptance-гейтов: G1 tests, G2 pint, G3 phpstan, G4 diff-cleanliness+scope';

    /**
     * Дефолтный off-limits, когда нет .accept/scope.json.
     * Файлы/директории, которые агент не должен трогать автоматом.
     *
     * @var array<int,string>
     */
    private const DEFAULT_OFF_LIMITS = [
        '.env',
        '.env.*',
        'composer.json',
        'composer.lock',
        'bootstrap/app.php',
        'config/*',
        'vendor/*',
        'node_modules/*',
        'database/database.sqlite',
    ];

    /**
     * Паттерны отладочного мусора в добавленных строках диффа.
     *
     * @var array<int,string>
     */
    private const DEBUG_PATTERNS = [
        '\bdd\s*\(',
        '\bdump\s*\(',
        '\bvar_dump\s*\(',
        '\bdie\s*\(',
        '\bexit\s*\(',
        'console\.log\s*\(',
    ];

    /**
     * Запуск gate-engine: прогон выбранных гейтов, сбор результатов, вывод.
     *
     * @return int 0 при overall PASS, 1 при любом FAIL.
     */
    public function handle(): int
    {
        $selected = $this->selectedGates();

        $results = [];
        if (in_array('G1', $selected, true)) {
            $results['G1'] = $this->runTestsGate();
        }
        if (in_array('G2', $selected, true)) {
            $results['G2'] = $this->runPintGate();
        }
        if (in_array('G3', $selected, true)) {
            $results['G3'] = $this->runPhpstanGate();
        }
        if (in_array('G4', $selected, true)) {
            $results['G4'] = $this->runDiffGate();
        }

        $hasFail = collect($results)->contains(fn (array $r): bool => $r['status'] === 'fail');
        $overall = $hasFail ? 'fail' : 'pass';

        if ($this->option('json')) {
            $this->line(json_encode([
                'overall' => $overall,
                'hash' => $this->workingTreeHash(),
                'gates' => $results,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderTable($results, $overall);
        }

        return $overall === 'pass' ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Разбор опции --gate в верхний регистр; по умолчанию все G1–G4.
     *
     * @return array<int,string>
     */
    private function selectedGates(): array
    {
        if (! $this->option('gate')) {
            return ['G1', 'G2', 'G3', 'G4'];
        }

        return array_map('strtoupper', array_map('trim', explode(',', (string) $this->option('gate'))));
    }

    /**
     * G1: прогон затронутых тестов через `php artisan test`.
     * Если map изменённых файлов не дал тестов — SKIP с предупреждением.
     *
     * @return array{status:string,detail:string}
     */
    private function runTestsGate(): array
    {
        $tests = $this->affectedTests();
        if (empty($tests)) {
            return [
                'status' => 'skip',
                'detail' => 'нет затронутых тестов (map изменённых файлов пуст) — рекомендуется добавить тест',
            ];
        }

        $cmdline = 'php artisan test --compact '.implode(' ', array_map('escapeshellarg', $tests));
        $proc = Process::fromShellCommandline($cmdline, base_path(), null, null, 300);
        $proc->run();

        if ($proc->isSuccessful()) {
            return ['status' => 'pass', 'detail' => count($tests).' тест-файл(ов) зелёный'];
        }

        return ['status' => 'fail', 'detail' => $this->tail(trim($proc->getOutput()."\n".$proc->getErrorOutput()), 25)];
    }

    /**
     * G2: Laravel Pint --dirty (форматирует изменённые PHP-файлы, exit 0 = ок).
     *
     * @return array{status:string,detail:string}
     */
    private function runPintGate(): array
    {
        $proc = Process::fromShellCommandline('vendor/bin/pint --dirty', base_path(), null, null, 120);
        $proc->run();

        $output = trim($proc->getOutput()."\n".$proc->getErrorOutput());

        return [
            'status' => $proc->isSuccessful() ? 'pass' : 'fail',
            'detail' => $proc->isSuccessful() ? 'clean (auto-format applied)' : $this->tail($output, 15),
        ];
    }

    /**
     * G3: PHPStan (Larastan) — уровень и пути из phpstan.neon.
     *
     * @return array{status:string,detail:string}
     */
    private function runPhpstanGate(): array
    {
        $proc = Process::fromShellCommandline(
            'vendor/bin/phpstan analyse --memory-limit=2G --no-ansi',
            base_path(),
            null,
            null,
            300
        );
        $proc->run();

        $output = trim($proc->getOutput()."\n".$proc->getErrorOutput());

        return [
            'status' => $proc->isSuccessful() ? 'pass' : 'fail',
            'detail' => $proc->isSuccessful() ? 'no errors' : $this->tail($output, 25),
        ];
    }

    /**
     * G4: diff-cleanliness (отладочный мусор, маркеры незавершёнки в добавленных строках) + scope-guard (off-limits).
     *
     * @return array{status:string,detail:string}
     */
    private function runDiffGate(): array
    {
        $violations = [];

        $added = $this->addedContent();
        foreach (self::DEBUG_PATTERNS as $pattern) {
            if (preg_match('/'.$pattern.'/', $added)) {
                $violations[] = 'отладочный вызов: '.$pattern;
            }
        }
        // Маркеры собираем из кодов символов, чтобы детектор не срабатывал
        // на собственном исходнике (dogfooding G4).
        [$todo, $fixme] = [
            implode(array_map('chr', [84, 79, 68, 79])),
            implode(array_map('chr', [70, 73, 88, 77, 69])),
        ];
        if (preg_match('/\b('.$todo.'|'.$fixme.')\b/', $added)) {
            $violations[] = 'найдены маркеры незавершёнки ('.$todo.'/'.$fixme.')';
        }

        $offLimits = $this->offLimits();
        foreach ($this->changedAnyFiles() as $file) {
            foreach ($offLimits as $pattern) {
                if ($this->pathMatches($pattern, $file)) {
                    $violations[] = "scope: затронут off-limits «{$pattern}» → {$file}";
                }
            }
        }

        return [
            'status' => empty($violations) ? 'pass' : 'fail',
            'detail' => empty($violations) ? 'clean' : implode('; ', array_slice($violations, 0, 8)),
        ];
    }

    /**
     * Список затронутых тестов: тесты из диффа + map изменённых app/* по имени класса.
     *
     * @return array<int,string>
     */
    private function affectedTests(): array
    {
        $tests = [];
        foreach ($this->changedPhpFiles() as $file) {
            $normalized = str_replace('\\', '/', $file);
            if (str_starts_with($normalized, 'tests/')) {
                $tests[] = $normalized;

                continue;
            }
            $base = basename($normalized, '.php');
            if ($base === '' || str_contains($base, ' ')) {
                continue;
            }
            foreach ($this->findTestsForClass($base) as $relative) {
                $tests[] = $relative;
            }
        }

        return array_values(array_unique($tests));
    }

    /**
     * Поиск test-файлов для класса по конвенции имени (*<Class>*Test.php).
     *
     * @return array<int,string>
     */
    private function findTestsForClass(string $base): array
    {
        if (! is_dir(base_path('tests'))) {
            return [];
        }
        $finder = Finder::create()
            ->in(base_path('tests'))
            ->files()
            ->name("/{$base}Test\.php$|.*{$base}Test\.php$/i");

        $out = [];
        foreach ($finder as $f) {
            $out[] = 'tests/'.str_replace('\\', '/', $f->getRelativePathname());
        }

        return $out;
    }

    /**
     * Изменённые .php-файлы: git diff (tracked) + untracked, только .php.
     *
     * @return array<int,string>
     */
    private function changedPhpFiles(): array
    {
        return array_values(array_filter(
            $this->changedAnyFiles(),
            fn (string $f): bool => str_ends_with($f, '.php')
        ));
    }

    /**
     * Все изменённые файлы (любые): tracked (added/copied/modified) + untracked.
     *
     * @return array<int,string>
     */
    private function changedAnyFiles(): array
    {
        $tracked = Process::fromShellCommandline('git diff --name-only --diff-filter=ACM HEAD', base_path());
        $tracked->run();
        $untracked = Process::fromShellCommandline('git ls-files --others --exclude-standard', base_path());
        $untracked->run();

        $files = array_filter(array_merge(
            explode("\n", trim($tracked->getOutput())),
            explode("\n", trim($untracked->getOutput()))
        ));

        return array_values(array_unique(array_map(
            fn (string $f): string => str_replace('\\', '/', $f),
            $files
        )));
    }

    /**
     * Содержимое «добавленного»: добавленные строки из git diff HEAD + полный текст untracked .php.
     */
    private function addedContent(): string
    {
        $diff = Process::fromShellCommandline('git diff HEAD', base_path());
        $diff->run();
        $added = collect(explode("\n", $diff->getOutput()))
            ->filter(fn (string $line): bool => str_starts_with($line, '+') && ! str_starts_with($line, '+++'))
            ->map(fn (string $line): string => substr($line, 1))
            ->implode("\n");

        foreach ($this->changedPhpFiles() as $file) {
            $normalized = str_replace('\\', '/', $file);
            if (! str_starts_with($normalized, 'tests/') && $this->isUntracked($normalized)) {
                $path = base_path($normalized);
                if (is_file($path)) {
                    $added .= "\n".(string) file_get_contents($path);
                }
            }
        }

        return $added;
    }

    /**
     * Проверка, что файл untracked (не в git index).
     */
    private function isUntracked(string $file): bool
    {
        $proc = Process::fromShellCommandline('git ls-files --error-unmatch '.escapeshellarg($file), base_path());
        $proc->run();

        return ! $proc->isSuccessful();
    }

    /**
     * Список off-limits: из .accept/scope.json (ключ off_limits), иначе дефолт.
     *
     * @return array<int,string>
     */
    private function offLimits(): array
    {
        $scopeFile = base_path('.accept/scope.json');
        if (is_file($scopeFile)) {
            $data = json_decode((string) file_get_contents($scopeFile), true);
            if (is_array($data['off_limits'] ?? null)) {
                return array_values($data['off_limits']);
            }
        }

        return self::DEFAULT_OFF_LIMITS;
    }

    /**
     * Сопоставление файла с glob-паттерном off-limits.
     * Паттерн «dir/*» → префикс директории (рекурсивно); иначе fnmatch/exact.
     */
    private function pathMatches(string $pattern, string $file): bool
    {
        $pattern = str_replace('\\', '/', $pattern);
        if (str_ends_with($pattern, '/*')) {
            $prefix = substr($pattern, 0, -1); // 'config/'

            return str_starts_with($file, $prefix);
        }
        if (str_contains($pattern, '*')) {
            return fnmatch($pattern, $file);
        }

        return $file === $pattern;
    }

    /**
     * Хеш рабочего дерева для throttle Stop-хука (md5 от git diff + untracked + off-limits).
     */
    private function workingTreeHash(): string
    {
        $diff = Process::fromShellCommandline('git diff HEAD', base_path());
        $diff->run();
        $untracked = Process::fromShellCommandline('git ls-files --others --exclude-standard', base_path());
        $untracked->run();

        return md5($diff->getOutput().'|'.$untracked->getOutput().'|'.implode(',', $this->offLimits()));
    }

    /**
     * Последние N строк текста (для кратких detail в отчётах гейтов).
     */
    private function tail(string $text, int $lines): string
    {
        $all = explode("\n", $text);
        $slice = array_slice($all, max(0, count($all) - $lines));

        return trim(implode("\n", $slice));
    }

    /**
     * Человекочитаемый вывод результатов гейтов.
     *
     * @param  array<string,array{status:string,detail:string}>  $results
     */
    private function renderTable(array $results, string $overall): void
    {
        foreach ($results as $gate => $r) {
            $icon = match ($r['status']) {
                'pass' => '✓',
                'fail' => '✗',
                default => '–',
            };
            $this->line(sprintf('[%s] %s %s — %s', $gate, $icon, $r['status'], $r['detail']));
        }
        $this->line(str_repeat('─', 40));
        $this->line($overall === 'pass' ? 'VERDICT: PASS' : 'VERDICT: FAIL');
    }
}
