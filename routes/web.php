<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaiterController;
use App\Http\Controllers\LoyaltyController;
use Inertia\Inertia;
use App\Http\Controllers\ConfigPromotionController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TableRoomController;


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
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/getProducts', [ProductController::class, 'getProducts'])->name('products.getProducts');
        Route::get('/products/getAllCategories', [ProductController::class, 'getAllCategories']);
        Route::get('/products/getAllInventories', [ProductController::class, 'getAllInventories'])->name('products.getAllInventories');
        Route::get('/products/getTestingRecords', [ProductController::class, 'getTestingRecords'])->name('products.getTestingRecords');
        Route::get('/products_details/{id}', [ProductController::class, 'showProductDetails'])->name('products.showProductDetails');
        Route::get('/products/getInventoryItemStock/{id}', [ProductController::class, 'getInventoryItemStock'])->name('products.getInventoryItemStock');
        Route::get('/products/getProductSaleHistories', [ProductController::class, 'getProductSaleHistories'])->name('products.getProductSaleHistories');
        Route::get('/products/getProductWithItems/{id}', [ProductController::class, 'getProductWithItems'])->name('products.getProductWithItems');
        Route::put('/products/updateProduct/{id}', [ProductController::class, 'updateProduct'])->name('products.updateProduct');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::delete('/products/deleteProduct/{id}', [ProductController::class, 'deleteProduct'])->name('products.deleteProduct');
        Route::delete('/products/deleteProductItem/{id}', [ProductController::class, 'deleteProductItem'])->name('products.deleteProductItem');
    });

     /********* Inventory **********/
     Route::prefix('inventory')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
        Route::get('/inventory/getInventories', [InventoryController::class, 'getInventories']);
        Route::get('/inventory/getAllCategories', [InventoryController::class, 'getAllCategories']);
        Route::get('/inventory/getAllItemCategories', [InventoryController::class, 'getAllItemCategories']);
        Route::get('/inventory/getDropdownValue', [InventoryController::class, 'getDropdownValue']);
        Route::get('/inventory/getInventoryItems/{id}', [InventoryController::class, 'getInventoryItems']);
        Route::get('/inventory/getRecentKeepHistory', [InventoryController::class, 'getRecentKeepHistory']);
        Route::get('/inventory/keep_history', [InventoryController::class, 'viewKeepHistories'])->name('inventory.viewKeepHistories');
        Route::get('/inventory/stock_history', [InventoryController::class, 'viewStockHistories'])->name('inventory.viewStockHistories');
        Route::get('/inventory/getAllStockHistory', [InventoryController::class, 'getAllStockHistory']);
        Route::post('/inventory/store', [InventoryController::class, 'store'])->name('inventory.store');
        Route::put('/inventory/updateInventoryItemStock/{id}', [InventoryController::class, 'updateInventoryItemStock'])->name('inventory.updateInventoryItemStock');
        Route::put('/inventory/updateInventoryAndItems/{id}', [InventoryController::class, 'updateInventoryAndItems'])->name('inventory.updateInventoryAndItems');
        Route::delete('/inventory/deleteInventory/{id}', [InventoryController::class, 'deleteInventory'])->name('inventory.deleteInventory');
    });

    /******* Profile ********/
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /******* Configuration ********/
    Route::prefix('configurations')->group(function () {
        Route::get('/configurations', [ConfigPromotionController::class, 'index'])->name('configurations');

        Route::get('/getStock', [ConfigPromotionController::class, 'getStock'])->name('configurations.getStock');

        /******* Promotion ********/
        Route::get('/promotions', [ConfigPromotionController::class, 'index'])->name('configurations.promotions.index');
        Route::post('/promotions/store', [ConfigPromotionController::class, 'store'])->name('configurations.promotions.store');
        Route::post('/edit', [ConfigPromotionController::class, 'edit'])->name('configurations.promotion.edit');
        Route::post('/delete', [ConfigPromotionController::class, 'delete'])->name('configurations.promotion.delete');
        
        Route::post('/updateMerchant', [ConfigPromotionController::class, 'updateMerchant'])->name('configurations.updateMerchant');
        Route::post('/addTax', [ConfigPromotionController::class, 'addTax'])->name('configurations.addTax');
        Route::get('/getTax', [ConfigPromotionController::class, 'getTax'])->name('configurations.getTax');

    });

    /********Loyalty Programme **********/
    Route::prefix('loyalty-programme')->group(function(){
        Route::get('/loyalty-programme', [LoyaltyController::class, 'index'])->name('loyalty-program');
        Route::get('/getShowRecords', [LoyaltyController::class, 'showRecord'])->name('loyalty-program.show');
        Route::get('/getIcons', [LoyaltyController::class, 'showIcons'])->name('loyalty-program.showIcons');
        Route::get('/tier_details/{id}', [LoyaltyController::class, 'showTierDetails'])->name('products.showTierDetails');
        Route::get('/getMemberList', [LoyaltyController::class, 'showMemberList'])->name('loyalty-programme.getMemberList');
        Route::get('/getTierData', [LoyaltyController::class, 'showTierData'])->name('loyalty-programme.getTierData');
        Route::get('/getAllInventoryWithItems', [LoyaltyController::class, 'getAllInventoryWithItems'])->name('loyalty-programme.getAllInventoryWithItems');
        Route::post('/create-tier', [LoyaltyController::class, 'store'])->name('loyalty.create-tier');
        Route::put('/loyalty-programme/updateTier/{id}', [LoyaltyController::class, 'updateTier'])->name('loyalty-programme.updateTier');
        Route::delete('/loyalty-programme/deleteTier/{id}', [LoyaltyController::class, 'deleteTier'])->name('loyalty-programme.deleteTier');
    });

     /********Table and room **********/
     Route::prefix('table-room')->group(function(){
        Route::get('/table-room', [TableRoomController::class, 'index'])->name('table-room');
        Route::post('/add-zones', [TableRoomController::class,'addZone'])->name('tableroom.add-zone');
        Route::delete('/table-room/deleteZone/{id}', [TableRoomController::class, 'deleteZone'])->name('tableroom.delete-zone');
        Route::delete('/table-room/deleteTable/{id}', [TableRoomController::class,'deleteTable'])->name('tableroom.delete-table');
        Route::post('/add-table', [TableRoomController::class,'addTable'])->name('tableroom.add-table');
        Route::get('/get-tabledetails', [TableRoomController::class,'getTableDetails'])->name('tableroom.getTableDetails');
        Route::post('/edit-table', [TableRoomController::class,'editTable'])->name('tableroom.edit-table');
     });
});

require __DIR__.'/auth.php';
