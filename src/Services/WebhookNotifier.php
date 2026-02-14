<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookNotifier
{
    /**
     * Send regression data to a webhook URL.
     */
    public function notify(string $url, array $regressions, array $options = []): bool
    {
        $headers = $options['headers'] ?? [];
        $payload = $this->buildPayload($regressions, $options);

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('QueryLens: Regression webhook sent successfully', ['url' => $url]);
                return true;
            }

            Log::warning('QueryLens: Regression webhook failed', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('QueryLens: Regression webhook error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build webhook payload suitable for Slack/Discord/Teams incoming webhooks.
     */
    public function buildPayload(array $regressions, array $options = []): array
    {
        $regressionList = $regressions['regressions'] ?? [];
        $summary = $regressions['summary'] ?? [];

        $hasCritical = $summary['has_critical'] ?? false;
        $regressionCount = $summary['regression_count'] ?? count($regressionList);

        $title = $hasCritical
            ? "CRITICAL: {$regressionCount} query regression(s) detected"
            : "WARNING: {$regressionCount} query regression(s) detected";

        $details = [];
        foreach ($regressionList as $r) {
            $arrow = $r['change_pct'] > 0 ? '+' : '';
            $pct = round($r['change_pct'] * 100, 1);
            $details[] = sprintf(
                '[%s] %s: %.4f -> %.4f (%s%s%%)',
                strtoupper($r['severity']),
                $r['label'],
                $r['previous'],
                $r['current'],
                $arrow,
                $pct
            );
        }

        $detailText = implode("\n", $details);
        $period = $summary['period'] ?? 'daily';

        return [
            'text' => $title,
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*{$title}*\nPeriod: {$period} | Threshold: " . (($summary['threshold'] ?? 0.2) * 100) . '%',
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "```\n{$detailText}\n```",
                    ],
                ],
            ],
            'query_lens' => [
                'regressions' => $regressionList,
                'summary' => $summary,
            ],
        ];
    }
}
