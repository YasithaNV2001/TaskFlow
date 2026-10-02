<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| REST API, served under /api. Route names are prefixed with "api."
| so they don't clash with the web routes (e.g. api.tasks.index vs tasks.index).
*/

Route::name('api.')->group(function () {
    // Log in: max 6 attempts per minute per IP to slow down password guessing
    Route::post('tokens', [TokenController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('tokens.store');

    // Everything below needs "Authorization: Bearer <token>"
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::delete('tokens/current', [TokenController::class, 'destroy'])->name('tokens.destroy');

        Route::get('user', fn (Request $request) => $request->user()?->only(['id', 'name', 'email']))
            ->name('user');

        // index, store, show, update, destroy
        Route::apiResource('tasks', TaskController::class);
    });
});
