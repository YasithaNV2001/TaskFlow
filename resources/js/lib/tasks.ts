import type { Task, TaskPriority, TaskStatus } from '@/types';

/** Today's date as "YYYY-MM-DD" in the user's local time zone. */
function todayIso(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60_000;

    return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}

/** Mirrors Task::isOverdue() on the server. */
export function isOverdue(task: Pick<Task, 'due_date' | 'status'>): boolean {
    return (
        task.due_date !== null &&
        task.status !== 'done' &&
        task.due_date < todayIso()
    );
}

/** "2026-10-02" → "Oct 2, 2026" */
export function formatDate(date: string): string {
    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

export const priorityClasses: Record<TaskPriority, string> = {
    low: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    medium: 'bg-amber-500/10 text-amber-700 dark:text-amber-400',
    high: 'bg-red-500/10 text-red-700 dark:text-red-400',
};

export const statusLabels: Record<TaskStatus, string> = {
    todo: 'To do',
    in_progress: 'In progress',
    done: 'Done',
};
