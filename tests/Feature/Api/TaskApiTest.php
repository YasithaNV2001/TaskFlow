<?php

use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('authentication', function () {
    it('rejects requests without a token', function () {
        $this->getJson(route('api.tasks.index'))->assertUnauthorized();
    });

    it('issues a token for valid credentials and accepts it', function () {
        $response = $this->postJson(route('api.tokens.store'), [
            'email' => $this->user->email,
            'password' => 'password', // UserFactory default
            'device_name' => 'pest',
        ])->assertCreated()->assertJsonStructure(['token', 'token_type']);

        $this->withToken($response->json('token'))
            ->getJson(route('api.user'))
            ->assertOk()
            ->assertExactJson(['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email]);
    });

    it('rejects wrong credentials', function () {
        $this->postJson(route('api.tokens.store'), [
            'email' => $this->user->email,
            'password' => 'wrong-password',
            'device_name' => 'pest',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    });

    it('revokes the current token', function () {
        $token = $this->user->createToken('pest')->plainTextToken;

        $this->withToken($token)->deleteJson(route('api.tokens.destroy'))->assertNoContent();

        expect($this->user->tokens()->count())->toBe(0);
    });
});

describe('tasks', function () {
    beforeEach(fn () => Sanctum::actingAs($this->user));

    it('lists only the user\'s tasks as paginated JSON', function () {
        Task::factory()->count(3)->for($this->user)->create();
        Task::factory()->count(2)->create();

        $this->getJson(route('api.tasks.index', ['per_page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'description', 'status', 'status_label', 'priority', 'due_date', 'is_overdue', 'created_at', 'updated_at']],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonMissingPath('data.0.user_id');
    });

    it('filters by status', function () {
        Task::factory()->for($this->user)->done()->create();
        Task::factory()->for($this->user)->create(['status' => 'todo']);

        $this->getJson(route('api.tasks.index', ['status' => 'done']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'done');
    });

    it('creates a task and returns 201', function () {
        $this->postJson(route('api.tasks.store'), [
            'title' => 'From the API',
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => '2026-12-24',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'From the API')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.due_date', '2026-12-24');

        expect($this->user->tasks()->count())->toBe(1);
    });

    it('returns validation errors as JSON', function () {
        $this->postJson(route('api.tasks.store'), ['status' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status', 'priority']);
    });

    it('shows, updates and deletes the user\'s own task', function () {
        $task = Task::factory()->for($this->user)->create(['title' => 'Before']);

        $this->getJson(route('api.tasks.show', $task))->assertOk()->assertJsonPath('data.id', $task->id);

        $this->putJson(route('api.tasks.update', $task), [
            'title' => 'After',
            'status' => 'done',
            'priority' => 'low',
        ])->assertOk()->assertJsonPath('data.title', 'After');

        $this->deleteJson(route('api.tasks.destroy', $task))->assertNoContent();
        $this->assertModelMissing($task);
    });

    it('forbids access to another user\'s task', function () {
        $task = Task::factory()->create();
        $valid = ['title' => 'x', 'status' => 'todo', 'priority' => 'low'];

        $this->getJson(route('api.tasks.show', $task))->assertForbidden();
        $this->putJson(route('api.tasks.update', $task), $valid)->assertForbidden();
        $this->deleteJson(route('api.tasks.destroy', $task))->assertForbidden();
        $this->assertModelExists($task);
    });

    it('returns 404 JSON for missing tasks', function () {
        $this->getJson(route('api.tasks.show', 999))->assertNotFound();
    });
});
