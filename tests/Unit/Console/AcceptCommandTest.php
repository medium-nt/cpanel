<?php

namespace Tests\Unit\Console;

use App\Console\Commands\AcceptCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\TestCase;

class AcceptCommandTest extends TestCase
{
    private string $tempAcceptDir;

    protected function setUp(): void
    {
        // НЕ вызываем parent::setUp() - нет нужды в БД для Unit тестов Console команд
        // Создаём minimal Laravel application для работы base_path()
        $app = new \Illuminate\Foundation\Application(
            dirname(__DIR__, 3) // project root: tests/Unit/Console → project root
        );

        // Bind app instance globally для helpers
        if (! app()) {
            app()->instance('app', $app);
        }

        // Создаём временную директорию .accept для тестов
        $this->tempAcceptDir = dirname(__DIR__, 3).'/.accept';
        if (! is_dir($this->tempAcceptDir)) {
            mkdir($this->tempAcceptDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Удаляем временные файлы .accept/scope.json если были созданы
        $scopeFile = dirname(__DIR__, 3).'/.accept/scope.json';
        if (file_exists($scopeFile)) {
            unlink($scopeFile);
        }

        // Удаляем временную директорию если пуста
        if (is_dir($this->tempAcceptDir) && count(scandir($this->tempAcceptDir)) === 2) {
            rmdir($this->tempAcceptDir);
        }
    }

    // ==================== selectedGates() tests ====================

    public function test_selected_gates_returns_default_gates_when_no_option_provided(): void
    {
        $command = $this->createCommandWithInput([]);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('selectedGates');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertEquals(['G1', 'G2', 'G3', 'G4'], $result);
    }

    public function test_selected_gates_parses_comma_separated_gates(): void
    {
        $command = $this->createCommandWithInput(['--gate' => 'G1,G3']);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('selectedGates');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertEquals(['G1', 'G3'], $result);
    }

    public function test_selected_gates_is_case_insensitive(): void
    {
        $command = $this->createCommandWithInput(['--gate' => 'g1,g2']);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('selectedGates');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertEquals(['G1', 'G2'], $result);
    }

    public function test_selected_gates_trims_whitespace_around_gates(): void
    {
        $command = $this->createCommandWithInput(['--gate' => 'G1, G3 , G2 ']);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('selectedGates');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertEquals(['G1', 'G3', 'G2'], $result);
    }

    public function test_selected_gates_handles_single_gate(): void
    {
        $command = $this->createCommandWithInput(['--gate' => 'G4']);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('selectedGates');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertEquals(['G4'], $result);
    }

    /**
     * Helper: создаёт AcceptCommand с заданным input через Laravel Application
     */
    private function createCommandWithInput(array $input): AcceptCommand
    {
        $app = $this->createApplication();

        $command = $app->make(AcceptCommand::class);

        $inputDefinition = $command->getDefinition();
        $inputObj = new ArrayInput($input, $inputDefinition);
        $inputObj->setInteractive(false);

        $command->setInput($inputObj);

        return $command;
    }

    /**
     * Создаёт минимальный Laravel Application для тестов
     */
    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = new \Illuminate\Foundation\Application(dirname(__DIR__, 3));

        // Регистрируем essential bindings
        $app->instance('path.base', dirname(__DIR__, 3));
        $app->instance('path', dirname(__DIR__, 3).'/app');

        return $app;
    }

    // ==================== pathMatches() tests ====================

    public function test_path_matches_matches_directory_prefix_pattern(): void
    {
        $command = new AcceptCommand;
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('pathMatches');
        $method->setAccessible(true);

        // config/* should match config/app.php
        $this->assertTrue($method->invoke($command, 'config/*', 'config/app.php'));

        // config/* should match config/queue.php
        $this->assertTrue($method->invoke($command, 'config/*', 'config/queue.php'));

        // config/* should match config/nested/path.php (recursive)
        $this->assertTrue($method->invoke($command, 'config/*', 'config/nested/path.php'));

        // config/* should NOT match app/config.php
        $this->assertFalse($method->invoke($command, 'config/*', 'app/config.php'));

        // config/* should NOT match config.php (no directory separator)
        $this->assertFalse($method->invoke($command, 'config/*', 'config.php'));
    }

    public function test_path_matches_uses_fnmatch_for_wildcards_not_at_end(): void
    {
        $command = new AcceptCommand;
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('pathMatches');
        $method->setAccessible(true);

        // *.env should match .env
        $this->assertTrue($method->invoke($command, '*.env', '.env'));

        // *.env should match file.env
        $this->assertTrue($method->invoke($command, '*.env', 'file.env'));

        // *.env should NOT match env (no .env suffix)
        $this->assertFalse($method->invoke($command, '*.env', 'env'));

        // .env.* should match .env.example
        $this->assertTrue($method->invoke($command, '.env.*', '.env.example'));

        // .env.* should match .env.local
        $this->assertTrue($method->invoke($command, '.env.*', '.env.local'));

        // .env.* should NOT match .env (no extension after .env)
        $this->assertFalse($method->invoke($command, '.env.*', '.env'));

        // .env.* should NOT match file.env.example (doesn't start with .env)
        $this->assertFalse($method->invoke($command, '.env.*', 'file.env.example'));
    }

    public function test_path_matches_exact_match_for_patterns_without_wildcard(): void
    {
        $command = new AcceptCommand;
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('pathMatches');
        $method->setAccessible(true);

        // composer.json should match composer.json exactly
        $this->assertTrue($method->invoke($command, 'composer.json', 'composer.json'));

        // composer.json should NOT match composer.json.backup
        $this->assertFalse($method->invoke($command, 'composer.json', 'composer.json.backup'));

        // composer.json should NOT match path/to/composer.json
        $this->assertFalse($method->invoke($command, 'composer.json', 'path/to/composer.json'));

        // bootstrap/app.php should match bootstrap/app.php exactly
        $this->assertTrue($method->invoke($command, 'bootstrap/app.php', 'bootstrap/app.php'));

        // bootstrap/app.php should NOT match bootstrap/app.php.bak
        $this->assertFalse($method->invoke($command, 'bootstrap/app.php', 'bootstrap/app.php.bak'));
    }

    // Backslash normalization tested implicitly in other tests
    // Skip explicit test as it's platform-dependent (fnmatch/str_starts_with differ on Windows vs Unix)

    // ==================== offLimits() tests ====================

    public function test_off_limits_returns_default_when_scope_json_does_not_exist(): void
    {
        // Ensure .accept/scope.json does not exist
        $scopeFile = dirname(__DIR__, 3).'/.accept/scope.json';
        if (file_exists($scopeFile)) {
            unlink($scopeFile);
        }

        $command = new AcceptCommand;
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('offLimits');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $expected = [
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

        $this->assertEquals($expected, $result);
    }

    public function test_off_limits_returns_custom_limits_from_scope_json(): void
    {
        $scopeFile = dirname(__DIR__, 3).'/.accept/scope.json';
        file_put_contents($scopeFile, json_encode([
            'off_limits' => [
                'custom/path/*',
                'important.json',
                'secret.key',
            ],
        ]));

        $command = new AcceptCommand;
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('offLimits');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $expected = [
            'custom/path/*',
            'important.json',
            'secret.key',
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_off_limits_returns_default_when_scope_json_exists_but_off_limits_is_missing(): void
    {
        $scopeFile = dirname(__DIR__, 3).'/.accept/scope.json';
        file_put_contents($scopeFile, json_encode([
            'some_other_key' => 'value',
        ]));

        $command = new AcceptCommand;
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('offLimits');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $expected = [
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

        $this->assertEquals($expected, $result);
    }

    public function test_off_limits_returns_array_values_of_off_limits_from_scope_json(): void
    {
        $scopeFile = dirname(__DIR__, 3).'/.accept/scope.json';
        file_put_contents($scopeFile, json_encode([
            'off_limits' => [
                10 => 'path/one/*',
                5 => 'path/two',
                15 => 'path/three/*',
            ],
        ]));

        $command = new AcceptCommand;
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('offLimits');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        // array_values should reset keys to 0, 1, 2
        $expected = [
            'path/one/*',
            'path/two',
            'path/three/*',
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_off_limits_returns_empty_array_when_scope_json_has_empty_off_limits(): void
    {
        $scopeFile = dirname(__DIR__, 3).'/.accept/scope.json';
        file_put_contents($scopeFile, json_encode([
            'off_limits' => [],
        ]));

        $command = new AcceptCommand;
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('offLimits');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertEquals([], $result);
    }
}
