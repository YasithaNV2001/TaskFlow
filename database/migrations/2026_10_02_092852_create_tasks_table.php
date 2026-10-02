<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: create the `tasks` table.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            // Foreign key to users.id; deleting a user also deletes their tasks
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // Values come from the App\Enums\TaskStatus and TaskPriority enums
            $table->string('status')->default('todo');
            $table->string('priority')->default('medium');
            $table->date('due_date')->nullable();
            $table->timestamps();

            // Speeds up the most common query: "this user's tasks with this status"
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations (used by `php artisan migrate:rollback`).
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
