<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use GladeHQ\QueryLens\Models\Alert;
use GladeHQ\QueryLens\Models\AlertLog;
use GladeHQ\QueryLens\Services\AlertService;

class AlertController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function index(): JsonResponse
    {
        $alerts = Alert::orderByDesc('created_at')->get();

        return response()->json([
            'alerts' => $alerts,
            'stats' => $this->alertService->getAlertStats(),
            'types' => Alert::getAvailableTypes(),
            'channels' => Alert::getAvailableChannels(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:slow_query,threshold,n_plus_one,error_rate',
            'enabled' => 'boolean',
            'conditions' => 'required|array',
            'channels' => 'required|array',
            'channels.*' => 'string|in:log,mail,slack',
            'cooldown_minutes' => 'integer|min:1|max:1440',
        ]);

        $alert = Alert::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'enabled' => $validated['enabled'] ?? true,
            'conditions' => $validated['conditions'],
            'channels' => $validated['channels'],
            'cooldown_minutes' => $validated['cooldown_minutes'] ?? 5,
        ]);

        return response()->json([
            'message' => 'Alert created successfully',
            'alert' => $alert,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $alert = Alert::with('logs')->findOrFail($id);

        return response()->json([
            'alert' => $alert,
            'recent_logs' => $alert->logs()->orderByDesc('created_at')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $alert = Alert::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:slow_query,threshold,n_plus_one,error_rate',
            'enabled' => 'sometimes|boolean',
            'conditions' => 'sometimes|array',
            'channels' => 'sometimes|array',
            'channels.*' => 'string|in:log,mail,slack',
            'cooldown_minutes' => 'sometimes|integer|min:1|max:1440',
        ]);

        $alert->update($validated);

        return response()->json([
            'message' => 'Alert updated successfully',
            'alert' => $alert->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $alert = Alert::findOrFail($id);
        $alert->delete();

        return response()->json([
            'message' => 'Alert deleted successfully',
        ]);
    }

    public function logs(int $id): JsonResponse
    {
        $alert = Alert::findOrFail($id);

        $logs = $alert->logs()
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($logs);
    }

    public function clearLogs(): JsonResponse
    {
        AlertLog::truncate();
        
        // Store the last cleared timestamp in cache
        cache()->put('query-lens.alerts.last_cleared_at', now()->toIso8601String());

        return response()->json([
            'message' => 'Alert logs cleared successfully',
            'last_cleared_at' => now()->toIso8601String(),
        ]);
    }

    public function recentLogs(Request $request): JsonResponse
    {
        $hours = (int) $request->query('hours', 24);
        $limit = (int) $request->query('limit', 50);

        $logs = AlertLog::recent($hours)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'logs' => $logs,
            'stats' => $this->alertService->getAlertStats(),
            'last_cleared_at' => cache()->get('query-lens.alerts.last_cleared_at'),
        ]);
    }

    public function toggle(int $id): JsonResponse
    {
        $alert = Alert::findOrFail($id);
        $alert->update(['enabled' => !$alert->enabled]);

        return response()->json([
            'message' => $alert->enabled ? 'Alert enabled' : 'Alert disabled',
            'alert' => $alert,
        ]);
    }

    public function test(int $id): JsonResponse
    {
        $alert = Alert::findOrFail($id);

        // Create a mock context to test the alert
        AlertLog::create([
            'alert_id' => $alert->id,
            'query_id' => null,
            'alert_type' => $alert->type,
            'alert_name' => $alert->name,
            'message' => 'Test alert triggered manually',
            'context' => ['test' => true],
            'notified_channels' => $alert->channels,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Test alert sent successfully',
        ]);
    }
}
