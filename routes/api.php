<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PromotionController;
use App\Http\Controllers\API\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/********* Login & Logout **********/
Route::controller(AuthController::class)->group(function(){
    Route::post('login', 'login');
    Route::post('logout', 'logout');
});

Route::controller(DashboardController::class)->group(function(){
});

/********* Attendance **********/
Route::controller(AttendanceController::class)->group(function(){
    Route::get('attendance/check_in_time', 'getCheckInTime');
    Route::get('attendance/today_attendance', 'getTodayAttendance');
    Route::get('attendance/attendance_histories', 'getAllAttendances');
    Route::post('attendance/check_in', 'checkIn');
    Route::post('attendance/authenticate_old_passcode', 'authenticateOldPasscode');
    Route::put('attendance/change_new_passcode', 'changeNewPasscode');
    Route::put('attendance/check_out', 'checkOut');
});

/********* Promotion **********/
Route::controller(PromotionController::class)->group(function(){
    Route::get('promotions', 'getAllPromotions');
    Route::get('promotions/most_recent_promotions', 'getMostRecentPromotions');
    Route::get('promotions/promotion_details', 'getPromotionDetails');
});

/********* Insights **********/
Route::controller(ReportController::class)->group(function(){
    // Sales & Commissions
    Route::get('insights/sales_commissions/summary', 'getSalesCommissionSummary');
    Route::get('insights/sales_commissions/details', 'getSalesCommissionDetails');
    Route::get('insights/sales_commissions/sales_year_list', 'getSalesYearList');
    Route::get('insights/sales_commissions/recent_histories', 'getRecentSalesHistories');
    Route::get('insights/sales_commissions/histories', 'getSalesHistories');
    Route::get('insights/sales_commissions/sales_details', 'getSalesDetails');
    Route::get('insights/sales_commissions/order_histories', 'getOrderHistories');
    
    // Incentive
    Route::get('insights/incentive/summary', 'getIncentiveSummary');
    Route::get('insights/incentive/details', 'getIncentiveDetails');
    Route::get('insights/incentive/recent_histories', 'getRecentIncentiveHistories');
    Route::get('insights/incentive/histories', 'getIncentiveHistories');
});

Route::controller(OrderController::class)->group(function(){
    Route::get('orders/tables', 'getAllTables');
    Route::get('orders/order/order_summary', 'getOrderSummary');
    Route::get('orders/order/products', 'getAllProducts');
    Route::get('orders/order/pending_serve_items', 'getPendingServeItems');
    Route::get('orders/order/product_categories', 'getAllCategories');
    Route::post('orders/check_in_table', 'checkInTable');
    Route::post('orders/check_in_customer', 'checkInCustomer');
    Route::put('orders/order/serve_item', 'serveOrderItem');
});
