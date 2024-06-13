<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaiterController;
use App\Http\Controllers\AddWaiterController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/components', function () {
    return Inertia::render('ComponentDisplay/ComponentShowcase');
})->middleware(['auth', 'verified'])->name('components');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/******* Waiter ********/
Route::prefix('waiter')->group(function () {
    Route::get('waiter', [WaiterController::class, 'waiter'])->name('waiter');
});


Route::post('/submit-form', [AddWaiterController::class, 'store']);
Route::post('waiter/add-waiter', [AddWaiterController::class, 'store'])->name('waiter.add-waiter');



require __DIR__.'/auth.php';
