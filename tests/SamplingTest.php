<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Orchestra\Testbench\TestCase;

class SamplingTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function makeAnalyzer(float $samplingRate): array
    {
        $storage = new InMemoryQueryStorage();
        $analyzer = new QueryAnalyzer(
            [
                'sampling_rate' => $samplingRate,
                'performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0],
            ],
            $storage
        );

        return [$analyzer, $storage];
    }

    public function test_sampling_rate_zero_records_nothing(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(0.0);

        $analyzer->setRequestId('req-1');
        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);
        $analyzer->recordQuery('SELECT * FROM posts', [], 0.1);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_sampling_rate_one_records_everything(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(1.0);

        $analyzer->setRequestId('req-1');
        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);
        $analyzer->recordQuery('SELECT * FROM posts', [], 0.1);

        $this->assertCount(2, $storage->getAllQueries());
    }

    public function test_sampling_decision_is_per_request_not_per_query(): void
    {
        // Use a rate that will give us a mix of sampled and not-sampled requests
        // over many iterations, but for a single request all queries must be
        // either sampled or not sampled
        $storage = new InMemoryQueryStorage();
        $analyzer = new QueryAnalyzer(
            [
                'sampling_rate' => 0.5,
                'performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0],
            ],
            $storage
        );

        // Run multiple requests and verify consistency within each request
        for ($i = 0; $i < 50; $i++) {
            $storage->clear();
            $analyzer->setRequestId("req-$i");

            $analyzer->recordQuery('SELECT 1', [], 0.05);
            $analyzer->recordQuery('SELECT 2', [], 0.05);
            $analyzer->recordQuery('SELECT 3', [], 0.05);

            $count = count($storage->getAllQueries());
            // Either all 3 are recorded or none -- never partial
            $this->assertTrue(
                $count === 0 || $count === 3,
                "Expected 0 or 3 queries, got $count for request req-$i"
            );
        }
    }

    public function test_sampling_decision_resets_on_new_request_id(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(0.0);

        $analyzer->setRequestId('req-1');
        $this->assertFalse($analyzer->isSampled());

        // Change to sampling rate 1.0 by creating a new analyzer
        $analyzer2 = new QueryAnalyzer(
            [
                'sampling_rate' => 1.0,
                'performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0],
            ],
            $storage
        );
        $analyzer2->setRequestId('req-2');
        $this->assertTrue($analyzer2->isSampled());
    }

    public function test_sampling_rate_defaults_to_one_when_not_configured(): void
    {
        $storage = new InMemoryQueryStorage();
        $analyzer = new QueryAnalyzer(
            ['performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0]],
            $storage
        );

        $analyzer->setRequestId('req-1');
        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);

        $this->assertCount(1, $storage->getAllQueries());
    }

    public function test_sampling_rate_zero_prevents_cache_interaction_recording(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(0.0);

        $analyzer->setRequestId('req-1');
        $analyzer->recordCacheInteraction('hit', 'some_key', ['tag1']);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_sampling_rate_one_allows_cache_interaction_recording(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(1.0);

        $analyzer->setRequestId('req-1');
        $analyzer->recordCacheInteraction('hit', 'some_key', ['tag1']);

        $this->assertCount(1, $storage->getAllQueries());
    }

    public function test_is_sampled_is_lazy_evaluated_when_no_request_id_set(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(1.0);

        // Do NOT call setRequestId -- isSampled should still work
        $this->assertTrue($analyzer->isSampled());
    }

    public function test_is_sampled_returns_false_for_zero_rate_without_request_id(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(0.0);

        $this->assertFalse($analyzer->isSampled());
    }

    public function test_sampling_at_partial_rate_produces_statistical_distribution(): void
    {
        $sampledCount = 0;
        $iterations = 1000;

        for ($i = 0; $i < $iterations; $i++) {
            $storage = new InMemoryQueryStorage();
            $analyzer = new QueryAnalyzer(
                [
                    'sampling_rate' => 0.5,
                    'performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0],
                ],
                $storage
            );

            $analyzer->setRequestId("req-$i");

            if ($analyzer->isSampled()) {
                $sampledCount++;
            }
        }

        // With 50% sampling rate over 1000 iterations, expect between 35% and 65%
        $ratio = $sampledCount / $iterations;
        $this->assertGreaterThan(0.35, $ratio, "Sampling ratio $ratio is suspiciously low");
        $this->assertLessThan(0.65, $ratio, "Sampling ratio $ratio is suspiciously high");
    }

    public function test_disabled_recording_takes_precedence_over_sampling(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(1.0);

        $analyzer->setRequestId('req-1');
        $analyzer->disableRecording();
        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_config_integration_reads_sampling_rate(): void
    {
        $this->app['config']->set('query-lens.sampling_rate', 0.0);
        $this->app['config']->set('query-lens.enabled', true);

        $analyzer = new QueryAnalyzer(
            $this->app['config']['query-lens'],
            new InMemoryQueryStorage()
        );

        $analyzer->setRequestId('req-1');
        $this->assertFalse($analyzer->isSampled());
    }
}
