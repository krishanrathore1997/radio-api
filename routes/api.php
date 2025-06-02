<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\PlaylistScheduleController;
use App\Http\Controllers\SongController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
Route::prefix('user')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Category Routes
    Route::prefix('categories')->group(function () {
        Route::get('list', [CategoryController::class, 'list'])->name('categories.index');         // Get all categories
        Route::post('store', [CategoryController::class, 'store'])->name('categories.store');       // Store a new category
        Route::post('update/{id}', [CategoryController::class, 'update'])->name('categories.update'); // Update a category
        Route::delete('delete/{id}', [CategoryController::class, 'delete'])->name('categories.destroy'); // Delete a category
    });
    Route::prefix('brands')->group(function () {
        Route::get('list', [BrandController::class, 'list'])->name('brands.index');         // Get all categories
        Route::post('store', [BrandController::class, 'store'])->name('brands.store');       // Store a new category
        Route::post('update/{id}', [BrandController::class, 'update'])->name('brands.update'); // Update a category
        Route::delete('delete/{id}', [BrandController::class, 'delete'])->name('brands.destroy'); // Delete a category
    });
    // Song Routes
    Route::prefix('songs')->group(function () {
        Route::get('list', [SongController::class, 'list'])->name('songs.index');                   // Get all songs
        Route::post('upload', [SongController::class, 'store'])->name('songs.store');               // Upload a new song
        Route::post('update/{id}', [SongController::class, 'update'])->name('songs.update');        // Update a song
        Route::delete('delete', [SongController::class, 'destroy'])->name('songs.destroy');
    });


    Route::prefix('playlists')->group(function () {
        Route::get('list', [PlaylistController::class, 'list']);
        Route::get('view/{id}', [PlaylistController::class, 'show']);
        Route::post('store', [PlaylistController::class, 'store']);
        Route::post('update/{id}', [PlaylistController::class, 'update']);
        Route::delete('delete/{id}', [PlaylistController::class, 'destroy']);
    });

    Route::prefix('schedule')->group(function () {
        Route::get('list', [PlaylistScheduleController::class, 'list']);
        Route::get('today', [PlaylistScheduleController::class, 'todaySchedule']);
        Route::post('store', [PlaylistScheduleController::class, 'store']);
        Route::post('update/{id}', [PlaylistScheduleController::class, 'updte']);
        Route::delete('delete/{id}', [PlaylistScheduleController::class, 'destroy']);
    });
});
Route::get('/songs/{id}/cover_image', [SongController::class, 'coverImage'])->name('songs.coverImage');    // Delete a song
Route::get('/now-playing', function () {
    return response()->json(Cache::get('now_playing', [
        'title' => 'No song playing',
        'file_url' => null,
        'started_at' => null,
        'duration' => null
    ]));
});
