<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index (){

        //sales today
        $sales = Order::whereDate('created_at', Carbon::today())->where('status', 'Order Served')->sum('total_amount');
        if($sales == 0){
            $sales = '0';
        }

        $salesYesterday = Order::whereDate('created_at', Carbon::yesterday())->where('status','Served')->sum('total_amount');
        $comparedSale = 0;
        if($salesYesterday !== 0){
            $comparedSale = ($sales/$salesYesterday)*100; 
        };

        //product sold today
        $productSold = OrderItem::whereDate('created_at', Carbon::today())->where('status', 'Served')->sum('item_qty');

        $productSoldYesterday = OrderItem::whereDate('created_at', Carbon::yesterday())->where('status','Served')->sum('item_qty');
        $comparedSold = 0;
        if($productSoldYesterday !== 0){
            $comparedSold = ($productSold/$productSoldYesterday)*100; 
        };

        //order today
        $order = Order::whereDate('created_at', Carbon::today())->where('status', 'Order Served')->count();

        $orderYesterday = Order::whereDate('created_at', Carbon::yesterday())->where('status','Served')->sum('total_amount');
        $comparedOrder = 0;
        if($orderYesterday !== 0){
            $comparedOrder = ($order/$orderYesterday)*100; 
        };

        //table room activity

        //product low at stock
        $allProducts = IventoryItem::with(['itemCategory', 'inventory.category'])
                        ->select('inventory_id', 'item_name', 'item_cat_id', 'stock_qty')
                        ->get();

        $allProducts = $allProducts->map(function ($product) {

            $categoryName = $product->itemCategory ? $product->itemCategory->name : null;

            return [
                'inventory_id' => $product->inventory_id,
                'item_name' => $product->item_name,
                'item_cat_id' => $product->item_cat_id,
                'stock_qty' => $product->stock_qty,
                'type' => $categoryName, 
                'product_name' => $product->inventory->name,
                'low_stock_qty' => $product->itemCategory->low_stock_qty,
                'category' => $product->inventory->category->name,
            ];

            })->filter(function ($product) {
                return $product['stock_qty'] < $product['low_stock_qty'];
            });

        //on duty today

        $products = collect($allProducts)->sortByDesc('stock_qty')->values()->all();

        return Inertia::render('Dashboard/Dashboard', [
            'products' => $products,
            'sales' => $sales,
            'productSold' => $productSold,
            'order' => $order,
            'compareSold' => (int) round($comparedSold),
            'compareSale' => (int) round($comparedSale),
            'compareOrder' => (int) round($comparedOrder),
        ]);
    }
}
