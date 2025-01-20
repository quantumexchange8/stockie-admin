<?php

namespace App\Services;

use App\Models\User;

class TotalSalesService
{
    public static function getSales($waiterID)
    {
        // $waiters = User::find($waiterID)
        //                 ->withSum('orderedItems', 'amount')
        //                 // ->select('id', 'full_name', 'ordered_items_sum_amount')
        //                 ->get()
        //                 ->map(function($sales){
        //                     return [
        //                         'id' => $sales->id,
        //                         'full_name' => $sales->full_name,
        //                         'total_sales' => $sales->ordered_items_sum_amount
        //                     ];
        //                 })->unique('id');

        // // dd($waiters);

        // return $waiters;
        $waiter = User::where('id', $waiterID)
                        ->withSum('itemSales', 'amount')
                        ->first();

        return $waiter->ordered_items_sum_amount ?? 0;
    }
}
