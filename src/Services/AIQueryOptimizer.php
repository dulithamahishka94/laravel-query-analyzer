<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use GladeHQ\QueryLens\Contracts\AIProvider;
use GladeHQ\QueryLens\Services\AI\NullProvider;
use GladeHQ\QueryLens\Services\AI\OpenAIProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIQueryOptimizer
{
    protected AIProvider $provider;
    protected array $config;
    protected int $maxRequestsPerMinute;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->maxRequestsPerMinute = $config['rate_limit'] ?? 10;
        $this->provider = $this->resolveProvider($config);
    }

    /**
     * Analyze a SQL query and return AI-powered optimization suggestions.
     */
    public function optimize(string $sql, array $context = []): array
    {
        if (!$this->isEnabled()) {
            return $this->emptyResult('AI optimization is disabled');
        }

        if (!$this->provider->isAvailable()) {
            return $this->emptyResult('AI provider is not available');
        }

        // Check cache first
        $cacheKey = $this->getCacheKey($sql, $context);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return array_merge($cached, ['cached' => true]);
        }

        // Rate limiting
        if (!$this->checkRateLimit()) {
            return $this->emptyResult('Rate limit exceeded. Try again later.');
        }

        $result = $this->provider->analyze($sql, $context);

        // Cache successful responses
        if (!empty($result['suggestions']) && empty($result['error'])) {
            $ttl = $this->config['cache_ttl'] ?? 3600;
            Cache::put($cacheKey, $result, $ttl);
        }

        $result['cached'] = false;

        return $result;
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    public function getProvider(): AIProvider
    {
        return $this->provider;
    }

    public function setProvider(AIProvider $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    protected function resolveProvider(array $config): AIProvider
    {
        if (!($config['enabled'] ?? false)) {
            return new NullProvider();
        }

        $driverName = $config['provider'] ?? 'null';

        return match ($driverName) {
            'openai' => new OpenAIProvider(
                apiKey: $config['api_key'] ?? '',
                model: $config['model'] ?? 'gpt-4o-mini',
                endpoint: $config['endpoint'] ?? 'https://api.openai.com/v1/chat/completions',
            ),
            default => new NullProvider(),
        };
    }

    protected function getCacheKey(string $sql, array $context): string
    {
        // Normalize SQL for caching (same query pattern = same cache key)
        $normalized = preg_replace('/\s+/', ' ', trim($sql));
        $contextHash = md5(json_encode($context));

        return 'query_lens_ai:' . md5($normalized . '|' . $contextHash);
    }

    protected function checkRateLimit(): bool
    {
        $key = 'query_lens_ai_rate:' . now()->format('Y-m-d-H-i');
        $current = (int) Cache::get($key, 0);

        if ($current >= $this->maxRequestsPerMinute) {
            return false;
        }

        Cache::put($key, $current + 1, 120);

        return true;
    }

    protected function emptyResult(string $message): array
    {
        return [
            'suggestions' => [],
            'raw_response' => null,
            'provider' => $this->provider->getName(),
            'message' => $message,
            'cached' => false,
        ];
    }
}
