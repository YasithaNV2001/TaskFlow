<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property Carbon|null $due_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
// Only these columns can be mass-assigned (e.g. Task::create($data)); user_id is set through the relationship
#[Fillable(['title', 'description', 'status', 'priority', 'due_date'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * Default values for new tasks (match the column defaults in the migration).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'todo',
        'priority' => 'medium',
    ];

    /**
     * Casts convert database values to PHP types and back.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            // Serialized as "2026-10-02" so it fits straight into <input type="date">
            'due_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Relationship: every task belongs to one user (tasks.user_id → users.id).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the task is past its due date and not finished yet.
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->status !== TaskStatus::Done
            && $this->due_date->isBefore(today());
    }

    /**
     * Query scope: Task::query()->search('report') matches title or description.
     *
     * @param  Builder<Task>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || $term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Query scope: apply the validated list filters (see FilterTasksRequest).
     *
     * @param  Builder<Task>  $query
     * @param  array{search?: string, status?: string, priority?: string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority) => $query->where('priority', $priority));
    }

    /**
     * Query scope: tasks with a due date first (soonest first), then newest.
     *
     * @param  Builder<Task>  $query
     */
    public function scopeOrderByDeadline(Builder $query): void
    {
        $query->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->latest();
    }

    /**
     * Query scope: tasks that are past due and not done.
     *
     * @param  Builder<Task>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->where('status', '!=', TaskStatus::Done);
    }
}
