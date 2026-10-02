<?php

namespace App\Enums;

/**
 * A backed enum: a fixed set of allowed values stored as strings in the database.
 * Using an enum instead of loose strings means a typo like 'Done' or 'complete'
 * is caught by validation and static analysis instead of silently saved.
 */
enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'To do',
            self::InProgress => 'In progress',
            self::Done => 'Done',
        };
    }

    /**
     * Value/label pairs for dropdowns in the Vue frontend.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
