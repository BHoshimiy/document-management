<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentFolderController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:auth');

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:auth');
});

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active.user', 'locale'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Standards (menus) and their folders — the client-facing browse flow
    Route::get('menus/{menu:slug}', [MenuController::class, 'show'])->name('menus.show');
    Route::get('folders/{folder:slug}', [DocumentFolderController::class, 'show'])->name('folders.show');

    // Document upload / download / delete
    Route::post('folders/{folder:slug}/documents', [DocumentController::class, 'store'])
        ->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])
        ->name('documents.destroy');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    /*
    |----------------------------------------------------------------------
    | Admin area
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::post('categories/reorder', [CategoryController::class, 'reorder'])
            ->name('categories.reorder');

        Route::resource('folders', DocumentFolderController::class)
            ->except(['show'])
            ->parameters(['folders' => 'folder']);

        Route::resource('menus', MenuController::class)->except(['show']);
        Route::post('menus/reorder', [MenuController::class, 'reorder'])
            ->name('menus.reorder');

        Route::resource('companies', CompanyController::class)
            ->only(['index', 'show', 'edit', 'update', 'destroy']);
    });
});
