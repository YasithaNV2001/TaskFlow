<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Single-action ("invokable") controller: one class, one job, handled by __invoke().
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tasks = $request->user()->tasks();

        // One grouped query instead of three separate COUNTs: SELECT status, COUNT(*) … GROUP BY status
        $countsByStatus = (clone $tasks)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => (int) $countsByStatus->sum(),
                'todo' => (int) ($countsByStatus[TaskStatus::Todo->value] ?? 0),
                'in_progress' => (int) ($countsByStatus[TaskStatus::InProgress->value] ?? 0),
                'done' => (int) ($countsByStatus[TaskStatus::Done->value] ?? 0),
                'overdue' => (clone $tasks)->overdue()->count(),
            ],
            // The next five unfinished tasks with a due date
            'upcoming' => (clone $tasks)
                ->where('status', '!=', TaskStatus::Done)
                ->whereNotNull('due_date')
                ->orderBy('due_date')
                ->limit(5)
                ->get(['id', 'title', 'priority', 'status', 'due_date']),
        ]);
    }
}
