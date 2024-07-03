<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaiterController;
use App\Http\Controllers\LoyaltyController;
use Inertia\Inertia;
use App\Http\Controllers\ConfigPromotionController;
use App\Http\Controllers\ProductController;


Route::get('/', function () {
    return Inertia::render('Auth/Login', [
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
    Route::prefix('waiter')->group(function(){
       Route::get('/waiter', [WaiterController::class, 'waiter'])->name('waiter');
    Route::post('/waiter-create', [WaiterController::class, 'store'])->name('waiter.add-waiter');
    
    });
 
    /********* Menu Management **********/
    Route::prefix('menu-management')->group(function () {
        Route::get('/products/getTestingRecords', [ProductController::class, 'getTestingRecords'])->name('products.getTestingRecords');
        Route::get('/products_details/{id}', [ProductController::class, 'showProductDetails'])->name('products.showProductDetails');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    });

    /********* Inventory **********/
    // Route::middleware(['auth', 'verified'])->prefix('inventory')->group(function () {
    //     Route::get('/components', function () {
    //         return Inertia::render('ComponentDisplay/ComponentShowcase');
    //     })->middleware(['auth', 'verified'])->name('components');
    // });

    /******* Profile ********/
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    /******* Configuration ********/
    Route::prefix('configurations')->group(function () {
        Route::get('/configurations', function () {
            return Inertia::render('Configuration/MainConfiguration');
        })->name('configurations');
        
        Route::get('/promotions', [ConfigPromotionController::class, 'index'])->name('configurations.promotions.index');
        Route::post('/promotions/store', [ConfigPromotionController::class, 'store'])->name('configurations.promotions.store');
    });

    /********Loyalty Programme **********/
    Route::prefix('loyalty-programme')->group(function(){
        Route::get('/loyalty-programme', [LoyaltyController::class, 'index'])->name('loyalty-program');
        Route::post('/create-tier', [LoyaltyController::class, 'store'])->name('loyalty.create-tier');
    
    });

});

require __DIR__.'/auth.php';
