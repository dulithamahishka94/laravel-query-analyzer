<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Listeners;

use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use GladeHQ\QueryLens\QueryAnalyzer;

class TransactionListener
{
    protected QueryAnalyzer $analyzer;

    /**
     * Stack of active transactions keyed by connection name.
     * Each entry holds: [id, connection, started_at, nesting_depth, query_count].
     *
     * @var array<string, array<int, array>>
     */
    protected array $transactionStacks = [];

    /**
     * Completed transactions waiting to be stored.
     *
     * @var array<int, array>
     */
    protected array $completed = [];

    public function __construct(QueryAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function register(): void
    {
        Event::listen(TransactionBeginning::class, [$this, 'handleBeginEvent']);
        Event::listen(TransactionCommitted::class, [$this, 'handleCommitEvent']);
        Event::listen(TransactionRolledBack::class, [$this, 'handleRollbackEvent']);
    }

    // -- Event handlers (delegate to string-based methods for testability) --

    public function handleBeginEvent(TransactionBeginning $event): void
    {
        $this->beginTransaction($event->connectionName);
    }

    public function handleCommitEvent(TransactionCommitted $event): void
    {
        $this->commitTransaction($event->connectionName);
    }

    public function handleRollbackEvent(TransactionRolledBack $event): void
    {
        $this->rollbackTransaction($event->connectionName);
    }

    // -- Core transaction tracking methods --

    public function beginTransaction(string $connection): void
    {
        $depth = $this->getDepth($connection) + 1;

        $transaction = [
            'id' => (string) Str::orderedUuid(),
            'connection' => $connection,
            'started_at' => microtime(true),
            'nesting_depth' => $depth,
            'query_count' => 0,
            'request_id' => $this->analyzer->getRequestId(),
        ];

        $this->transactionStacks[$connection][] = $transaction;

        // Set the transaction ID on the analyzer so queries can be tagged
        $this->analyzer->setCurrentTransactionId($transaction['id']);
    }

    public function commitTransaction(string $connection): void
    {
        $this->finalizeTransaction($connection, 'committed');
    }

    public function rollbackTransaction(string $connection): void
    {
        $this->finalizeTransaction($connection, 'rolled_back');
    }

    protected function finalizeTransaction(string $connection, string $status): void
    {
        $stack = $this->transactionStacks[$connection] ?? [];

        if (empty($stack)) {
            return;
        }

        $transaction = array_pop($stack);
        $this->transactionStacks[$connection] = $stack;

        $endedAt = microtime(true);
        $durationMs = ($endedAt - $transaction['started_at']) * 1000;

        $completed = [
            'id' => $transaction['id'],
            'connection' => $connection,
            'started_at' => $transaction['started_at'],
            'ended_at' => $endedAt,
            'duration_ms' => round($durationMs, 3),
            'status' => $status,
            'nesting_depth' => $transaction['nesting_depth'],
            'query_count' => $transaction['query_count'],
            'request_id' => $transaction['request_id'] ?? $this->analyzer->getRequestId(),
        ];

        $this->completed[] = $completed;

        // Update the analyzer's transaction ID to the parent transaction, if any
        $parentTransaction = end($stack);
        $this->analyzer->setCurrentTransactionId(
            $parentTransaction ? $parentTransaction['id'] : null
        );
    }

    /**
     * Increment the query count for the current transaction on a connection.
     */
    public function incrementQueryCount(string $connection): void
    {
        $stack = $this->transactionStacks[$connection] ?? [];
        if (empty($stack)) {
            return;
        }

        $lastIndex = count($stack) - 1;
        $this->transactionStacks[$connection][$lastIndex]['query_count']++;
    }

    /**
     * Get the current transaction ID for a connection, or null if none active.
     */
    public function getCurrentTransactionId(string $connection): ?string
    {
        $stack = $this->transactionStacks[$connection] ?? [];
        if (empty($stack)) {
            return null;
        }

        return end($stack)['id'] ?? null;
    }

    /**
     * Get the current nesting depth for a connection.
     */
    public function getDepth(string $connection): int
    {
        return count($this->transactionStacks[$connection] ?? []);
    }

    /**
     * Check if any transaction is active on any connection.
     */
    public function hasActiveTransactions(): bool
    {
        foreach ($this->transactionStacks as $stack) {
            if (!empty($stack)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all completed transactions and optionally flush the buffer.
     */
    public function getCompletedTransactions(bool $flush = false): array
    {
        $completed = $this->completed;

        if ($flush) {
            $this->completed = [];
        }

        return $completed;
    }

    /**
     * Get all active (in-flight) transactions across all connections.
     */
    public function getActiveTransactions(): array
    {
        $active = [];

        foreach ($this->transactionStacks as $connection => $stack) {
            foreach ($stack as $transaction) {
                $active[] = array_merge($transaction, [
                    'status' => 'active',
                    'duration_ms' => round((microtime(true) - $transaction['started_at']) * 1000, 3),
                ]);
            }
        }

        return $active;
    }

    /**
     * Reset all state. Used for testing and request isolation.
     */
    public function reset(): void
    {
        $this->transactionStacks = [];
        $this->completed = [];
    }
}
