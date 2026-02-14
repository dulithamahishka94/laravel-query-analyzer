<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Contracts\AIProvider;
use GladeHQ\QueryLens\Services\AI\NullProvider;
use GladeHQ\QueryLens\Services\AI\OpenAIProvider;
use GladeHQ\QueryLens\Services\AIQueryOptimizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class AIQueryOptimizerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('query-lens.storage.driver', 'cache');
        $app['config']->set('query-lens.ai.enabled', false);
    }

    // ---------------------------------------------------------------
    // NullProvider
    // ---------------------------------------------------------------

    public function test_null_provider_returns_empty_suggestions(): void
    {
        $provider = new NullProvider();
        $result = $provider->analyze('SELECT * FROM users');

        $this->assertEmpty($result['suggestions']);
        $this->assertNull($result['raw_response']);
        $this->assertSame('null', $result['provider']);
    }

    public function test_null_provider_is_always_available(): void
    {
        $provider = new NullProvider();
        $this->assertTrue($provider->isAvailable());
    }

    public function test_null_provider_name(): void
    {
        $provider = new NullProvider();
        $this->assertSame('null', $provider->getName());
    }

    // ---------------------------------------------------------------
    // OpenAIProvider: configuration
    // ---------------------------------------------------------------

    public function test_openai_provider_name(): void
    {
        $provider = new OpenAIProvider('test-key');
        $this->assertSame('openai', $provider->getName());
    }

    public function test_openai_provider_available_with_api_key(): void
    {
        $provider = new OpenAIProvider('sk-test-key');
        $this->assertTrue($provider->isAvailable());
    }

    public function test_openai_provider_unavailable_without_api_key(): void
    {
        $provider = new OpenAIProvider('');
        $this->assertFalse($provider->isAvailable());
    }

    public function test_openai_provider_returns_error_when_unavailable(): void
    {
        $provider = new OpenAIProvider('');
        $result = $provider->analyze('SELECT * FROM users');

        $this->assertEmpty($result['suggestions']);
        $this->assertArrayHasKey('error', $result);
    }

    // ---------------------------------------------------------------
    // OpenAIProvider: prompt building
    // ---------------------------------------------------------------

    public function test_prompt_includes_sql(): void
    {
        $provider = new OpenAIProvider('test-key');
        $prompt = $provider->buildPrompt('SELECT * FROM users WHERE id = ?');

        $this->assertStringContainsString('SELECT * FROM users WHERE id = ?', $prompt);
    }

    public function test_prompt_includes_explain_context(): void
    {
        $provider = new OpenAIProvider('test-key');
        $prompt = $provider->buildPrompt('SELECT * FROM users', [
            'explain' => [['type' => 'ALL', 'rows' => 1000]],
        ]);

        $this->assertStringContainsString('EXPLAIN output', $prompt);
        $this->assertStringContainsString('ALL', $prompt);
    }

    public function test_prompt_includes_schema_context(): void
    {
        $provider = new OpenAIProvider('test-key');
        $prompt = $provider->buildPrompt('SELECT * FROM users', [
            'schema' => ['id' => 'int', 'email' => 'varchar(255)'],
        ]);

        $this->assertStringContainsString('Table schema', $prompt);
    }

    public function test_prompt_includes_frequency(): void
    {
        $provider = new OpenAIProvider('test-key');
        $prompt = $provider->buildPrompt('SELECT * FROM users', [
            'frequency' => 500,
        ]);

        $this->assertStringContainsString('500', $prompt);
        $this->assertStringContainsString('frequency', $prompt);
    }

    public function test_prompt_includes_avg_duration(): void
    {
        $provider = new OpenAIProvider('test-key');
        $prompt = $provider->buildPrompt('SELECT * FROM users', [
            'avg_duration' => 250,
        ]);

        $this->assertStringContainsString('250', $prompt);
        $this->assertStringContainsString('duration', $prompt);
    }

    public function test_prompt_includes_existing_indexes(): void
    {
        $provider = new OpenAIProvider('test-key');
        $prompt = $provider->buildPrompt('SELECT * FROM users', [
            'indexes' => ['idx_email' => 'email'],
        ]);

        $this->assertStringContainsString('indexes', $prompt);
        $this->assertStringContainsString('idx_email', $prompt);
    }

    public function test_prompt_without_context_is_minimal(): void
    {
        $provider = new OpenAIProvider('test-key');
        $prompt = $provider->buildPrompt('SELECT 1');

        $this->assertStringContainsString('SELECT 1', $prompt);
        $this->assertStringNotContainsString('EXPLAIN output', $prompt);
        $this->assertStringNotContainsString('Table schema', $prompt);
    }

    // ---------------------------------------------------------------
    // OpenAIProvider: response parsing
    // ---------------------------------------------------------------

    public function test_parses_valid_json_array_response(): void
    {
        $provider = new OpenAIProvider('test-key');
        $content = json_encode([
            ['type' => 'index', 'suggestion' => 'Add index on email', 'impact' => 'high', 'explanation' => 'Speed up lookups'],
        ]);

        $suggestions = $provider->parseResponse($content);

        $this->assertCount(1, $suggestions);
        $this->assertSame('index', $suggestions[0]['type']);
        $this->assertSame('Add index on email', $suggestions[0]['suggestion']);
        $this->assertSame('high', $suggestions[0]['impact']);
    }

    public function test_parses_json_wrapped_in_code_block(): void
    {
        $provider = new OpenAIProvider('test-key');
        $content = "```json\n" . json_encode([
            ['type' => 'rewrite', 'suggestion' => 'Use specific columns', 'impact' => 'medium', 'explanation' => 'Reduces data transfer'],
        ]) . "\n```";

        $suggestions = $provider->parseResponse($content);

        $this->assertCount(1, $suggestions);
        $this->assertSame('rewrite', $suggestions[0]['type']);
    }

    public function test_parses_response_with_missing_optional_fields(): void
    {
        $provider = new OpenAIProvider('test-key');
        $content = json_encode([
            ['suggestion' => 'Optimize this query'],
        ]);

        $suggestions = $provider->parseResponse($content);

        $this->assertCount(1, $suggestions);
        $this->assertSame('general', $suggestions[0]['type']);
        $this->assertSame('medium', $suggestions[0]['impact']);
    }

    public function test_parses_invalid_json_returns_empty(): void
    {
        $provider = new OpenAIProvider('test-key');
        $suggestions = $provider->parseResponse('This is not JSON at all');

        $this->assertEmpty($suggestions);
    }

    public function test_parses_empty_string_returns_empty(): void
    {
        $provider = new OpenAIProvider('test-key');
        $suggestions = $provider->parseResponse('');

        $this->assertEmpty($suggestions);
    }

    public function test_parses_json_embedded_in_text(): void
    {
        $provider = new OpenAIProvider('test-key');
        $content = "Here are my suggestions:\n" . json_encode([
            ['type' => 'index', 'suggestion' => 'Add index', 'impact' => 'high', 'explanation' => 'test'],
        ]) . "\nHope this helps!";

        $suggestions = $provider->parseResponse($content);

        $this->assertCount(1, $suggestions);
    }

    public function test_skips_entries_without_suggestion_field(): void
    {
        $provider = new OpenAIProvider('test-key');
        $content = json_encode([
            ['type' => 'index', 'impact' => 'high'],
            ['type' => 'rewrite', 'suggestion' => 'Valid suggestion', 'impact' => 'low', 'explanation' => ''],
        ]);

        $suggestions = $provider->parseResponse($content);

        $this->assertCount(1, $suggestions);
        $this->assertSame('Valid suggestion', $suggestions[0]['suggestion']);
    }

    // ---------------------------------------------------------------
    // AIQueryOptimizer: disabled state
    // ---------------------------------------------------------------

    public function test_optimizer_disabled_returns_empty(): void
    {
        $optimizer = new AIQueryOptimizer(['enabled' => false]);
        $result = $optimizer->optimize('SELECT * FROM users');

        $this->assertEmpty($result['suggestions']);
        $this->assertStringContainsString('disabled', $result['message']);
    }

    public function test_optimizer_default_uses_null_provider(): void
    {
        $optimizer = new AIQueryOptimizer([]);
        $this->assertInstanceOf(NullProvider::class, $optimizer->getProvider());
    }

    public function test_optimizer_not_enabled_by_default(): void
    {
        $optimizer = new AIQueryOptimizer([]);
        $this->assertFalse($optimizer->isEnabled());
    }

    // ---------------------------------------------------------------
    // AIQueryOptimizer: enabled with mock provider
    // ---------------------------------------------------------------

    public function test_optimizer_enabled_calls_provider(): void
    {
        $mockProvider = new class implements AIProvider {
            public bool $called = false;

            public function analyze(string $sql, array $context = []): array
            {
                $this->called = true;
                return [
                    'suggestions' => [
                        ['type' => 'index', 'suggestion' => 'Test', 'impact' => 'high', 'explanation' => ''],
                    ],
                    'raw_response' => 'test',
                    'provider' => 'mock',
                ];
            }

            public function getName(): string { return 'mock'; }
            public function isAvailable(): bool { return true; }
        };

        $optimizer = new AIQueryOptimizer(['enabled' => true]);
        $optimizer->setProvider($mockProvider);

        $result = $optimizer->optimize('SELECT * FROM users');

        $this->assertTrue($mockProvider->called);
        $this->assertCount(1, $result['suggestions']);
        $this->assertFalse($result['cached']);
    }

    // ---------------------------------------------------------------
    // AIQueryOptimizer: caching
    // ---------------------------------------------------------------

    public function test_optimizer_caches_results(): void
    {
        $callCount = 0;
        $mockProvider = new class($callCount) implements AIProvider {
            private int $count;

            public function __construct(int &$count)
            {
                $this->count = &$count;
            }

            public function analyze(string $sql, array $context = []): array
            {
                $this->count++;
                return [
                    'suggestions' => [['type' => 'index', 'suggestion' => 'Cache test', 'impact' => 'high', 'explanation' => '']],
                    'raw_response' => 'test',
                    'provider' => 'mock',
                ];
            }

            public function getName(): string { return 'mock'; }
            public function isAvailable(): bool { return true; }
        };

        $optimizer = new AIQueryOptimizer(['enabled' => true, 'cache_ttl' => 3600, 'rate_limit' => 100]);
        $optimizer->setProvider($mockProvider);

        // First call - hits provider
        $result1 = $optimizer->optimize('SELECT * FROM users WHERE id = 1');
        $this->assertFalse($result1['cached']);

        // Second call - should be cached
        $result2 = $optimizer->optimize('SELECT * FROM users WHERE id = 1');
        $this->assertTrue($result2['cached']);
    }

    // ---------------------------------------------------------------
    // AIQueryOptimizer: rate limiting
    // ---------------------------------------------------------------

    public function test_optimizer_rate_limits(): void
    {
        $mockProvider = new class implements AIProvider {
            public function analyze(string $sql, array $context = []): array
            {
                return [
                    'suggestions' => [['type' => 'test', 'suggestion' => 'test', 'impact' => 'low', 'explanation' => '']],
                    'raw_response' => 'ok',
                    'provider' => 'mock',
                ];
            }

            public function getName(): string { return 'mock'; }
            public function isAvailable(): bool { return true; }
        };

        // Rate limit of 2 requests per minute
        $optimizer = new AIQueryOptimizer(['enabled' => true, 'rate_limit' => 2]);
        $optimizer->setProvider($mockProvider);

        // First two calls should succeed
        $r1 = $optimizer->optimize('SELECT 1');
        $this->assertNotEmpty($r1['suggestions']);

        // Use different SQL to avoid cache
        $r2 = $optimizer->optimize('SELECT 2');
        $this->assertNotEmpty($r2['suggestions']);

        // Third call should be rate limited
        $r3 = $optimizer->optimize('SELECT 3');
        $this->assertEmpty($r3['suggestions']);
        $this->assertStringContainsString('Rate limit', $r3['message']);
    }

    // ---------------------------------------------------------------
    // AIQueryOptimizer: provider resolution
    // ---------------------------------------------------------------

    public function test_resolves_openai_provider_when_configured(): void
    {
        $optimizer = new AIQueryOptimizer([
            'enabled' => true,
            'provider' => 'openai',
            'api_key' => 'sk-test',
        ]);

        $this->assertInstanceOf(OpenAIProvider::class, $optimizer->getProvider());
    }

    public function test_resolves_null_provider_for_unknown_driver(): void
    {
        $optimizer = new AIQueryOptimizer([
            'enabled' => true,
            'provider' => 'unknown_provider',
        ]);

        $this->assertInstanceOf(NullProvider::class, $optimizer->getProvider());
    }

    public function test_resolves_null_provider_when_disabled(): void
    {
        $optimizer = new AIQueryOptimizer([
            'enabled' => false,
            'provider' => 'openai',
            'api_key' => 'sk-test',
        ]);

        $this->assertInstanceOf(NullProvider::class, $optimizer->getProvider());
    }

    // ---------------------------------------------------------------
    // Config toggle
    // ---------------------------------------------------------------

    public function test_enabled_config_toggle(): void
    {
        $enabled = new AIQueryOptimizer(['enabled' => true]);
        $disabled = new AIQueryOptimizer(['enabled' => false]);

        $this->assertTrue($enabled->isEnabled());
        $this->assertFalse($disabled->isEnabled());
    }

    // ---------------------------------------------------------------
    // Missing API key graceful handling
    // ---------------------------------------------------------------

    public function test_openai_without_key_returns_error(): void
    {
        $optimizer = new AIQueryOptimizer([
            'enabled' => true,
            'provider' => 'openai',
            'api_key' => '',
        ]);

        $result = $optimizer->optimize('SELECT * FROM users');

        $this->assertEmpty($result['suggestions']);
        $this->assertStringContainsString('not available', $result['message']);
    }

    // ---------------------------------------------------------------
    // Service provider registration
    // ---------------------------------------------------------------

    public function test_optimizer_registered_in_container(): void
    {
        $optimizer = app(AIQueryOptimizer::class);
        $this->assertInstanceOf(AIQueryOptimizer::class, $optimizer);
    }

    public function test_optimizer_disabled_by_default_in_config(): void
    {
        $optimizer = app(AIQueryOptimizer::class);
        $this->assertFalse($optimizer->isEnabled());
    }

    // ---------------------------------------------------------------
    // API route
    // ---------------------------------------------------------------

    public function test_ai_optimize_route_registered(): void
    {
        $routes = app('router')->getRoutes();
        $routeNames = collect($routes->getRoutes())->map(fn($r) => $r->getName())->filter()->toArray();

        $this->assertContains('query-lens.api.v2.ai-optimize', $routeNames);
    }

    // ---------------------------------------------------------------
    // OpenAI HTTP integration (with faked HTTP)
    // ---------------------------------------------------------------

    public function test_openai_provider_successful_api_call(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                ['type' => 'index', 'suggestion' => 'Add index on email', 'impact' => 'high', 'explanation' => 'Will speed up lookups'],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $provider = new OpenAIProvider('sk-test-key');
        $result = $provider->analyze('SELECT * FROM users WHERE email = ?');

        $this->assertCount(1, $result['suggestions']);
        $this->assertSame('Add index on email', $result['suggestions'][0]['suggestion']);
    }

    public function test_openai_provider_handles_api_error(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response('Unauthorized', 401),
        ]);

        $provider = new OpenAIProvider('sk-invalid-key');
        $result = $provider->analyze('SELECT * FROM users');

        $this->assertEmpty($result['suggestions']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_openai_provider_handles_network_exception(): void
    {
        Http::fake(fn() => throw new \Exception('Connection timeout'));

        $provider = new OpenAIProvider('sk-test-key');
        $result = $provider->analyze('SELECT * FROM users');

        $this->assertEmpty($result['suggestions']);
        $this->assertArrayHasKey('error', $result);
    }

    // ---------------------------------------------------------------
    // Contract interface compliance
    // ---------------------------------------------------------------

    public function test_null_provider_implements_contract(): void
    {
        $provider = new NullProvider();
        $this->assertInstanceOf(AIProvider::class, $provider);
    }

    public function test_openai_provider_implements_contract(): void
    {
        $provider = new OpenAIProvider('test');
        $this->assertInstanceOf(AIProvider::class, $provider);
    }
}
