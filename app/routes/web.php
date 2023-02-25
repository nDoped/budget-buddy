<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [ DashboardController::class, 'dashboard' ])->name('dashboard');

    Route::get('/transactions', [ TransactionController::class, 'index' ])->name('transactions');
    Route::post('/transactions/store', [ TransactionController::class, 'store' ])->name('transactions.store');
    Route::post('/transactions/update/{transaction}', [ TransactionController::class, 'update' ])->name('transactions.update');
    Route::delete('/transactions/destroy/{id}', [ TransactionController::class, 'destroy' ])->name('transactions.destroy');
    Route::post('/categories/update/{category}', [ CategoryController::class, 'update' ])->name('categories.update');
    Route::delete('/categories/destroy/{id}', [ CategoryController::class, 'destroy' ])->name('categories.destroy');

    Route::get('/settings', [ SettingsController::class, 'index' ])->name('settings.show');
    Route::post('/settings/store_account', [ SettingsController::class, 'store_account' ])->name('accounts.store');
    Route::post('/settings/store_account_type', [ SettingsController::class, 'store_account_type' ])->name('account_types.store');
    Route::get('/settings/categories', [ SettingsController::class, 'categories' ])->name('settings.categories');
});
