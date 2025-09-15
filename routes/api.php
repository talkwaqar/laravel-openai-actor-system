<?php

declare(strict_types=1);

use App\Http\Controllers\ActorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Documentation and Health
Route::get('/health', [ActorController::class, 'health'])->name('api.health');
Route::get('/docs', [ActorController::class, 'documentation'])->name('api.docs');

// Actor API Routes
Route::prefix('actors')->name('api.actors.')->group(function () {
    // Required endpoint from specifications
    Route::get('/prompt-validation', [ActorController::class, 'getPromptValidation'])
        ->name('prompt-validation');

    // Actor management endpoints
    Route::get('/', [ActorController::class, 'index'])->name('index');
    Route::get('/{uuid}', [ActorController::class, 'show'])->name('show');
    Route::post('/{uuid}/retry', [ActorController::class, 'retry'])->name('retry');
});

// Rate limiting for API routes
Route::middleware(['throttle:60,1'])->group(function () {
    // These routes have stricter rate limiting (60 requests per minute)
    Route::post('/actors', [ActorController::class, 'store'])->name('api.actors.store');
});
