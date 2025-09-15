<?php

declare(strict_types=1);

use App\Http\Controllers\ActorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home page - redirect to actors
Route::get('/', function () {
    return redirect()->route('actors.index');
})->name('home');

// Actor Web Routes
Route::prefix('actors')->name('actors.')->group(function () {
    Route::get('/', function () {
        return view('actors.index');
    })->name('index');
    Route::post('/', [ActorController::class, 'store'])->name('store');
    Route::get('/{uuid}', [ActorController::class, 'show'])->name('show');
    Route::post('/{uuid}/retry', [ActorController::class, 'retry'])->name('retry');
});

// API Documentation Routes
Route::prefix('api/docs')->name('api.docs.')->group(function () {
    Route::get('/', function () {
        return view('api-docs.index');
    })->name('index');
    Route::get('/prompt-validation', function () {
        return view('api-docs.prompt-validation');
    })->name('prompt-validation');
    Route::get('/actors', function () {
        return view('api-docs.actors');
    })->name('actors');
});

// Health check endpoint for web
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'service' => 'actor-management-web',
        'timestamp' => now()->toISOString(),
    ]);
})->name('health');
