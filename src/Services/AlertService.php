<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use GladeHQ\QueryLens\Models\Alert;
use GladeHQ\QueryLens\Models\AlertLog;
use GladeHQ\QueryLens\Models\AnalyzedQuery;

class AlertService
{
    protected array $config;
    protected ?\Illuminate\Database\Eloquent\Collection $cachedAlerts = null;

    public function __construct()
    {
        $this->config = config('query-lens.alerts', []);
    }

    public function checkAlerts(AnalyzedQuery $query): void
    {
        $alerts = $this->getEnabledAlerts();

        foreach ($alerts as $alert) {
            $this->checkAlert($alert, $query);
        }
    }

    protected function getEnabledAlerts(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->cachedAlerts === null) {
            $this->cachedAlerts = Alert::enabled()->get();
        }

        return $this->cachedAlerts;
    }

    public function clearAlertCache(): void
    {
        $this->cachedAlerts = null;
    }

    protected function checkAlert(Alert $alert, AnalyzedQuery $query): void
    {
        if (!$alert->canTrigger()) {
            return;
        }

        $shouldTrigger = match ($alert->type) {
            'slow_query' => $this->checkSlowQueryAlert($alert, $query),
            'n_plus_one' => $this->checkNPlusOneAlert($alert, $query),
            'threshold' => $this->checkThresholdAlert($alert, $query),
            'error_rate' => $this->checkErrorRateAlert($alert, $query),
            default => false,
        };

        if ($shouldTrigger) {
            $this->triggerAlert($alert, $query);
        }
    }

    protected function checkSlowQueryAlert(Alert $alert, AnalyzedQuery $query): bool
    {
        $threshold = $alert->getCondition('threshold', 1.0);
        return $query->time >= $threshold;
    }

    protected function checkNPlusOneAlert(Alert $alert, AnalyzedQuery $query): bool
    {
        if (!$query->is_n_plus_one) {
            return false;
        }

        $minCount = $alert->getCondition('min_count', 5);
        return $query->n_plus_one_count >= $minCount;
    }

    protected function checkThresholdAlert(Alert $alert, AnalyzedQuery $query): bool
    {
        $metric = $alert->getCondition('metric', 'time');
        $threshold = $alert->getCondition('threshold', 1.0);
        $operator = $alert->getCondition('operator', '>=');

        $value = match ($metric) {
            'time' => $query->time,
            'complexity' => $query->complexity_score,
            default => 0,
        };

        return match ($operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            '=' => $value == $threshold,
            default => $value >= $threshold,
        };
    }

    protected function checkErrorRateAlert(Alert $alert, AnalyzedQuery $query): bool
    {
        if (!$query->hasIssues()) {
            return false;
        }

        $minIssues = $alert->getCondition('min_issues', 1);
        return count($query->getIssues()) >= $minIssues;
    }

    protected function triggerAlert(Alert $alert, AnalyzedQuery $query): void
    {
        $message = $this->buildAlertMessage($alert, $query);
        $context = $this->buildAlertContext($alert, $query);

        // Create log entry
        AlertLog::createFromAlert($alert, $message, $context, $query->id);

        // Mark alert as triggered
        $alert->markTriggered();

        // Send notifications
        $this->sendNotifications($alert, $message, $context);

        Log::info('Query Analyzer Alert Triggered', [
            'alert' => $alert->name,
            'type' => $alert->type,
            'query_id' => $query->id,
        ]);
    }

    protected function buildAlertMessage(Alert $alert, AnalyzedQuery $query): string
    {
        return match ($alert->type) {
            'slow_query' => sprintf(
                'Slow query detected: %.4fs (threshold: %.4fs)',
                $query->time,
                $alert->getCondition('threshold', 1.0)
            ),
            'n_plus_one' => sprintf(
                'N+1 query pattern detected: %d similar queries',
                $query->n_plus_one_count
            ),
            'threshold' => sprintf(
                'Query threshold exceeded: %s = %.4f',
                $alert->getCondition('metric', 'time'),
                $query->time
            ),
            'error_rate' => sprintf(
                'Query with %d issues detected',
                count($query->getIssues())
            ),
            default => 'Query alert triggered',
        };
    }

    protected function buildAlertContext(Alert $alert, AnalyzedQuery $query): array
    {
        return [
            'sql' => $query->sql,
            'time' => $query->time,
            'connection' => $query->connection,
            'type' => $query->type,
            'issues' => $query->getIssues(),
            'origin' => $query->origin,
            'request_id' => $query->request_id,
        ];
    }

    protected function sendNotifications(Alert $alert, string $message, array $context): void
    {
        foreach ($alert->channels as $channel) {
            try {
                match ($channel) {
                    'log' => $this->sendLogNotification($alert, $message, $context),
                    'mail' => $this->sendMailNotification($alert, $message, $context),
                    'slack' => $this->sendSlackNotification($alert, $message, $context),
                    default => null,
                };
            } catch (\Exception $e) {
                Log::error("Query Analyzer: Failed to send {$channel} notification", [
                    'error' => $e->getMessage(),
                    'alert' => $alert->name,
                ]);
            }
        }
    }

    protected function sendLogNotification(Alert $alert, string $message, array $context): void
    {
        Log::warning("Query Analyzer Alert [{$alert->name}]: {$message}", [
            'sql' => $context['sql'] ?? '',
            'time' => $context['time'] ?? 0,
            'origin' => $context['origin'] ?? [],
        ]);
    }

    protected function sendMailNotification(Alert $alert, string $message, array $context): void
    {
        $to = $this->config['mail_to'] ?? null;
        if (!$to) {
            return;
        }

        Mail::to($to)->send(new \GladeHQ\QueryLens\Mail\AlertTriggered($alert, $message, $context));
    }

    protected function sendSlackNotification(Alert $alert, string $message, array $context): void
    {
        $webhook = $this->config['slack_webhook'] ?? null;
        if (!$webhook) {
            return;
        }

        Http::post($webhook, [
            'text' => "Query Analyzer Alert: {$alert->name}",
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        ['title' => 'Message', 'value' => $message, 'short' => false],
                        ['title' => 'SQL', 'value' => substr($context['sql'] ?? '', 0, 500), 'short' => false],
                        ['title' => 'Time', 'value' => ($context['time'] ?? 0) . 's', 'short' => true],
                        ['title' => 'Connection', 'value' => $context['connection'] ?? 'default', 'short' => true],
                    ],
                ],
            ],
        ]);
    }

    protected function formatMailMessage(Alert $alert, string $message, array $context): string
    {
        $sql = $context['sql'] ?? '';
        $time = $context['time'] ?? 0;
        $origin = $context['origin'] ?? [];

        return <<<TEXT
Query Analyzer Alert: {$alert->name}

{$message}

SQL Query:
{$sql}

Execution Time: {$time}s

Origin: {$origin['file']}:{$origin['line']}

Request ID: {$context['request_id']}
TEXT;
    }

    public function getRecentAlerts(int $hours = 24, int $limit = 50): array
    {
        return AlertLog::recent($hours)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getAlertStats(): array
    {
        $last24h = AlertLog::recent(24)->count();
        $lastHour = AlertLog::recent(1)->count();

        $byType = AlertLog::recent(24)
            ->selectRaw('alert_type, COUNT(*) as count')
            ->groupBy('alert_type')
            ->pluck('count', 'alert_type')
            ->toArray();

        return [
            'last_24h' => $last24h,
            'last_hour' => $lastHour,
            'by_type' => $byType,
        ];
    }
}
