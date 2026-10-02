<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query-string filters for listing tasks (?search=…&status=…&priority=…&per_page=…).
 * Shared by the web page and the REST API so both behave the same way.
 */
class FilterTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array{search?: string, status?: string, priority?: string}
     */
    public function filters(): array
    {
        /** @var array{search?: string, status?: string, priority?: string} */
        return array_filter(
            $this->safe()->only(['search', 'status', 'priority']),
            fn ($value) => $value !== null && $value !== '',
        );
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 10);
    }
}
