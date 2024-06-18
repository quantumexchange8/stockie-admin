<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaiterController;

use Inertia\Inertia;
use App\Http\Controllers\ConfigPromotionController;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::middleware('auth')->group(function () {

   /********* Dashboard **********/
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    /********* Components **********/
    Route::get('/components', function () {
        return Inertia::render('ComponentDisplay/ComponentShowcase');
    })->name('components');

    /********* Waiter **********/
    Route::get('waiter', [WaiterController::class, 'waiter'])->name('waiter');
    Route::post('waiter/add-waiter', [WaiterController::class, 'store'])->name('waiter.add-waiter');

    /********* Menu Management **********/
    Route::middleware(['auth', 'verified'])->prefix('menu-management')->group(function () {
        Route::resource('/products', ProductController::class);
    });

    /********* Inventory **********/
    // Route::middleware(['auth', 'verified'])->prefix('inventory')->group(function () {
    //     Route::get('/components', function () {
    //         return Inertia::render('ComponentDisplay/ComponentShowcase');
    //     })->middleware(['auth', 'verified'])->name('components');
    // });

    /********* Configuration **********/
    Route::get('/configurations', function () {
        return Inertia::render('Configuration/Promotion');
    })->name('configurations.index');
    
    Route::resource('/configurations/promotions', ConfigPromotionController::class);

    /******* Profile ********/
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    /******* Configuration ********/
    Route::get('/configuration', function () {
        return Inertia::render('Configuration/MainConfiguration');
    })->name('configuration');


});

require __DIR__.'/auth.php';
