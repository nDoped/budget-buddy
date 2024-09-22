<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionImageController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryTypeController;
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
//        'canRegister' => Route::has('register'),
        'canRegister' => false,
        //'laravelVersion' => Application::VERSION,
        //'phpVersion' => PHP_VERSION,
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
    Route::patch('/transactions/update/{transaction}', [ TransactionController::class, 'update' ])->name('transactions.update');
    /* Route::post('/transactions/upload-receipt', [ TransactionController::class, 'uploadReceipt' ])->name('transactions.upload_receipt'); */
    Route::delete('/transactions/destroy/{id}', [ TransactionController::class, 'destroy' ])->name('transactions.destroy');
    Route::patch('/categories/update/{category}', [ CategoryController::class, 'update' ])->name('categories.update');
    Route::delete('/categories/destroy/{id}', [ CategoryController::class, 'destroy' ])->name('categories.destroy');
    Route::delete('/category_types/destroy/{id}', [ CategoryTypeController::class, 'destroy' ])->name('category_types.destroy');
    Route::patch('/category_types/update/{categoryType}', [ CategoryTypeController::class, 'update' ])->name('category_types.update');

    Route::get('/transaction_images/{image}', [ TransactionImageController::class, 'getImageData' ])->name('images.data');

    Route::get('/settings', [ SettingsController::class, 'index' ])->name('settings.accounts');
    Route::get('/settings/account_types', [ SettingsController::class, 'account_types' ])->name('settings.account_types');
    Route::post('/settings/store_account', [ SettingsController::class, 'store_account' ])->name('accounts.store');
    Route::post('/settings/store_account_type', [ SettingsController::class, 'store_account_type' ])->name('account_types.store');
    Route::get('/settings/categories', [ SettingsController::class, 'categories' ])->name('settings.categories');
    Route::post('/settings/store_category', [ CategoryController::class, 'store' ])->name('categories.store');
    Route::get('/settings/category_types', [ SettingsController::class, 'category_types' ])->name('settings.category_types');
    Route::post('/settings/store_category_type', [ CategoryTypeController::class, 'store' ])->name('category_type.store');
});
