<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ConfigCommissionController;
use App\Http\Controllers\ConfigDiscountController;
use App\Http\Controllers\ConfigEmployeeIncProgController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SummaryReportController;
use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaiterController;
use App\Http\Controllers\LoyaltyController;
use Inertia\Inertia;
use App\Http\Controllers\ConfigPromotionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReservationController;
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

    // Route::get('/dashboard', function () {
        //     return Inertia::render('Dashboard/Dashboard');
        // })->name('dashboard');
    Route::get('/allNotifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/latestNotification', [NotificationController::class, 'latestNotification'])->name('notifications.latest-notifications');
    Route::get('/notifications/filterNotification', [NotificationController::class, 'filterNotification'])->name('notifications.filter-notifications');
    Route::post('/notifications/markAsRead', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
        
    /********* Dashboard **********/
    Route::prefix('dashboard')->middleware([CheckPermission::class . ':dashboard'])->group(function(){
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/filterSale', [DashboardController::class, 'filterSales'])->name('dashboard.filter-sales');
        Route::get('/activeTables', [DashboardController::class, 'getActiveTables'])->name('dashboard.active-tables');
        Route::get('/getActivities', [DashboardController::class, 'getActivities'])->name('dashboard.activity-log');
    });

    /********* Components **********/
    Route::get('/components', function () {
        return Inertia::render('ComponentDisplay/ComponentShowcase');
    })->name('components');

    /********* Waiter **********/
    Route::prefix('waiter')->middleware([CheckPermission::class . ':waiter'])->group(function(){
        Route::get('/', [WaiterController::class, 'waiter'])->name('waiter');
        Route::post('/createWaiter', [WaiterController::class, 'store'])->name('waiter.add-waiter');
        Route::delete('/deleteWaiter/{id}', [WaiterController::class, 'deleteWaiter'])->name('waiter.delete-waiter');
        Route::post('/editWaiter', [WaiterController::class,'editWaiter'])->name('waiter.edit-waiter');
        Route::get('/waiterDetails/{id}', [WaiterController::class,'showWaiterDetails'])->name('waiter.waiter-details');
        Route::get('/orderDetails/{id}',[WaiterController::class,'orderDetails'])->name('waiter.order-details');
        Route::get('/salesReport/{id}', [WaiterController::class,'salesReport'])->name('waiter.sales-report');
        Route::get('/viewAttendance/{id}', [WaiterController::class,'viewAttendance'])->name('waiter.view-attendance');
        Route::get('/filterSalesPerformance', [WaiterController::class, 'filterSalesPerformance'])->name('waiter.filter-salesperformance');
        Route::get('/filterCommEarned', [WaiterController::class, 'filterCommEarned'])->name('waiter.filter-commEarned');
        Route::get('/viewEmployeeIncentive', [WaiterController::class, 'viewEmployeeIncentive'])->name('waiter.viewEmployeeIncentive');
    });
 
    /********* Menu Management **********/
    Route::prefix('menu-management')->middleware([CheckPermission::class . ':menu-management'])->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/storeFromInventoryItems', [ProductController::class, 'storeFromInventoryItems'])->name('products.storeFromInventoryItems');
        Route::post('/products/updateAvailability', [ProductController::class, 'updateAvailability'])->name('products.updateAvailability');
        Route::post('/products/updateProduct/{id}', [ProductController::class, 'updateProduct'])->name('products.updateProduct');
        Route::delete('/products/deleteProduct/{id}', [ProductController::class, 'deleteProduct'])->name('products.deleteProduct');
        Route::delete('/products/deleteProductItem/{id}', [ProductController::class, 'deleteProductItem'])->name('products.deleteProductItem');

        // Product Category
        Route::get('/products/category/getCategoryProducts/{id}', [ProductController::class, 'getCategoryProducts'])->name('products.category.getCategoryProducts');
        Route::post('/products/category/storeCategory', [ProductController::class, 'storeCategory'])->name('products.category.store');
        Route::post('/products/category/reassignProductsCategory/{id}', [ProductController::class, 'reassignProductsCategory'])->name('products.category.reassignProductsCategory');
        Route::put('/products/category/updateCategory/{id}', [ProductController::class, 'updateCategory'])->name('products.category.update');
        
        Route::get('/products_details/{id}', [ProductController::class, 'showProductDetails'])->name('products.showProductDetails');
        Route::get('/products/getProducts', [ProductController::class, 'getProducts'])->name('products.getProducts');
        Route::get('/products/getAllCategories', [ProductController::class, 'getAllCategories']);
        Route::get('/products/getAllInventories', [ProductController::class, 'getAllInventories'])->name('products.getAllInventories');
        Route::get('/products/getTestingRecords', [ProductController::class, 'getTestingRecords'])->name('products.getTestingRecords');
        Route::get('/products/getInventoryItemStock/{id}', [ProductController::class, 'getInventoryItemStock'])->name('products.getInventoryItemStock');
        Route::get('/products/getProductSaleHistories/{id}', [ProductController::class, 'getProductSaleHistories'])->name('products.getProductSaleHistories');
        Route::get('/products/getRedemptionHistories/{id}', [ProductController::class, 'getRedemptionHistories'])->name('products.getRedemptionHistories');
    });

     /********* Inventory **********/
     Route::prefix('inventory')->middleware([CheckPermission::class . ':inventory'])->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
        Route::post('/inventory/store', [InventoryController::class, 'store'])->name('inventory.store');
        Route::post('/inventory/updateInventoryItemStock/{id}', [InventoryController::class, 'updateInventoryItemStock'])->name('inventory.updateInventoryItemStock');
        Route::post('/inventory/updateInventoryAndItems/{id}', [InventoryController::class, 'updateInventoryAndItems'])->name('inventory.updateInventoryAndItems');
        Route::delete('/inventory/deleteInventory/{id}', [InventoryController::class, 'deleteInventory'])->name('inventory.deleteInventory');
        
        Route::get('/inventory/keep_history', [InventoryController::class, 'viewKeepHistories'])->name('inventory.viewKeepHistories');
        Route::get('/inventory/stock_history', [InventoryController::class, 'viewStockHistories'])->name('inventory.viewStockHistories');
        // Route::get('/inventory/getDropdownValue', [InventoryController::class, 'getDropdownValue']);
        Route::get('/inventory/getInventories', [InventoryController::class, 'getInventories']);
        Route::get('/inventory/getInventoryItems/{id}', [InventoryController::class, 'getInventoryItems']);
        Route::get('/inventory/getAllStockHistory', [InventoryController::class, 'getAllStockHistory']);
        Route::post('/inventory/getAllKeepHistory', [InventoryController::class, 'getAllKeepHistory']);
        Route::get('/inventory/getLatestInventory', [InventoryController::class, 'getLatestInventory']);
        Route::get('/inventory/activeKeptItem', [InventoryController::class, 'activeKeptItem'])->name('activeKeptItem');
        Route::get('/inventory/filterKeptItem', [InventoryController::class, 'filterKeptItem'])->name('filterKeptItem');
    });

    /******* Profile ********/
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/updateProfile', [ProfileController::class, 'updateProfile'])->name('profile.updateProfile');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /******* Configuration ********/
    Route::prefix('configurations')->middleware([CheckPermission::class . ':configuration'])->group(function () {
        Route::get('/configurations', [ConfigPromotionController::class, 'index'])->name('configurations');

        /******* Stock ********/
        Route::get('/getStock', [ConfigPromotionController::class, 'getStock'])->name('configurations.getStock');
        Route::post('/updateStock', [ConfigPromotionController::class, 'update'])->name('configurations.updateStock');

        /******* Discount Settings ********/
        Route::get('/getDiscount', [ConfigDiscountController::class, 'getDiscount'])->name('configurations.getDiscount');
        Route::post('/createDiscount', [ConfigDiscountController::class, 'createDiscount'])->name('configurations.createDiscount');
        Route::get('/discountDetails', [ConfigDiscountController::class, 'discountDetails'])->name('configurations.discountDetails');
        Route::delete('/deleteDiscount/{id}', [ConfigDiscountController::class, 'deleteDiscount'])->name('configurations.deleteDiscount');
        Route::post('/editDiscount/{id}', [ConfigDiscountController::class, 'editDiscount'])->name('configurations.editDiscount');
        Route::get('/editProductDetails/{id}', [ConfigDiscountController::class, 'editProductDetails'])->name('configurations.editProductDetails');

        /******* Bill Discount ********/
        Route::get('/getAllTiers', [ConfigDiscountController::class, 'getAllTiers'])->name('configurations.getAllTiers');
        Route::get('/getAllBillDiscounts', [ConfigDiscountController::class, 'getAllBillDiscounts'])->name('configurations.getAllBillDiscounts');
        Route::post('/addBillDiscount', [ConfigDiscountController::class, 'addBillDiscount'])->name('configurations.addBillDiscount');
        Route::put('/editBillDiscount/{id}', [ConfigDiscountController::class, 'editBillDiscount'])->name('configurations.editBillDiscount');
        Route::delete('deleteBillDiscount/{id}', [ConfigDiscountController::class, 'deleteBillDiscount'])->name('configurations.deleteBillDiscount');
        Route::post('updateBillStatus/{id}', [ConfigDiscountController::class, 'updateBillStatus'])->name('configurations.updateBillStatus');

        /******* Employee Commission ********/
        Route::get('/configurations/commission', [ConfigCommissionController::class, 'index'])->name('configurations.commission');
        Route::post('/addCommission', [ConfigCommissionController::class, 'addCommission'])->name('configurations.addCommission');
        Route::delete('/deleteCommission/{id}', [ConfigCommissionController::class, 'deleteCommission'])->name('configurations.deleteCommission');
        Route::post('/editCommission', [ConfigCommissionController::class, 'editCommission'])->name('configurations.editCommission');
        Route::get('/productDetails/{id}', [ConfigCommissionController::class, 'productDetails'])->name('configurations.productDetails');
        Route::delete('/deleteProduct', [ConfigCommissionController::class, 'deleteProduct'])->name('configurations.deleteProduct');
        Route::post('/addProducts', [ConfigCommissionController::class, 'addProducts'])->name('configurations.addProducts');
        Route::post('/updateCommission', [ConfigCommissionController::class, 'updateCommission'])->name('configurations.updateCommission');

        /******* Employee Incentive Programme ********/
        Route::get('/configurations/incentive', [ConfigEmployeeIncProgController::class, 'index'])->name('configurations.incentive');
        Route::get('/configurations/incentCommDetail/{id}', [ConfigEmployeeIncProgController::class, 'incentCommDetail'])->name('configuration.incentCommDetail');
        Route::get('/configurations/getIncentDetail/{id}', [ConfigEmployeeIncProgController::class, 'getIncentDetail'])->name('configurations.getIncentDetail');
        Route::get('/configurations/getAllWaiters/{id}', [ConfigEmployeeIncProgController::class, 'getAllWaiters'])->name('configurations.getAllWaiters');
        Route::post('configuration/addAchievement', [ConfigEmployeeIncProgController::class, 'addAchievement'])->name('configurations.addAchievement');
        Route::post('/configurations/editAchievement', [ConfigEmployeeIncProgController::class, 'editAchievement'])->name('configurations.editAchievement');
        Route::post('/configurations/updateStatus/{id}', [ConfigEmployeeIncProgController::class, 'updateStatus'])->name('configurations.updateStatus');
        Route::post('/configurations/updateIncentiveRecurringDay', [ConfigEmployeeIncProgController::class, 'updateIncentiveRecurringDay'])->name('configurations.updateIncentiveRecurringDay');
        Route::post('/configurations/addEntitledEmployees/{id}', [ConfigEmployeeIncProgController::class, 'addEntitledEmployees'])->name('configurations.addEntitledEmployees');
        Route::put('/configurations/deleteEntitled/{id}', [ConfigEmployeeIncProgController::class, 'deleteEntitled'])->name('configuration.deleteEntitled');
        Route::delete('/configurations/deleteAchievement/{id}', [ConfigEmployeeIncProgController::class, 'deleteAchievement'])->name('configurations.deleteAchievement');

        /******* Promotion ********/
        Route::get('/promotions', [ConfigPromotionController::class, 'index'])->name('configurations.promotions.index');
        Route::post('/promotions/store', [ConfigPromotionController::class, 'store'])->name('configurations.promotions.store');
        Route::post('/edit', [ConfigPromotionController::class, 'edit'])->name('configurations.promotion.edit');
        Route::post('/delete', [ConfigPromotionController::class, 'delete'])->name('configurations.promotion.delete');
        
        /******* Invoice Setting ********/
        Route::post('/updateMerchant', [ConfigPromotionController::class, 'updateMerchant'])->name('configurations.updateMerchant');
        Route::post('/addTax', [ConfigPromotionController::class, 'addTax'])->name('configurations.addTax');
        Route::get('/getTax', [ConfigPromotionController::class, 'getTax'])->name('configurations.getTax');
        Route::delete('/deleteTax/{id}', [ConfigPromotionController::class, 'deleteTax'])->name('configuration.deleteTax');
        Route::get('/getClassificationCodes', [ConfigPromotionController::class, 'getClassificationCodes'])->name('configuration.getClassificationCodes');
        Route::get('/refetchMerchant', [ConfigPromotionController::class, 'refetchMerchant'])->name('configurations.refetchMerchant');
        Route::get('/getMSICCodes', [ConfigPromotionController::class, 'getMSICCodes'])->name('configurations.getMSICCodes');
        Route::put('/editAddress', [ConfigPromotionController::class, 'editAddress'])->name('configurations.editAddress');
        Route::put('/editTax', [ConfigPromotionController::class, 'editTax'])->name('configurations.editTax');

        /******* Points Settings ********/
        Route::post('/pointCalculate', [ConfigPromotionController::class, 'pointCalculate'])->name('configuration.pointCalculate');
        Route::get('/getPoint', [ConfigPromotionController::class, 'getPoint'])->name('configurations.getPoint');
    });

    /******** Loyalty Programme **********/
    Route::prefix('loyalty-programme')->middleware([CheckPermission::class . ':loyalty-programme'])->group(function(){
        Route::get('/loyalty-programme', [LoyaltyController::class, 'index'])->name('loyalty-programme');
        
        /******* Tier ********/
        Route::get('/tiers', [LoyaltyController::class, 'index'])->name('loyalty-programme.tiers');
        Route::get('/tier_details/{id}', [LoyaltyController::class, 'showTierDetails'])->name('loyalty-programme.tiers.show');
        Route::post('/tiers/store', [LoyaltyController::class, 'storeTier'])->name('loyalty-programme.tiers.store');
        Route::post('/tiers/update/{id}', [LoyaltyController::class, 'updateTier'])->name('loyalty-programme.tiers.update');
        Route::delete('/tiers/destroy/{id}', [LoyaltyController::class, 'deleteTier'])->name('loyalty-programme.tiers.destroy');
        Route::get('/filterMemberSpending', [LoyaltyController::class, 'filterMemberSpending'])->name('loyalty-programme.tiers.filter');

        
        /******* Point ********/
        Route::get('/points', [LoyaltyController::class, 'index'])->name('loyalty-programme.points');
        Route::get('/point_details/{id}', [LoyaltyController::class, 'showPointDetails'])->name('loyalty-programme.points.show');
        Route::get('/points/recent_redemptions', [LoyaltyController::class, 'showRecentRedemptions'])->name('loyalty-programme.points.showRecentRedemptions');
        Route::post('/points', [LoyaltyController::class, 'storePoint'])->name('loyalty-programme.points.store');
        Route::post('/points/{id}', [LoyaltyController::class, 'updatePoint'])->name('loyalty-programme.points.update');
        Route::delete('/points/{id}', [LoyaltyController::class, 'deletePoint'])->name('loyalty-programme.points.deletePoint');
        
        Route::get('/getRecentRedemptionHistories/{id?}', [LoyaltyController::class, 'getRecentRedemptionHistories'])->name('loyalty-programme.getRecentRedemptionHistories');
        
    });

     /********Table and room **********/
     Route::prefix('table-room')->middleware([CheckPermission::class . ':table-room'])->group(function(){
        Route::get('/table-room', [TableRoomController::class, 'index'])->name('table-room');
        Route::post('/add-zones', [TableRoomController::class,'addZone'])->name('tableroom.add-zone');
        Route::delete('/table-room/deleteZone/{id}', [TableRoomController::class, 'deleteZone'])->name('tableroom.delete-zone');
        Route::delete('/table-room/deleteTable/{id}', [TableRoomController::class,'deleteTable'])->name('tableroom.delete-table');
        Route::post('/add-table', [TableRoomController::class,'addTable'])->name('tableroom.add-table');
        Route::get('/get-zonedetails', [TableRoomController::class, 'getZoneDetails'])->name('tableroom.getZoneDetails');
        Route::get('/get-tabledetails', [TableRoomController::class,'getTableDetails'])->name('tableroom.getTableDetails');
        Route::post('/edit-table', [TableRoomController::class,'editTable'])->name('tableroom.edit-table');
        Route::post('/edit-zone', [TableRoomController::class,'editZone'])->name('tableroom.edit-zone');
     });
     
    /******** Order Management **********/
    Route::prefix('order-management')->middleware([CheckPermission::class . ':order-management'])->group(function(){
        Route::get('/orders', [OrderController::class, 'index'])->name('orders');
        Route::get('/orders/getOrderPaymentDetails{id}', [OrderController::class, 'getOrderPaymentDetails'])->name('orders.getOrderPaymentDetails');
        Route::get('/orders/getOccupiedTablePayments{id}', [OrderController::class, 'getOccupiedTablePayments'])->name('orders.getOccupiedTablePayments');
        Route::put('/orders/cancelOrder/{id}', [OrderController::class, 'cancelOrder'])->name('orders.cancel');
        Route::put('/orders/updateOrderStatus/{id}', [OrderController::class, 'updateOrderStatus'])->name('orders.complete');
        Route::put('/orders/updateOrderCustomer/{data}', [OrderController::class, 'updateOrderCustomer'])->name('orders.updateOrderCustomer');
        Route::put('/orders/updateOrderPayment/{id}', [OrderController::class, 'updateOrderPayment'])->name('orders.updateOrderPayment');
        Route::put('/orders/removeOrderVoucher/{id}', [OrderController::class, 'removeOrderVoucher'])->name('orders.removeOrderVoucher');
        Route::get('/orders/getTableKeepItem/{id}', [OrderController::class, 'getTableKeepItem'])->name('orders.getTableKeepItem');

        // Order tables
        Route::post('/orders/storeOrderTable', [OrderController::class, 'storeOrderTable'])->name('orders.tables.store');
        Route::put('/orders/reservation/{id}', [OrderController::class, 'updateReservation'])->name('orders.reservations.update');
        Route::delete('/orders/reservation/{id}', [OrderController::class, 'deleteReservation'])->name('orders.reservations.destroy');
        Route::post('/orders/mergeTable', [OrderController::class, 'mergeTable'])->name('orders.mergeTable');
        
        // Order items
        Route::post('/orders/storeOrderItem', [OrderController::class, 'storeOrderItem'])->name('orders.items.store');
        Route::post('/orders/addItemToKeep', [OrderController::class, 'addItemToKeep'])->name('orders.items.keep');
        Route::put('/orders/updateOrderItem/{id}', [OrderController::class, 'updateOrderItem'])->name('orders.items.update');
        Route::put('/orders/removeOrderItem/{id}', [OrderController::class, 'removeOrderItem'])->name('orders.removeOrderItem');
        Route::get('/orders/getPendingServe/{id}', [OrderController::class, 'pendingServeItems'])->name('orders.pendingServe');

        // Order's customer
        Route::get('/orders/customer/{id}', [OrderController::class, 'getCustomerDetails'])->name('orders.customer');
        Route::get('/orders/customer/keep/getCustomerKeepHistories/{id}', [OrderController::class, 'getCustomerKeepHistories'])->name('orders.customer.keep.getCustomerKeepHistories');
        Route::get('/orders/customer/point/getCustomerPointHistories/{id}', [OrderController::class, 'getCustomerPointHistories'])->name('orders.customer.point.getCustomerPointHistories');
        Route::get('/orders/customer/tier/getCustomerTierRewards/{id}', [OrderController::class, 'getCustomerTierRewards'])->name('orders.customer.tier.getCustomerTierRewards');
        Route::post('/orders/customer/keep/addKeptItemToOrder/{id}', [OrderController::class, 'addKeptItemToOrder'])->name('orders.customer.keep.addKeptItemToOrder');
        Route::post('/orders/customer/point/redeemItemToOrder/{id}', [OrderController::class, 'redeemItemToOrder'])->name('orders.customer.point.redeemItemToOrder');
        Route::post('/orders/customer/reward/redeemEntryRewardToOrder/{id}', [OrderController::class, 'redeemEntryRewardToOrder'])->name('orders.customer.reward.redeemEntryRewardToOrder');

        Route::get('/getRedeemableItems', [OrderController::class, 'getRedeemableItems'])->name('orders.getRedeemableItems');
        Route::get('/orders/getAllCustomers', [OrderController::class, 'getAllCustomer'])->name('orders.getAllCustomer');
        Route::get('/getAllZones', [OrderController::class, 'getAllZones'])->name('orders.getAllZones');
        Route::get('/getAllProducts', [OrderController::class, 'getAllProducts'])->name('orders.getAllProducts');
        Route::get('/getCurrentTableOrder/{id}', [OrderController::class, 'getCurrentTableOrder'])->name('orders.getCurrentTableOrder');
        Route::get('/getOrderHistories', [OrderController::class, 'getOrderHistories'])->name('orders.getOrderHistories');
        Route::get('/getAllCategories', [OrderController::class, 'getAllCategories'])->name('orders.getAllCategories');

        //Order's keep item
        Route::put('/editKeptItemDetail', [OrderController:: class, 'editKeptItemDetail'])->name('editKeptItemDetail');
        Route::post('/reactivateExpiredItems', [OrderController::class, 'reactivateExpiredItems'])->name('reactivateExpiredItems');
        Route::post('/deleteKeptItem', [OrderController::class, 'deleteKeptItem'])->name('deleteKeptItem');
    });

     /********* Customer **********/
     Route::prefix('customer')->middleware([CheckPermission::class . ':customer'])->group(function(){
        Route::get('/',[CustomerController::class,'index'])->name('customer');
        Route::get('/filterCustomer', [CustomerController::class,'getFilteredCustomers'])->name('customer.filter-customer');
        Route::get('/getRedeemableItems', [CustomerController::class,'getRedeemableItems'])->name('customer.getRedeemableItems');
        Route::get('/getKeepHistories/{id}', [CustomerController::class,'getKeepHistories'])->name('customer.getKeepHistories');
        Route::get('/getCustomerPointHistories/{id}', [CustomerController::class,'getCustomerPointHistories'])->name('customer.getCustomerPointHistories');
        Route::get('/tierRewards/{id}', [CustomerController::class,'tierRewards'])->name('customer.tier-rewards');
        Route::post('/returnKeepItem/{id}', [CustomerController::class,'returnKeepItem'])->name('customer.returnKeepItem');
        Route::delete('/deleteCustomer/{id}', [CustomerController::class, 'deleteCustomer'])->name('customer.delete-customer');
        Route::post('/adjustPoint', [CustomerController::class, 'adjustPoint'])->name('customer.adjustPoint');
     });

     /********* Summary Report **********/
     Route::prefix('summary-report')->middleware([CheckPermission::class . ':sales-analysis'])->group(function(){
        Route::get('/', [SummaryReportController::class, 'index'])->name('summary.report');
        Route::get('/filterOrder', [SummaryReportController::class, 'filterOrder'])->name('summary-report.filter-order');
        Route::get('/filterSales', [SummaryReportController::class, 'filterSales'])->name('summary-report.filter-sales');
     });

    /******** Reservation **********/
    Route::prefix('reservation')->middleware([CheckPermission::class . ':reservation'])->group(function(){
        Route::get('', [ReservationController::class, 'index'])->name('reservations');
        Route::post('', [ReservationController::class, 'store'])->name('reservations.store');
        Route::put('/{id}', [ReservationController::class, 'update'])->name('reservations.update');
        Route::delete('/{id}', [ReservationController::class, 'delete'])->name('reservations.delete');
        
        // Reservation actions
        Route::get('/getReservations', [ReservationController::class, 'getReservations'])->name('reservations.getReservations');
        Route::post('/checkInGuest/{id}', [ReservationController::class, 'checkInGuest'])->name('reservations.checkInGuest');
        Route::put('/markAsNoShow/{id}', [ReservationController::class, 'markAsNoShow'])->name('reservations.markAsNoShow');
        Route::put('/delayReservation/{id}', [ReservationController::class, 'delayReservation'])->name('reservations.delayReservation');
        Route::put('/cancelReservation/{id}', [ReservationController::class, 'cancelReservation'])->name('reservations.cancelReservation');

        // View reservation history
        Route::get('/reservation-history', [ReservationController::class, 'viewReservationHistory'])->name('reservations.viewReservationHistory');
        Route::get('/filter-reservation-history', [ReservationController::class, 'filterReservationHistory'])->name('reservations.filterReservationHistory');
    });

    /********* Admin User **********/
    Route::prefix('admin-user')->middleware([CheckPermission::class . ':admin-user'])->group(function(){
        Route::get('', [AdminUserController::class, 'index'])->name('admin-user');
        Route::delete('/delete-admin-user/{id}', [AdminUserController::class, 'deleteAdmin'])->name('delete-admin');
        Route::post('/edit-permission', [AdminUserController::class, 'editPermission'])->name('edit-permission');
        Route::put('/edit-admin-details', [AdminUserController::class, 'editDetails'])->name('edit-admin-details');
        Route::post('/add-sub-admin', [AdminUserController::class, 'addSubAdmin'])->name('add-sub-admin');
        Route::get('/refetch-admin-users', [AdminUserController::class, 'refetchAdminUsers'])->name('refetch-admin-users');
    });

    /********* Admin User **********/
    Route::prefix('activity-log')->middleware([CheckPermission::class . ':activity-logs'])->group(function(){
        Route::get('', [ActivityLogController::class, 'index'])->name('activity-logs');
        Route::get('/filter-logs', [ActivityLogController::class, 'filterLogs'])->name('activity-logs.filter-logs');
    });
});

require __DIR__.'/auth.php';
