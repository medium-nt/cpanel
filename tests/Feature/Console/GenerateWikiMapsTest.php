<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Тесты команды wiki:generate: структурные карты + граф зависимостей + god-nodes.
 * RefreshDatabase нужен: wiki:generate при автозагрузке сервисов триггерит БД-запросы (Role::where name=seamstress).
 */
class GenerateWikiMapsTest extends TestCase
{
    use RefreshDatabase;

    private string $graphPath;

    private string $dependenciesMapPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->graphPath = base_path('docs/wiki/.graph.json');
        $this->dependenciesMapPath = base_path('docs/wiki/maps/dependencies.md');
    }

    #[Test]
    public function wiki_generate_exits_with_zero(): void
    {
        $this->artisan('wiki:generate')->assertSuccessful();
    }

    #[Test]
    public function graph_json_is_generated_and_valid(): void
    {
        $this->artisan('wiki:generate')->assertSuccessful();

        $this->assertFileExists($this->graphPath);

        $graph = json_decode((string) file_get_contents($this->graphPath), true);

        $this->assertIsArray($graph);
        $this->assertArrayHasKey('nodes', $graph);
        $this->assertArrayHasKey('edges', $graph);
        $this->assertArrayHasKey('god_nodes', $graph);
        $this->assertArrayHasKey('stats', $graph);
    }

    #[Test]
    public function graph_has_more_than_50_nodes(): void
    {
        $this->artisan('wiki:generate')->assertSuccessful();

        $graph = json_decode((string) file_get_contents($this->graphPath), true);

        // 25 Services + 41 Controllers = ~66
        $this->assertGreaterThan(50, count($graph['nodes']));
    }

    #[Test]
    public function graph_has_static_call_edges(): void
    {
        $this->artisan('wiki:generate')->assertSuccessful();

        $graph = json_decode((string) file_get_contents($this->graphPath), true);
        $kinds = array_unique(array_column($graph['edges'], 'kind'));

        // Проект использует статические сервисы (stateless) — рёбра в основном static_call.
        // DI-рёбер может не быть вообще (архитектурный факт), поэтому проверяем только static_call.
        $this->assertContains('static_call', $kinds, 'Граф должен содержать static-call рёбра (Service::method)');
        $this->assertNotEmpty($graph['edges'], 'Граф должен содержать хотя бы одно ребро');
    }

    #[Test]
    public function marketplace_api_service_is_in_god_nodes(): void
    {
        $this->artisan('wiki:generate')->assertSuccessful();

        $graph = json_decode((string) file_get_contents($this->graphPath), true);
        $names = array_column($graph['god_nodes'], 'name');

        // MarketplaceApiService — god-object (3258 строк, 54 static-метода). Без static-call extraction он был бы невидим.
        $this->assertContains('MarketplaceApiService', $names);
    }

    #[Test]
    public function god_nodes_score_follows_formula(): void
    {
        $this->artisan('wiki:generate')->assertSuccessful();

        $graph = json_decode((string) file_get_contents($this->graphPath), true);

        foreach ($graph['god_nodes'] as $node) {
            $expected = ($node['indegree'] * 2) + $node['outdegree'];
            $this->assertSame($expected, $node['score'], "Score для {$node['name']} не соответствует формуле indegree*2 + outdegree");
        }
    }

    #[Test]
    public function dependencies_map_contains_mermaid_and_table(): void
    {
        $this->artisan('wiki:generate')->assertSuccessful();

        $this->assertFileExists($this->dependenciesMapPath);

        $content = (string) file_get_contents($this->dependenciesMapPath);

        $this->assertStringContainsString('## Top 10 God Nodes', $content);
        $this->assertStringContainsString('```mermaid', $content);
    }
}
