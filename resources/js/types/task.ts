export type TaskStatus = 'todo' | 'in_progress' | 'done';
export type TaskPriority = 'low' | 'medium' | 'high';

/** A task as serialized by the App\Models\Task Eloquent model. */
export type Task = {
    id: number;
    title: string;
    description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string | null; // "YYYY-MM-DD"
    created_at: string;
    updated_at: string;
};

/** Value/label pairs from TaskStatus::options() and TaskPriority::options(). */
export type Option<T extends string = string> = {
    value: T;
    label: string;
};

/** The JSON shape of a Laravel LengthAwarePaginator (->paginate()). */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};
