<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaiterController;
use App\Http\Controllers\MainController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::middleware('auth')->group(function () {
    
    /******* Component *********/
    Route::get('/components', [MainController::class, 'component'])->name('components');

    /******* Dashboard *********/
    Route::get('/dashboard', [MainController::class, 'dashboard'])->name('dashboard');

    /********* Waiter **********/
    Route::get('waiter', [WaiterController::class, 'waiter'])->name('waiter');
    Route::post('waiter/add-waiter', [WaiterController::class, 'store'])->name('waiter.add-waiter');


    /******* Profile ********/
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
