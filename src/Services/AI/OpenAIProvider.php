<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services\AI;

use GladeHQ\QueryLens\Contracts\AIProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI-compatible provider. Works with OpenAI API, Azure OpenAI, or
 * any local LLM that exposes a compatible chat completions endpoint.
 */
class OpenAIProvider implements AIProvider
{
    protected string $apiKey;
    protected string $model;
    protected string $endpoint;

    public function __construct(string $apiKey, string $model = 'gpt-4o-mini', string $endpoint = 'https://api.openai.com/v1/chat/completions')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->endpoint = $endpoint;
    }

    public function analyze(string $sql, array $context = []): array
    {
        if (!$this->isAvailable()) {
            return [
                'suggestions' => [],
                'raw_response' => null,
                'provider' => $this->getName(),
                'error' => 'API key not configured',
            ];
        }

        $prompt = $this->buildPrompt($sql, $context);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->endpoint, [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a database performance expert. Analyze SQL queries and provide specific, actionable optimization suggestions. Respond ONLY with valid JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 1000,
                ]);

            if (!$response->successful()) {
                Log::warning('QueryLens AI: API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'suggestions' => [],
                    'raw_response' => $response->body(),
                    'provider' => $this->getName(),
                    'error' => 'API request failed with status ' . $response->status(),
                ];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return [
                'suggestions' => $this->parseResponse($content),
                'raw_response' => $content,
                'provider' => $this->getName(),
            ];
        } catch (\Exception $e) {
            Log::error('QueryLens AI: Provider error', ['error' => $e->getMessage()]);

            return [
                'suggestions' => [],
                'raw_response' => null,
                'provider' => $this->getName(),
                'error' => $e->getMessage(),
            ];
        }
    }

    public function buildPrompt(string $sql, array $context = []): string
    {
        $parts = ["Analyze this SQL query and suggest optimizations:\n\nSQL: {$sql}"];

        if (!empty($context['explain'])) {
            $explain = is_array($context['explain']) ? json_encode($context['explain'], JSON_PRETTY_PRINT) : $context['explain'];
            $parts[] = "\nEXPLAIN output:\n{$explain}";
        }

        if (!empty($context['schema'])) {
            $schema = is_array($context['schema']) ? json_encode($context['schema'], JSON_PRETTY_PRINT) : $context['schema'];
            $parts[] = "\nTable schema:\n{$schema}";
        }

        if (isset($context['frequency'])) {
            $parts[] = "\nQuery frequency: {$context['frequency']} executions per day";
        }

        if (isset($context['avg_duration'])) {
            $parts[] = "\nAverage duration: {$context['avg_duration']}ms";
        }

        if (!empty($context['indexes'])) {
            $indexes = is_array($context['indexes']) ? json_encode($context['indexes'], JSON_PRETTY_PRINT) : $context['indexes'];
            $parts[] = "\nExisting indexes:\n{$indexes}";
        }

        $parts[] = "\nRespond with a JSON array of suggestion objects, each with: type (index|rewrite|schema|config), suggestion (string), impact (high|medium|low), explanation (string).";

        return implode("\n", $parts);
    }

    public function parseResponse(string $content): array
    {
        // Try to extract JSON from the response
        $content = trim($content);

        // Remove markdown code blocks if present
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to find a JSON array within the response
            if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (!is_array($decoded)) {
            return [];
        }

        // Normalize: if it's a flat array of suggestion objects, validate them
        $suggestions = [];
        $items = isset($decoded[0]) ? $decoded : [$decoded];

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['suggestion'])) {
                continue;
            }

            $suggestions[] = [
                'type' => $item['type'] ?? 'general',
                'suggestion' => $item['suggestion'],
                'impact' => $item['impact'] ?? 'medium',
                'explanation' => $item['explanation'] ?? '',
            ];
        }

        return $suggestions;
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
