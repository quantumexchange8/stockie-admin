<?php

use App\Http\Controllers\API\ActivityController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PromotionController;
use App\Http\Controllers\API\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/********* Login & Logout **********/
Route::controller(AuthController::class)->group(function(){
    Route::post('login', 'login');
    Route::post('logout', 'logout');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/get-user', fn () => Auth::user());

    // Route::controller(DashboardController::class)->prefix('menu-management')->group(function(){
    // });

    /********* Attendance **********/
    Route::controller(AttendanceController::class)->prefix('attendance')->group(function(){
        Route::get('/check_in_time', 'getCheckInTime');
        Route::get('/today_attendance', 'getTodayAttendance');
        Route::get('/attendance_histories', 'getAllAttendances');
        Route::post('/check_in', 'checkIn');
        Route::post('/authenticate_old_passcode', 'authenticateOldPasscode');
        Route::put('/change_new_passcode', 'changeNewPasscode');
        Route::put('/check_out', 'checkOut');
    });

    /********* Promotion **********/
    Route::controller(PromotionController::class)->prefix('promotions')->group(function(){
        Route::get('/', 'getAllPromotions');
        Route::get('/most_recent_promotions', 'getMostRecentPromotions');
        Route::get('/promotion_details', 'getPromotionDetails');
    });

    /********* Insights **********/
    Route::controller(ReportController::class)->prefix('insights')->group(function(){
        // Sales & Commissions
        Route::get('/sales_commissions/summary', 'getSalesCommissionSummary');
        Route::get('/sales_commissions/details', 'getSalesCommissionDetails');
        Route::get('/sales_commissions/sales_year_list', 'getSalesYearList');
        Route::get('/sales_commissions/recent_histories', 'getRecentSalesHistories');
        Route::get('/sales_commissions/histories', 'getSalesHistories');
        Route::get('/sales_commissions/sales_details', 'getSalesDetails');
        Route::get('/sales_commissions/order_histories', 'getOrderHistories');
        
        // Incentive
        Route::get('/incentive/summary', 'getIncentiveSummary');
        Route::get('/incentive/details', 'getIncentiveDetails');
        Route::get('/incentive/recent_histories', 'getRecentIncentiveHistories');
        Route::get('/incentive/histories', 'getIncentiveHistories');
    });

    Route::controller(OrderController::class)->prefix('orders')->group(function(){
        // Tables
        Route::get('/tables', 'getAllTables');
        Route::get('/customers', 'getAllCustomers');
        Route::post('/check_in_table', 'checkInTable');
        Route::post('/check_in_customer', 'checkInCustomer');

        // Order
        // Route::get('/order/order_summary', 'getOrderSummary');
        Route::get('/order/products', 'getAllProducts');
        Route::get('/order/pending_serve_items', 'getPendingServeItems');
        Route::get('/order/product_categories', 'getAllCategories');
        // Route::get('/order/customer', 'getOrderCustomer');
        Route::get('/order/table_keep_items', 'getTableKeepItems');
        Route::post('/order/place_order_items', 'placeOrderItem');
        Route::put('/order/serve_item', 'serveOrderItem');
    });

    Route::controller(ActivityController::class)->prefix('activity')->group(function(){
        Route::get('/recent_activities', 'getRecentActivityLogs');
        Route::get('/activities', 'getAllActivityLogs');
    });
});
