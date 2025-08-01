<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Requests\OrderTableRequest;
use App\Jobs\GiveBonusPoint;
use App\Jobs\GiveEntryReward;
use App\Jobs\UpdateTier;
use App\Models\BillDiscount;
use App\Models\BillDiscountUsage;
use App\Models\Category;
use App\Models\ConfigIncentive;
use App\Models\ConfigIncentiveEmployee;
use App\Models\ConfigMerchant;
use App\Models\ConfigPrinter;
use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\EmployeeCommission;
use App\Models\IventoryItem;
use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemSubitem;
use App\Models\OrderTable;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PayoutConfig;
use App\Models\Point;
use App\Models\PointHistory;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Ranking;
use App\Models\RankingReward;
use App\Models\Reservation;
use App\Models\SaleHistory;
use App\Models\ShiftTransaction;
use App\Models\StockHistory;
use App\Models\Table;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\InventoryOutOfStock;
use App\Notifications\InventoryRunningOutOfStock;
use App\Notifications\OrderAssignedWaiter;
use App\Notifications\OrderCheckInCustomer;
use App\Notifications\OrderPlaced;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Request;
use App\Models\Zone;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\GdEscposImage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $reservedTablesId = $this->getReservedTablesId();

        $zones = Zone::with([
                            'tables' => fn ($query) => $query->where('state', 'active'),
                            'tables.orderTables' => fn ($query) => $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided', 'Order Merged']),
                            'tables.orderTables.order.orderItems.subItems:id,item_qty,order_item_id,serve_qty',
                            'tables.order:id,order_no,amount,voucher_id',
                        ])
                        ->where('status', 'active')
                        ->select('id', 'name')
                        ->get()
                        ->map(function ($zone) use ($reservedTablesId) {
                            return [
                                'text' => $zone->name,
                                'value' => $zone->id,
                                'tables' => $zone->tables->map(function ($table) use ($reservedTablesId) {
                                    $pendingCount = $table->orderTables->reduce(function ($pendingCount, $orderTable) {
                                        $orderPendingCount = $orderTable->order->orderItems->where('status', 'Pending Serve')->reduce(function ($itemCount, $orderItem) {
                                            $itemTotalQty = collect($orderItem->subItems)->reduce(function ($subTotal, $subItem) use ($orderItem) {
                                                return $subTotal + ($subItem['item_qty'] * $orderItem['item_qty'] - $subItem['serve_qty']);
                                            }, 0);
                                            return $itemCount + $itemTotalQty;
                                        }, 0);
                                        return $pendingCount + $orderPendingCount;
                                    }, 0);

                                    $table->orderTables->each(function ($orderTab) {
                                        $orderTab->order->orderItems->each(function ($item) {
                                            $item->product_name = $item->product->product_name;
                                            $item->bucket = $item->product->bucket;
                                            unset($item->product);
                                        });
                                    });
                                    
                                    // Find the first non-pending clearance table
                                    $currentOrderTable = $table->orderTables->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided'])
                                                                        ->firstWhere('status', '!=', 'Pending Clearance') 
                                                        ?? $table->orderTables->first();

                                    // Initialize joined_tables as empty array by default
                                    $table->joined_tables = [];
                                                        
                                    if ($currentOrderTable && $currentOrderTable->order) {
                                        // Eager load the necessary relationships
                                        $currentOrderTable->order->load([
                                            'orderTable' => function ($query) {
                                                $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided'])
                                                    ->select('id', 'order_id', 'table_id', 'status')
                                                    ->with('table:id,table_no');
                                            }
                                        ]);
                                        
                                        // Safely get joined tables
                                        if ($currentOrderTable->order->orderTable) {
                                            $table->joined_tables = $currentOrderTable->order->orderTable
                                                ->pluck('table.id')
                                                ->filter()
                                                ->values()
                                                ->toArray();
                                        }
                                    }

                                    $table->is_reserved = in_array($table->id, $reservedTablesId);
                                    $table->pending_count = $pendingCount;

                                    return $table;
                                }),
                            ];
                        });
        
        // dd($zones);

        // $waiters = Waiter::orderBy('id')->get();
        $users = User::select(['id', 'full_name', 'position'])
                        ->where('status', 'Active')
                        ->orderBy('id')
                        ->get();
        $users->each(function($user){
            $user->image = $user->getFirstMediaUrl('user');
        });

        // $orders = Order::with([
        //                     'orderItems:id,order_id,product_id,item_qty,amount,status', 
        //                     'orderItems.product:id,product_name', 
        //                     'orderTable:id,table_id,order_id', 
        //                     'orderTable.table:id,table_no', 
        //                     'waiter:id,full_name',
        //                     'customer:id,point',
        //                     'payment:id,order_id,status'
        //                 ])
        //                 ->orderByDesc('id')
        //                 ->get()
        //                 ->filter(fn ($order) => $order->status === 'Order Completed' || $order->status === 'Order Cancelled')
        //                 ->values();

        // $orders->each(function($order){
        //     if($order->waiter){
        //         $order->waiter->image = $order->waiter->getFirstMediaUrl('user');
        //     };
        // });

        $customers = Customer::orderBy('full_name')
                                ->get(['id', 'full_name', 'phone'])
                                ->map(function ($customer) {
                                    $customer->image = $customer->getFirstMediaUrl('customer');

                                    return $customer;
                                    // return [
                                        // 'text' => $customer->full_name,
                                        // 'value' => $customer->id,
                                        // 'image' => $customer->getFirstMediaUrl('customer'),
                                    // ];
                                });
                                
        $autoUnlockSetting = Setting::where('name', 'Table Auto Unlock')
                                    ->first(['name', 'value_type', 'value']);
        
        // Get the flashed messages from the session
        // $message = $request->session()->get('message');

        return Inertia::render('Order/Order', [
            // 'message' => $message ?? [],
            'zones' => $zones,
            'hasOpenedShift' => ShiftTransaction::hasOpenedShift(),
            'users' => $users,
            // 'orders' => $orders,
            'occupiedTables' => Table::where('status', '!=', 'Empty Seat')->get(),
            'customers' => $customers,
            'autoUnlockSetting' => $autoUnlockSetting,
        ]);
    }

    private function getReservedTablesId()
    {
        // $reservedTableIdsss = Reservation::whereNotIn('status', ['Cancelled', 'Completed', 'No show', 'Checked in']) // Adjust status if needed
        //                                 ->pluck('table_no') // Get JSON arrays of table_no
        //                                 ->flatMap(fn ($tableNos) =>
        //                                     array_map(fn ($table) => $table['id'], $tableNos) // Decode JSON only if it's a string
        //                                 )
        //                                 ->unique()
        //                                 ->toArray();
                                        
        $now = now();
    
        $reservations = Reservation::whereNotIn('status', ['Cancelled', 'Completed', 'No show', 'Checked in'])->get(); // Get all active reservations
        
        $reservedTableIds = [];
        
        foreach ($reservations as $reservation) {
            $reservationTime = Carbon::parse($reservation->reservation_date);
            $lockBeforeMinutes = $reservation->lock_before_minutes;
            $gracePeriod = $reservation->grace_period;

            $reservedTables = $this->extractTables($reservation->table_no);
            
            foreach ($reservedTables as $table) {
                if ($table->status === 'Empty Seat') {
                    // Case 1: Within pre-lock window (before reservation time)
                    $preLockStart = $reservationTime->copy()->subMinutes($lockBeforeMinutes);
                    // dd($preLockStart, $now >= $preLockStart, $now < $reservationTime, $now, $reservationTime);
                    if ($now >= $preLockStart && $now < $reservationTime) {
                        array_push($reservedTableIds, $table->id);
                        continue;
                    }
                    
                    // Case 2: Within grace period (after reservation time)
                    $gracePeriodEnd = $reservationTime->copy()->addMinutes($gracePeriod);
                    if ($now >= $reservationTime && $now <= $gracePeriodEnd) {
                        array_push($reservedTableIds, $table->id);
                    }
                } 
            };
        }

        
        return array_unique($reservedTableIds);
    }
    
    /**
     * Extract table IDs from reservation's table_no JSON.
     */
    private function extractTables($tableNos)
    {
        $tableIdArr = array_map(
            fn ($table) => $table['id'], 
            $tableNos
        );

        return Table::whereIn('id', $tableIdArr)
                    ->where('state', 'active')
                    ->get(['id', 'status', 'is_locked']);
    }

    /**
     * Store new order table.
     */
    public function storeOrderTable(OrderTableRequest $request)
    {
        $validatedData = $request->validated();

        $waiter = User::select('id', 'full_name')->find($validatedData['assigned_waiter']);
        $waiter->image = $waiter->getFirstMediaUrl('user');
        
        $tableString = $this->getTableName($validatedData['tables']);
        
        $newOrder = Order::create([
            'order_no' => RunningNumberService::getID('order'),
            'pax' => $validatedData['pax'],
            'user_id' => $validatedData['assigned_waiter'],
            'amount' => 0.00,
            'total_amount' => 0.00,
            'status' => 'Pending Serve',
        ]);

        foreach ($validatedData['tables'] as $selectedTable) {
            $table = Table::find($selectedTable);
            $table->update([
                'status' => 'Pending Order',
                'order_id' => $newOrder->id
            ]);
    
            OrderTable::create([
                'table_id' => $selectedTable,
                'pax' => $validatedData['pax'],
                'user_id' => $request->user_id,
                'status' => 'Pending Order',
                'order_id' => $newOrder->id
            ]);
            
        }

        //check in
        activity()->useLog('Order')
                    ->performedOn($newOrder)
                    ->event('check in')
                    ->withProperties([
                        'waiter_name' => $waiter->full_name,
                        'table_name' => $tableString, 
                        'waiter_image' => $waiter->image
                    ])
                    ->log("New customer check-in by :properties.waiter_name.");

        $toBeNotified = $waiter->id === auth()->user()->id
            ? User::where('position', 'admin')->get()
            : User::where('position', 'admin')
                    ->orWhere('id', $waiter->id)
                    ->whereNot('id', auth()->user()->id)
                    ->get();

        Notification::send($toBeNotified, new OrderCheckInCustomer($tableString, $waiter->full_name, $waiter->id));

        //assign to serve
        activity()->useLog('Order')
                    ->performedOn($newOrder)
                    ->event('assign to serve')
                    ->withProperties([
                        'waiter_name' => $waiter->full_name,
                        'waiter_image' => $waiter->image,
                        'table_name' => $tableString, 
                        'assigned_by' => auth()->user()->full_name,
                        'assigner_image' => auth()->user()->getFirstMediaUrl('user'),
                    ])
                    ->log("Assigned :properties.waiter_name to serve :properties.table_name.");

        Notification::send($toBeNotified, new OrderAssignedWaiter($tableString, auth()->user()->id, $waiter->id));

        return redirect()->back();
    }

    /**
     * Update reserved order table's details.
     */
    public function updateReservation(OrderTableRequest $request, string $id)
    {
        $validatedData = $request->validated();
        
        // if (!isset($id)) {
        //     $severity = 'error';
        //     $summary = "No reservation id found.";
        // }

        if (isset($id)) {
            $reservation = OrderTable::find($id);
            
            if ($reservation) {
                $reservation->update([
                    'table_id'=>$validatedData['table_id'],
                    'reservation' => $request->reservation ? 'reserved' : null,
                    'pax' => $validatedData['pax'],
                    'user_id' => $validatedData['user_id'],
                    'status' => $validatedData['status'],
                    'reservation_date' => $validatedData['reservation_date'],
                    'order_id' => $validatedData['order_id']
                ]);

                // $severity = 'success';
                // $summary = "Changes saved";
            } else {
                // $severity = 'error';
                // $summary = "No reservation found.";
            }
        }

        // $message = [ 
        //     'severity' => $severity, 
        //     'summary' => $summary
        // ];

        return redirect()->back();
    }

    /**
     * Delete reserved order table.
     */
    public function deleteReservation(Request $request, string $id)
    {
        $existingReservation = OrderTable::find($id);

        // $message = [ 
        //     'severity' => 'error', 
        //     'summary' => 'No reservation found.'
        // ];

        if ($existingReservation) {
            $existingReservation->delete();

            // $message = [ 
            //     'severity' => 'success', 
            //     'summary' => 'Selected reservation has been deleted successfully.'
            // ];
        }
        
        return redirect()->back();
    }

    /**
     * Store new item into order.
     */
    private function getTableName(array $tables) 
    {
        $orderTables = array_map(function ($table) {
            return Table::where('id', $table)->pluck('table_no')->first();
        }, $tables);
        
        return implode(', ', $orderTables);
    }

    /**
     * Store new item into order.
     */
    public function storeOrderItem(Request $request)
    {
        $orderItems = $request->items;
        $validatedOrderItems = [];
        $allItemErrors = [];
        $fixedOrderDetails = $request->matching_order_details;
        $addNewOrder = $fixedOrderDetails['current_order_completed'];
        $serveNow = $request->action_type === 'now' ? true : false;

        // dd($fixedOrderDetails['tables']);
        $tableString = $this->getTableName($fixedOrderDetails['tables']);
        $waiter = User::find($request->user_id);
        $waiter->image = $waiter->getFirstMediaUrl('user');

        foreach ($orderItems as $index => $item) {
            $rules = ['item_qty' => 'required|integer'];
            $requestMessages = ['item_qty.required' => 'This field is required.', 'item_qty.integer' => 'This field must be an integer.'];

            // Validate order items data
            $orderItemsValidator = Validator::make(
                $item,
                $rules,
                $requestMessages,
            );
            
            if ($orderItemsValidator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($orderItemsValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $orderItemsValidator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedOrderItems[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) {
            return redirect()->back()->withErrors($allItemErrors)->withInput();
        }

        if (count($validatedOrderItems) > 0) {
            if ($addNewOrder) {
                $newOrder = Order::create([
                    'order_no' => RunningNumberService::getID('order'),
                    'pax' => $fixedOrderDetails['pax'],
                    'user_id' => $fixedOrderDetails['assigned_waiter'],
                    'customer_id' => $fixedOrderDetails['customer_id'],
                    'amount' => 0.00,
                    'total_amount' => 0.00,
                    'status' => 'Pending Serve',
                ]);
        
                foreach ($fixedOrderDetails['tables'] as $selectedTable) {
                    $table = Table::find($selectedTable);
                    $table->update([
                        'status' => 'Pending Order',
                        'order_id' => $newOrder->id
                    ]);
            
                    OrderTable::create([
                        'table_id' => $selectedTable,
                        'pax' => $fixedOrderDetails['pax'],
                        'user_id' => $request->user_id,
                        'status' => 'Pending Order',
                        'order_id' => $newOrder->id
                    ]);
                }
                $newOrder->refresh();
            }

            $temp = 0.00;
            $totalDiscountedAmount = 0.00;

            foreach ($orderItems as $key => $item) {
                $status = $serveNow ? 'Served' : 'Pending Serve'; 
                $product = Product::select('id', 'discount_id')->find($item['product_id']);

                $originalItemAmount = $item['price'] * $item['item_qty'];
                $currentProductDiscount = $product->discountSummary($product->discount_id)?->first();
                $newItemAmount = round($currentProductDiscount ? $currentProductDiscount['price_after'] * $item['item_qty'] : $originalItemAmount, 2);

                $new_order_item = OrderItem::create([
                    'order_id' => $addNewOrder ? $newOrder->id : $request->order_id,
                    'user_id' => $request->user_id,
                    'type' => 'Normal',
                    'product_id' => $item['product_id'],
                    'item_qty' => $item['item_qty'],
                    'amount_before_discount' => $originalItemAmount,
                    'discount_id' => $currentProductDiscount ? $currentProductDiscount['id'] : null,
                    'discount_amount' => $originalItemAmount - $newItemAmount,
                    'amount' => $newItemAmount,
                    'status' => $status,
                ]);

                $totalDiscountedAmount += $currentProductDiscount ? ($currentProductDiscount['price_before'] - $currentProductDiscount['price_after']) * $item['item_qty'] : 0.00;

                $toBeNotified = $waiter->id === $fixedOrderDetails['assigned_waiter']
                    ? User::where('position', 'admin')->get()
                    : User::where('position', 'admin')
                            ->orWhere('id', $fixedOrderDetails['assigned_waiter'])
                            ->whereNot('id', $waiter->id)
                            ->get();

                Notification::send($toBeNotified, new OrderPlaced($tableString, $waiter->full_name, $waiter->id));

                // placed an order for {{table name}}.
                activity()->useLog('Order')
                            ->performedOn($new_order_item)
                            ->event('place to order')
                            ->withProperties([
                                'waiter_name' => $waiter->full_name, 
                                'table_name' => $tableString,
                                'waiter_image' => $waiter->image,
                            ])
                            ->log("placed an order for :properties.table_name.");

                if (count($item['product_items']) > 0) {
                    $temp += $newItemAmount;
                    
                    foreach ($item['product_items'] as $key => $value) {
                        $productItem = ProductItem::with('inventoryItem:id,item_name,stock_qty,item_cat_id,inventory_id,low_stock_qty,current_kept_amt')->find($value['id']);
                        $inventoryItem = $productItem->inventoryItem;
    
                        // Deduct stock
                        $stockToBeSold = $value['qty'] * $item['item_qty'];
                        $oldStockQty = $inventoryItem->stock_qty;
                        $newStockQty = $oldStockQty - $stockToBeSold;

                        $newStatus = match(true) {
                            $newStockQty == 0 => 'Out of stock',
                            $newStockQty <= $inventoryItem->low_stock_qty => 'Low in stock',
                            default => 'In stock'
                        };

                        $inventoryItem->update([
                            'stock_qty' => $newStockQty,
                            'status' => $newStatus,
                            'current_kept_amt' => $newStockQty < 0 ? $inventoryItem->current_kept_amt + $newStockQty : $inventoryItem->current_kept_amt
                        ]);
                        $inventoryItem->refresh();
    
                        StockHistory::create([
                            'inventory_id' => $inventoryItem->inventory_id,
                            'inventory_item' => $inventoryItem->item_name,
                            'old_stock' => $oldStockQty,
                            'in' => 0,
                            'out' => $stockToBeSold,
                            'current_stock' => $inventoryItem->stock_qty,
                            'kept_balance' => $inventoryItem->current_kept_amt
                        ]);
                        
                        $serveQty = $serveNow ? $value['qty'] * $item['item_qty'] : 0;
                        OrderItemSubitem::create([
                            'order_item_id' => $new_order_item->id,
                            'product_item_id' => $value['id'],
                            'item_qty' => $value['qty'],
                            'serve_qty' => $serveQty,
                        ]);

                        if($newStatus === 'Out of stock'){
                            Notification::send(User::where('position', 'admin')->get(), new InventoryOutOfStock($inventoryItem->item_name, $inventoryItem->id));
                        };

                        if($newStatus === 'Low in stock'){
                            Notification::send(User::where('position', 'admin')->get(), new InventoryRunningOutOfStock($inventoryItem->item_name, $inventoryItem->id));
                        }
                    }
                }
            }
        
            $order = Order::with('orderTable.table')->find($addNewOrder ? $newOrder->id : $request->order_id);

            if ($order) {
                $statusArr = collect($order->orderItems->pluck('status')->unique());
                $orderStatus = 'Pending Serve';
                $orderTableStatus = 'Pending Order';
            
                if ($statusArr->contains('Pending Serve')) {
                    $orderStatus = 'Pending Serve';
                    $orderTableStatus = 'Order Placed';
                } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
                    $orderStatus = 'Order Served';
                    $orderTableStatus = 'All Order Served';
                } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
                    $orderStatus = 'Order Served';
                    $orderTableStatus = 'All Order Served';
                }
                
                $order->update([
                    'amount' => $order->amount + $temp,
                    'total_amount' => $order->amount + $temp,
                    'discount_amount' => $order->discount_amount + $totalDiscountedAmount,
                    'status' => $orderStatus
                ]);
                
                // Update all tables associated with this order
                $order->orderTable->each(function ($tab) use ($orderTableStatus) {
                    $tab->table->update(['status' => $orderTableStatus]);
                    $tab->update(['status' => $orderTableStatus]);
                });
            }
        }
    
        return response()->json($order->id);
    }

    /**
     * Get all products.
     */
    public function getAllProducts()
    {
        $products = Product::with([
                                'productItems.inventoryItem', 
                                'category:id,name', 
                            ])
                            ->where('availability', 'Available')
                            ->orderBy('product_name')
                            ->get()
                            ->map(function ($product) {
                                $product_items = $product->productItems;
                                $minStockCount = 0;
                                $product->image = $product->getFirstMediaUrl('product');
                                $product->discount_item = $product->discountSummary($product->discount_id)?->first(); 
                                unset($product->discountItems);

                                if (count($product_items) > 0) {
                                    $stockCountArr = [];

                                    foreach ($product_items as $key => $value) {
                                        $inventory_item = IventoryItem::select('stock_qty')->find($value['inventory_item_id']);

                                        $stockQty = $inventory_item->stock_qty;
                                        
                                        $stockCount = (int)bcdiv($stockQty, (int)$value['qty']);
                                        array_push($stockCountArr, $stockCount);
                                    }
                                    $minStockCount = min($stockCountArr);
                                }
                                $product['stock_left'] = $minStockCount; 
                                $product['category_name'] = $product->category->name;
                                
                                unset($product->category);

                                return $product;
                            });
                            
        return response()->json($products);
    }

    /**
     * Get order details with its items.
     */
    public function getCurrentTableOrder(string $id)
    {
        // Fetch only the main order tables first, selecting only necessary columns
        $orderTables = OrderTable::select('id', 'table_id', 'status', 'updated_at', 'order_id')
                                    ->with('table:id,table_no,status')
                                    ->where('table_id', $id)
                                    ->orderByDesc('updated_at')
                                    ->get();

        // Find the first non-pending clearance table
        $currentOrderTable = $orderTables->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided'])
                                            ->firstWhere('status', '!=', 'Pending Clearance') 
                            ?? $orderTables->first();
                            
        if ($currentOrderTable) {
            // Lazy load relationships only for the selected table
            $currentOrderTable->load([
                'order.orderItems.product.discount:id,name',
                'order.orderItems.product.productItems.inventoryItem',
                'order.orderItems.productDiscount:id,discount_id,price_before,price_after',
                'order.orderItems.productDiscount.discount:id,name',
                'order.orderItems.handledBy:id,full_name',
                'order.orderItems.subItems.keepItems.keepHistories' => function ($query) {
                    $query->where('status', 'Keep')->latest()->offset(1)->limit(100);
                },
                'order.orderItems.subItems.keepItems.oldestKeepHistory' => function ($query) {
                    $query->where('status', 'Keep');
                },
                'order.orderItems.keepItem:id,qty,cm,remark,expired_from,expired_to', 
                'order.orderItems.keepItem.oldestKeepHistory:id,keep_item_id,qty,cm,status',
                'order.orderItems.keepItem.keepHistories' => function ($query) {
                    $query->where('status', 'Keep')->oldest()->offset(1)->limit(100);
                },
                'order.waiter:id,full_name',
                'order.customer:id,full_name,email,phone,point,ranking',
                'order.customer.billDiscountUsages',
                'order.orderTable' => function ($query) {
                    $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided'])
                            ->select('id', 'order_id', 'table_id', 'status');
                },
                'order.orderTable.table:id,table_no',
                'order.payment:id,order_id',
                'order.voucher:id,ranking_id,reward_type,min_purchase,discount,min_purchase_amount,bonus_point,free_item,item_qty,updated_at',
                'order.voucher.ranking:id,name',
                'order.voucher.product:id,product_name,availability',
                'order.voucher.product.productItems',
                'order.voucher.customerReward:id,ranking_reward_id,customer_id'
            ]);

            if ($currentOrderTable->order->orderItems) {
                foreach ($currentOrderTable->order->orderItems as $orderItem) {
                    $orderItem->product->image = $orderItem->product->getFirstMediaUrl('product');
                    $orderItem->handledBy->image = $orderItem->handledBy->getFirstMediaUrl('user');
                    $orderItem->product->discount_item = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first();
                    unset($orderItem->product->discountItems);
                }
            }

            if ($currentOrderTable->order->customer) {
                $currentOrderTable->order->customer->image = $currentOrderTable->order->customer->getFirstMediaUrl('customer');

                if ($currentOrderTable->order->voucher) {
                    $currentOrderTable->order->voucher->customer_reward_id = $currentOrderTable->order->voucher->customerReward->where('customer_id', $currentOrderTable->order->customer['id'])->first()->id;
                    unset($currentOrderTable->order->voucher->customerReward);
                }
            }

            $reservedTablesId = $this->getReservedTablesId();

            $currentTable = Table::with([
                            'orderTables' => fn ($query) => $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided', 'Order Merged']),
                            'orderTables.order.orderItems.subItems:id,item_qty,order_item_id,serve_qty'
                        ])
                        ->find($id);
            
            $pendingCount = $currentTable->orderTables->reduce(function ($pendingCount, $orderTable) {
                $orderPendingCount = $orderTable->order->orderItems->where('status', 'Pending Serve')->reduce(function ($itemCount, $orderItem) {
                    $itemTotalQty = collect($orderItem->subItems)->reduce(function ($subTotal, $subItem) use ($orderItem) {
                        return $subTotal + ($subItem['item_qty'] * $orderItem['item_qty'] - $subItem['serve_qty']);
                    }, 0);
                    return $itemCount + $itemTotalQty;
                }, 0);
                return $pendingCount + $orderPendingCount;
            }, 0);

            $currentTable->orderTables->each(function ($orderTab) {
                $orderTab->order->orderItems->each(function ($item) {
                    $item->product_name = $item->product->product_name;
                    $item->bucket = $item->product->bucket;
                    unset($item->product);
                });
            });

            $currentTable->is_reserved = in_array($currentTable->id, $reservedTablesId);
            $currentTable->pending_count = $pendingCount;
            
            $data = [
                'currentTable' => $currentTable,
                'currentOrderTable' => $currentOrderTable->table,
                'order' => $currentOrderTable->order
            ];
        } else {
            $data = null;
        }

        return response()->json($data);
    }

    /**
     * Cancel the order along with its items.
     */
    public function cancelOrder(string $id)
    {
        return DB::transaction(function () use ($id) {
            $existingOrder = Order::with([
                                        'orderItems.subItems.productItem.inventoryItem:id,item_name,stock_qty,low_stock_qty,inventory_id,current_kept_amt', 
                                        'orderTable.table',
                                    ])->find($id);

            if ($existingOrder) {
                $isAllKeepType = $existingOrder->orderItems->every(function ($orderItem, int $key) {
                    return $orderItem->type === 'Keep';
                });

                foreach ($existingOrder->orderItems as $item) { 
                    // NEEED TO ADD  CONDITION TO CHECK WHHETHER THERE ARE ANY KEEP ITEM CREATED USING THE ORDER ITEM IF YES, THEN ONLY RETURN THE QTY AFTER DEDUCTING THE QTY FROM THE NEW KEEP ITEM. IF NO THEN RETURN ALL


                    // if ($item['status'] === 'Pending Serve') {
                    if ($item['type'] === 'Normal') {
                        foreach ($item->subItems as $subItem) {
                            $totalQtyReturned = 0;
                            $totalInitialKeptQty = 0;

                            $keepItems = KeepItem::with([
                                                    'orderItemSubitem.productItem.inventoryItem',
                                                    'oldestKeepHistory' => function ($query) {
                                                        $query->where('status', 'Keep');
                                                    },
                                                ])
                                                ->where('order_item_subitem_id', $subItem->id)
                                                ->get(); 

                            if ($keepItems && $keepItems->count() > 0) {
                                $keepItems->each(function ($ki) use ($totalQtyReturned, $totalInitialKeptQty) {
                                    $ki->update(['status' => 'Deleted']); 
    
                                    activity()->useLog('delete-kept-item')
                                                ->performedOn($ki)
                                                ->event('deleted')
                                                ->withProperties([
                                                    'edited_by' => auth()->user()->full_name,
                                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                                    'item_name' => $ki->orderItemSubitem->productItem->inventoryItem->item_name,
                                                ])
                                                ->log(":properties.item_name is deleted.");
                                                
                                    KeepHistory::create([
                                        'keep_item_id' => $ki->id,
                                        'qty' => $ki->qty,
                                        'cm' => number_format((float) $ki->cm, 2, '.', ''),
                                        'keep_date' => $ki->created_at,
                                        'remark' => 'Cancelled order',
                                        'user_id' => auth()->user()->id,
                                        'kept_from_table' => $ki->kept_from_table,
                                        'status' => 'Deleted',
                                    ]);

                                    $totalQtyReturned += $ki->qty;
                                    $totalInitialKeptQty += (int)$ki->oldestKeepHistory['qty'];
                                });

                            }

                            $inventoryItem = $subItem->productItem->inventoryItem;
                            
                            // $qtySold = $subItem['serve_qty'];
                            // $restoredQty = $item['item_qty'] * $subItem['item_qty'] - $qtySold;
                            $restoredQty = $keepItems->count() > 0 
                                    ? ($item['item_qty'] * $subItem['item_qty'] - $totalInitialKeptQty) +  $totalQtyReturned
                                    : $item['item_qty'] * $subItem['item_qty'];
                                    
                            $oldStockQty = $inventoryItem->stock_qty;
                            $newStockQty = $oldStockQty + $restoredQty;
        
                            // Update inventory with restored stock
                            $newStatus = match(true) {
                                $newStockQty === 0 => 'Out of stock',
                                $newStockQty <= $inventoryItem->low_stock_qty => 'Low in stock',
                                default => 'In stock'
                            };

                            $inventoryItem->update([
                                'stock_qty' => $newStockQty,
                                'status' => $newStatus
                            ]);
                            $inventoryItem->refresh();
        
                            if ($restoredQty > 0) {
                                StockHistory::create([
                                    'inventory_id' => $inventoryItem->inventory_id,
                                    'inventory_item' => $inventoryItem->item_name,
                                    'old_stock' => $oldStockQty,
                                    'in' => $restoredQty,
                                    'out' => 0,
                                    'current_stock' => $inventoryItem->stock_qty,
                                    'kept_balance' => $inventoryItem->current_kept_amt,
                                ]);
                            }
                        }
                    }
                    
                    if ($item['type'] === 'Keep') {
                        $keepItem = KeepItem::with('oldestKeepHistory')->find($item['keep_item_id']);
                        $keepType = $keepItem->oldestKeepHistory->qty > $keepItem->oldestKeepHistory->cm ? 'qty' : 'cm';

                        if ($keepType === 'qty') {
                            $totalQtyReturned = 0;
                            $totalInitialKeptQty = 0;

                            $keepItems = KeepItem::with([
                                                    'orderItemSubitem.productItem.inventoryItem',
                                                    'oldestKeepHistory' => function ($query) {
                                                        $query->where('status', 'Keep');
                                                    },
                                                ])
                                                ->where('order_item_subitem_id', $item->subItems[0]['id'])
                                                ->get(); 

                            if ($keepItems && $keepItems->count() > 0) {
                                $keepItems->each(function ($ki) use ($totalQtyReturned, $totalInitialKeptQty) {
                                    $ki->update(['status' => 'Deleted']); 
    
                                    activity()->useLog('delete-kept-item')
                                                ->performedOn($ki)
                                                ->event('deleted')
                                                ->withProperties([
                                                    'edited_by' => auth()->user()->full_name,
                                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                                    'item_name' => $ki->orderItemSubitem->productItem->inventoryItem->item_name,
                                                ])
                                                ->log(":properties.item_name is deleted.");
                                                
                                    KeepHistory::create([
                                        'keep_item_id' => $ki->id,
                                        'qty' => $ki->qty,
                                        'cm' => number_format((float) $ki->cm, 2, '.', ''),
                                        'keep_date' => $ki->created_at,
                                        'remark' => 'Cancelled order',
                                        'user_id' => auth()->user()->id,
                                        'kept_from_table' => $ki->kept_from_table,
                                        'status' => 'Deleted',
                                    ]);

                                    $totalQtyReturned += $ki->qty;
                                    $totalInitialKeptQty += (int)$ki->oldestKeepHistory['qty'];
                                });

                            }

                            $returnKeepQty = $keepItems->count() > 0 
                                    ? ($item['item_qty'] - $totalInitialKeptQty) + $totalQtyReturned 
                                    : $item['item_qty'];
                            
                            $keepItem->update([
                                'qty' => $keepItem->qty + $returnKeepQty,
                                'status' => 'Keep',
                            ]);
        
                            $keepItem->save();
                            $keepItem->refresh();
        
                            $associatedSubItem = OrderItemSubitem::where('id', $keepItem['order_item_subitem_id'])
                                                                    ->with(['productItem:id,inventory_item_id'])
                                                                    ->first();
                            $tempInventoryItem = $associatedSubItem->productItem->inventory_item_id;
                            $tempOrderItem = IventoryItem::find($tempInventoryItem);
                            
                            KeepHistory::create([
                                'keep_item_id' => $item['keep_item_id'],
                                'order_item_id' => $item['order_item_id'],
                                'qty' => round($returnKeepQty, 2),
                                'cm' => '0.00',
                                'keep_date' => $keepItem->updated_at,
                                'kept_balance' => $tempOrderItem->current_kept_amt + $returnKeepQty,
                                'user_id' => auth()->user()->id,
                                'kept_from_table' => $keepItem->kept_from_table,
                                'status' => 'Keep',
                            ]);
                            
                            $tempOrderItem->increment('total_kept', $returnKeepQty);
                            $tempOrderItem->increment('current_kept_amt', $returnKeepQty);
                        }
                    }
                    // }

                    // $item->update(['status' => $isAllKeepType ? 'Voided' : 'Cancelled']);
                    $item->update(['status' => 'Cancelled']);
                }
                
                // $existingOrder->update(['status' => $isAllKeepType ? 'Order Voided' : 'Order Cancelled']);
                $existingOrder->update(['status' => 'Order Cancelled']);

                // Update all tables associated with this order
                $existingOrder->orderTable->each(function ($tab) use ($isAllKeepType) {
                    $tab->table->update([
                        'status' => 'Pending Clearance',
                        // 'order_id' => null
                    ]);
                    $tab->update(['status' => 'Pending Clearance']);
                });
            }

            return redirect()->back();
        });
    }

    /**
     * Serve order item.
     */
    public function updateOrderItem(Request $request, string $id)
    {        
        if (isset($id)) {
            $orderItem = OrderItem::with(['subItems', 'product', 'keepHistory'])->find($id);
            $subItems = $orderItem->subItems;
            
            if ($subItems) {
                $totalItemQty = 0;
                $totalServedQty = 0;
                $hasServeQty = false;

                foreach ($subItems as $key => $item) {
                    foreach ($request->items as $key => $updated_item) {
                        if ($item['id'] === $updated_item['sub_item_id']) {
                            $item->update(['serve_qty' => $item['serve_qty'] + $updated_item['serving_qty']]);
                            $item->save();
                            $item->refresh();

                            $totalItemQty += $item['item_qty'] * $orderItem->item_qty;
                            $totalServedQty += $item['serve_qty'];
                            $hasServeQty = $updated_item['serving_qty'] > 0 || $hasServeQty ? true : false;
                        }
                    }
                }
                
                if ($hasServeQty) {
                    $orderItem->update(['status' => $totalServedQty === $totalItemQty ? 'Served' : 'Pending Serve']);
                    $orderItem->save();
                }
            }
            
            $orderItem->refresh();

            $order = Order::with(['orderTable.table', 'orderItems'])->find($orderItem->order_id);
            $orderTableNames = implode(", ", $order->orderTable->map(fn ($order_table) => $order_table['table']['table_no'])->toArray());

            if ($orderItem->status === 'Served' && $orderItem->type === 'Keep' && $orderItem->keep_item_id) {
                $keepItem = KeepItem::find($orderItem->keep_item_id);
                $keepHistory = $orderItem->keepHistory;

                $keepItem->update(['status' => 'Served']);

                KeepHistory::create([
                    'keep_item_id' => $orderItem->keep_item_id,
                    'order_item_id' => $orderItem->id,
                    'qty' => $keepHistory->qty,
                    'cm' => number_format((float) $keepHistory->cm, 2, '.', ''),
                    'keep_date' => $keepItem->created_at,
                    'user_id' => auth()->user()->id,
                    'kept_from_table' => $keepItem->kept_from_table,
                    'redeemed_to_table' => $orderTableNames,
                    'status' => 'Served',
                ]);
            }
            
            if ($order) {
                $allOrderItems = $order->orderItems;
                $statusArr = [];
                $orderStatus = 'Pending Serve';
                $orderTableStatus = 'Pending Order';
    
                foreach ($allOrderItems as $key => $item) {
                    array_push($statusArr, $item['status']);
                }
    
                $uniqueStatuses = array_unique($statusArr);
    
                if (in_array("Pending Serve", $uniqueStatuses)) {
                    $orderStatus = 'Pending Serve';
                    $orderTableStatus = 'Order Placed';
                }
    
                if (count($uniqueStatuses) === 1) {
                    if ($uniqueStatuses[0] === 'Served' || $uniqueStatuses[0] === 'Cancelled') {
                        $orderStatus = 'Order Served';
                        $orderTableStatus = 'All Order Served';
                    }
                } else if (count($uniqueStatuses) === 2) {
                    if (in_array('Served', $uniqueStatuses) && in_array('Cancelled', $uniqueStatuses)) {
                        $orderStatus = 'Order Served';
                        $orderTableStatus = 'All Order Served';
                    }
                }

                $allClearance = $order->orderTable->every(function($table){
                    return in_array($table->status, ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided']);
                });

                if(($request->input('current_order_id') === $request->input('order_id')) && !$allClearance){
                    // Update all tables associated with this order
                    $order->orderTable->each(function ($tab) use ($orderTableStatus) {
                        $table = Table::find($tab['table_id']);
                        $table->update(['status' => $orderTableStatus]);
                        $tab->update(['status' => $orderTableStatus]);
                    });
    
                    $order->update(['status' => $orderStatus]);
                }
            }
        }

        return redirect()->back();
    }
    
    /**
     * Get all zones along with its tables and order.
     */
    public function getAllZones(Request $request)
    {
        $autoUnlockSetting = Setting::where('name', 'Table Auto Unlock')
                                    ->first(['name', 'value_type', 'value']);

        $duration = $autoUnlockSetting->value_type === 'minutes'
            ? ((int)floor($autoUnlockSetting->value ?? 0)) * 60
            : ((int)floor($autoUnlockSetting->value ?? 0));

        $lockedTables = collect($request->locked_tables)->map(fn ($t) => $t['tableId']);

        Table::where([
                    ['locked_by', auth()->user()->id],
                    ['updated_at', '>', now()->subSeconds($duration)],
                ])
                ->whereIn('id', $lockedTables)
                ->update(['updated_at' => now()]);

        Table::where([
                    ['locked_by', auth()->user()->id],
                    ['updated_at', '<', now()->subSeconds(5)],
                ])
                ->whereNotIn('id', $lockedTables)
                ->update([
                    'is_locked' => false,
                    'locked_by' => null,
                ]);

        $reservedTablesId = $this->getReservedTablesId();

        $zones = Zone::with([
                            'tables' => fn ($query) => $query->where('state', 'active'),
                            'tables.orderTables' => fn ($query) => $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided', 'Order Merged']),
                            'tables.orderTables.order.orderItems.subItems:id,item_qty,order_item_id,serve_qty',
                            'tables.order:id,order_no,amount,voucher_id,customer_id',
                        ])
                        ->where('status', 'active')
                        ->select('id', 'name')
                        ->get()
                        ->map(function ($zone) use ($reservedTablesId) {
                            return [
                                'text' => $zone->name,
                                'value' => $zone->id,
                                'tables' => $zone->tables->map(function ($table) use ($reservedTablesId) {
                                    $pendingCount = $table->orderTables->reduce(function ($pendingCount, $orderTable) {
                                        $orderPendingCount = $orderTable->order->orderItems->where('status', 'Pending Serve')->reduce(function ($itemCount, $orderItem) {
                                            $itemTotalQty = collect($orderItem->subItems)->reduce(function ($subTotal, $subItem) use ($orderItem) {
                                                return $subTotal + ($subItem['item_qty'] * $orderItem['item_qty'] - $subItem['serve_qty']);
                                            }, 0);
                                            return $itemCount + $itemTotalQty;
                                        }, 0);
                                        return $pendingCount + $orderPendingCount;
                                    }, 0);

                                    $table->orderTables->each(function ($orderTab) {
                                        $orderTab->order->orderItems->each(function ($item) {
                                            $item->product_name = $item->product->product_name;
                                            $item->bucket = $item->product->bucket;
                                            unset($item->product);
                                        });
                                    });
                                    
                                    // Find the first non-pending clearance table
                                    $currentOrderTable = $table->orderTables->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided'])
                                                                        ->firstWhere('status', '!=', 'Pending Clearance') 
                                                        ?? $table->orderTables->first();

                                    // Initialize joined_tables as empty array by default
                                    $table->joined_tables = [];
                                                        
                                    if ($currentOrderTable && $currentOrderTable->order) {
                                        // Eager load the necessary relationships
                                        $currentOrderTable->order->load([
                                            'orderTable' => function ($query) {
                                                $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided'])
                                                    ->select('id', 'order_id', 'table_id', 'status')
                                                    ->with('table:id,table_no');
                                            }
                                        ]);
                                        
                                        // Safely get joined tables
                                        if ($currentOrderTable->order->orderTable) {
                                            $table->joined_tables = $currentOrderTable->order->orderTable
                                                ->pluck('table.id')
                                                ->filter()
                                                ->values()
                                                ->toArray();
                                        }
                                    }
                    
                                    $table->is_reserved = in_array($table->id, $reservedTablesId);
                                    $table->pending_count = $pendingCount;
                                    
                                    return $table;
                                }),
                            ];
                        });

        $data = [
            'zones' => $zones,
            'auto_unlock_timer' => $autoUnlockSetting,
        ];

        return response()->json($data);
    }

    /**
     * Rounds off the amount based on the bank negara rounding mechanism.
     */
    private function priceRounding(float $amount)
    {
        // Get the decimal part in cents
        $cents = round(($amount - floor($amount)) * 100);
        
        // Determine rounding based on the last digit of cents
        $lastDigit = $cents % 10;
    
        if (in_array($lastDigit, [1, 2, 6, 7])) {
            // Round down to the nearest multiple of 5
            $cents = ($cents - $lastDigit) + ($lastDigit < 5 ? 0 : 5);
        } elseif (in_array($lastDigit, [3, 4, 8, 9])) {
            // Round up to the nearest multiple of 5
            $cents = ($cents + 5) - $lastDigit % 5;
        }
    
        // Calculate the final rounded amount
        $roundedAmount = floor($amount) + $cents / 100;
        
        return $roundedAmount;
    }

    /**
     * Update order status to completed once all order items are served.
     */
    public function updateOrderStatus(Request $request, string $id)
    {
        if (!$id) return redirect()->back();

        $order = Order::with([
                            'orderTable.table', 
                            'payment', 
                            'voucher:id,reward_type,discount',
                            'orderItems' => fn($query) => $query->where('status', 'Served')->orWhere('status', 'Pending Serve')
                        ])->find($id);

        if (!$order) return redirect()->back();
        
        if ($request->action_type === 'clear') {
            $toBeClearedOrderTables = OrderTable::with([
                                            'table', 
                                            'order.reservation',
                                            'order.orderTable.table',
                                            'order.orderItems' => fn($query) => $query->where('status', 'Served'),
                                        ])
                                        ->where('status', 'Pending Clearance')
                                        ->whereIn('table_id', $order->orderTable->pluck('table.id'))
                                        ->orderBY('id')
                                        ->get();

            // Get unique orders and include each order's payment details
            $uniqueOrders = $toBeClearedOrderTables->pluck('order')->unique('id');

            // Update all tables associated with this order
            $uniqueOrders->each(function ($order) {
                $order->orderTable->each(function ($tab) use ($order) {
                    if ($tab->table['status'] !== 'Empty Seat' && $tab->table['order_id'] !== null) {
                        $tab->table->update([
                            'status' => 'Empty Seat',
                            'order_id' => null
                        ]);
                    }

                    if (!in_array($tab->status, ['Order Cancelled', 'Order Voided'])) { 
                        $tab->update(['status' => $order->status]);
                    }
                });

                $order->reservation?->update(['status' => 'Completed']);
            });
        }

        return redirect()->back();
    }
    
    /**
     * Get date filtered order histories.
     */
    public function getOrderHistories(Request $request)
    {
        $dateFilter = $request->input('dateFilter');
        $query = Order::query();
        
        if ($dateFilter) {
            $dateFilter = array_map(function ($date) {
                                return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
                            }, $dateFilter);

            // Apply the date filter (single date or date range)
            $query->whereDate('updated_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                                    ->when(count($dateFilter) > 1, function ($subQuery) use ($dateFilter) {
                                        $subQuery->whereDate('updated_at', '<=', $dateFilter[1]);
                                    });
        }

        // Ensure `date` condition applies to all `status` filters
        $query->where(function ($query) {
            $query->whereIn('status', ['Order Cancelled', 'Order Voided'])
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('status', 'Order Completed')
                            ->whereHas('payment', function ($paymentQuery) {
                                $paymentQuery->where('status', 'Successful');
                            });
                    });
        });

        $orders = $query->with([
                            'orderTable:id,table_id,order_id', 
                            'orderTable.table:id,table_no', 
                            'waiter:id,full_name',
                        ])
                        ->orderBy('updated_at', 'desc')
                        ->get()
                        ->values();
        // dd($query->toSql(), $query->getBindings());

        return response()->json($orders);
    }

    /**
     * Get order payment details.
     */
    public function getOrderPaymentDetails(Request $request, string $id)
    {
        $order = Order::with([
                            'orderItems:id,order_id,type,product_id,item_qty,amount_before_discount,discount_id,discount_amount,amount,status', 
                            'orderItems.product:id,product_name,price,bucket,discount_id', 
                            'orderItems.product.discount:id,name', 
                            'orderItems.productDiscount:id,discount_id',
                            'orderItems.productDiscount.discount:id,name',
                            'orderTable:id,table_id,order_id', 
                            'orderTable.table:id,table_no', 
                            'waiter:id,full_name',
                            'customer:id,full_name,dial_code,phone,email,ranking,point',
                            'customer.rank:id,name',
                            'payment:id,order_id,receipt_no,receipt_end_date,total_amount,rounding,sst_amount,service_tax_amount,discount_id,discount_amount,bill_discounts,bill_discount_total,grand_total,point_earned,pax,status',
                            'payment.pointHistory:id,payment_id,amount,new_balance',
                            'payment.voucher:id,reward_type,discount',
                        ])
                        ->orderBy('updated_at', 'desc')
                        ->find($id);

        foreach ($order->orderItems as $orderItem) {
            $orderItem->product->discount_item = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first();
            unset($orderItem->product->discountItems);
        }

        if ($order->payment) {
            // $order->payment->applied_discounts = isset($order->payment->bill_discounts) 
            //     ? BillDiscount::whereIn('id', $order->payment->bill_discounts)
            //                     ->get(['id', 'name', 'discount_type', 'discount_rate'])
            //     : null; // return null if no discounts

            // Also check if bill_discounts exists and is not null
            if ($order->payment->bill_discounts) {
                $discountIds = [];
                $manualDiscounts = [];

                foreach ($order->payment->bill_discounts as $discount) {
                    if (is_numeric($discount)) {
                        // It's an ID reference
                        $discountIds[] = $discount;
                    } elseif (is_array($discount)) {
                        // It's a manual discount object
                        $manualDiscounts[] = $discount;
                    }
                }

                // Get database discounts for the IDs
                $databaseDiscounts = [];
                if (!empty($discountIds)) {
                    $databaseDiscounts = BillDiscount::whereIn('id', $discountIds)
                        ->get(['id', 'name', 'discount_type', 'discount_rate'])
                        ->toArray();
                }

                // Combine both types of discounts
                $order->payment->applied_discounts = array_merge($databaseDiscounts, $manualDiscounts);

            } else {
                $order->payment->applied_discounts = null;
            }
        }

        if($order->waiter){
            $order->waiter->image = $order->waiter->getFirstMediaUrl('user');
        };

        $taxes = Setting::whereIn('name', ['SST', 'Service Tax'])->pluck('value', 'name');

        $merchant = ConfigMerchant::select('id', 'merchant_name', 'merchant_contact', 'merchant_address_line1', 'merchant_address_line2', 'postal_code', 'area', 'state')->first();

        if ($merchant) {
            $merchant->image = $merchant->getFirstMediaUrl('merchant_settings');
        }

        $data = [
            'order' => $order,
            'taxes' => $taxes,
            'merchant' => $merchant
        ];

        return response()->json($data);
    }
    
    /**
     * Get all categories.
     */
    public function getAllCategories()
    {
        $data = Category::select(['id', 'name'])
                        ->orderBy('id')
                        ->get()
                        ->map(function ($category) {
                            return [
                                'text' => $category->name,
                                'value' => $category->id
                            ];
                        });

        return response()->json($data);
    }

    /**
     * Update order item's status and/or quantity.
     */
    public function removeOrderItem(Request $request, string $id)
    {        
        if (isset($id)) {
            $order = Order::with(['orderItems.product', 'orderTable.table'])->find($id);
            $orderItems = $order->orderItems;
            
            if ($orderItems) {
                $cancelledAmount = 0;

                foreach ($orderItems as $key => $item) {
                    foreach ($request->items as $key => $updated_item) {
                        if ($item['id'] === $updated_item['order_item_id']) {
                            // Restore stock for each product item
                            $subItems = OrderItemSubitem::where('order_item_id', $item['id'])->get();
                                                
                            foreach ($subItems as $subItem) {
                                $productItem = ProductItem::with('inventoryItem:id,item_name,stock_qty,low_stock_qty,inventory_id,current_kept_amt')->find($subItem->product_item_id);
                                $inventoryItem = $productItem->inventoryItem;
                                
                                // $qtySold = $subItem['serve_qty'];
                                $restoredQty = $updated_item['remove_qty'] * $subItem['item_qty'];
                                $oldStockQty = $inventoryItem->stock_qty;
                                $newStockQty = $oldStockQty + $restoredQty;

                                // Update inventory with restored stock
                                $newStatus = match(true) {
                                    $newStockQty === 0 => 'Out of stock',
                                    $newStockQty <= $inventoryItem->low_stock_qty => 'Low in stock',
                                    default => 'In stock'
                                };
            
                                $inventoryItem->update([
                                    'stock_qty' => $newStockQty,
                                    'status' => $newStatus
                                ]);
                                $inventoryItem->refresh();

                                $subItem->update([
                                    'serve_qty' => ($subItem['serve_qty'] - $restoredQty) < 0 ? 0 : $subItem['serve_qty'] - $restoredQty
                                ]);

                                StockHistory::create([
                                    'inventory_id' => $inventoryItem->inventory_id,
                                    'inventory_item' => $inventoryItem->item_name,
                                    'old_stock' => $oldStockQty,
                                    'in' => $restoredQty,
                                    'out' => 0,
                                    'current_stock' => $inventoryItem->stock_qty,
                                    'kept_balance' => $inventoryItem->current_kept_amt,
                                ]);
                            }

                            $balanceQty = $item['item_qty'] - $updated_item['remove_qty'];
                            
                            if ((int)$balanceQty > 0) {
                                $cancelledAmount += ($item['amount'] / $item['item_qty']) * $updated_item['remove_qty'];
                                
                                $item->update([
                                    'item_qty' => $balanceQty,
                                    'amount' => ($item['amount'] / $item['item_qty']) * $balanceQty,
                                    'status' => $updated_item['has_products_left'] ? 'Pending Serve' : 'Served'
                                ]);
                            } else {
                                $cancelledAmount += $item['amount'];
                                $item->update(['status' => 'Cancelled']);
                            }
                            $item->save();
                        }
                    }
                }
                $order->refresh();

                $order->update([
                    'amount' => $order->amount - $cancelledAmount,
                    'total_amount' => $order->total_amount - $cancelledAmount,
                ]);

            }
            
            $order->refresh();

            if ($order) {
                $statusArr = [];
                $orderStatus = 'Pending Serve';
                $orderTableStatus = 'Pending Order';
    
                foreach ($orderItems as $key => $item) {
                    array_push($statusArr, $item['status']);
                }
    
                $uniqueStatuses = array_unique($statusArr);
    
                if (in_array("Pending Serve", $uniqueStatuses)) {
                    $orderStatus = 'Pending Serve';
                    $orderTableStatus = 'Order Placed';
                }
    
                if (count($uniqueStatuses) === 1) {
                    if ($uniqueStatuses[0] === 'Served') {
                        $orderStatus = 'Order Served';
                        $orderTableStatus = 'All Order Served';
                    }
                } else if (count($uniqueStatuses) === 2) {
                    if (in_array('Served', $uniqueStatuses) && in_array('Cancelled', $uniqueStatuses)) {
                        $orderStatus = 'Order Served';
                        $orderTableStatus = 'All Order Served';
                    }
                }

                $order->update(['status' => $orderStatus]);
                
                // Update all tables associated with this order
                $order->orderTable->each(function ($tab) use ($orderTableStatus) {
                    $tab->table->update(['status' => $orderTableStatus]);
                    $tab->update(['status' => $orderTableStatus]);
                });
            }
        }

        return redirect()->back();
    }

    /**
     * Add the items to keep.
     */
    public function addItemToKeep(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $request->validate(['customer_id' => 'required|integer'], ['required' => 'This field is required.']);

            $items = $request->items;
            $validatedItems = [];
            $allItemErrors = [];
            
            $rules = [
                'order_item_subitem_id' => 'required|integer',
                'amount' => 'required|decimal:0,2',
                'remark' => 'nullable|string',
                'expired_from' => 'required|date_format:Y-m-d',
                'expired_to' => 'required|date_format:Y-m-d',
            ];
            $requestMessages = [
                'required' => 'This field is required.',
                'integer' => 'This field must be an integer.',
                'decimal' => 'This field must be a decimal number.',
                'string' => 'This field must be an string.',
                'date_format' => 'This field must be in a date format: Y-m-d.',
            ];

            foreach ($items as $index => $item) {
                // Validate items data
                $orderItemsValidator = Validator::make($item, $rules, $requestMessages);
                
                if ($orderItemsValidator->fails()) {
                    // Collect the errors for each item and add to the array with item index
                    foreach ($orderItemsValidator->errors()->messages() as $field => $messages) {
                        $allItemErrors["items.$index.$field"] = $messages;
                    }
                } else {
                    // Collect the validated item and manually add the 'id' field back
                    $validatedItem = $orderItemsValidator->validated();
                    if (isset($item['id'])) {
                        $validatedItem['id'] = $item['id'];
                    }
                    $validatedItems[] = $validatedItem;
                }
            }

            // If there are any item validation errors, return them
            if (!empty($allItemErrors)) {
                // return redirect()->back()->withErrors($allItemErrors);
                return response()->json(['errors' => $allItemErrors], 400);
            }

            if (count($validatedItems) > 0) {
                $order = Order::with(['orderTable.table', 'orderItems.subItems', 'orderItems.product'])->find($request->order_id);
                $currentOrder = Order::with(['orderTable.table', 'orderItems.subItems', 'orderItems.product'])->find($request->current_order_id);

                $orderTableNames = $request->order_id != $request->current_order_id
                        ? implode(", ", $order->orderTable->map(fn ($order_table) => $order_table['table']['table_no'])->toArray())
                        : implode(", ", $currentOrder->orderTable->map(fn ($order_table) => $order_table['table']['table_no'])->toArray());

                // dd($orderTableNames);
                $orderItems = $order->orderItems;
                foreach ($orderItems as $orderItemKey => $orderItem) {
                    $totalItemQty = 0;
                    $totalServedQty = 0;
                    $hasServeQty = false;
                    $keepQty = 0;
                    $keepSubItemQty = 0;

                    foreach ($orderItem->subItems as $subItemKey => $subItem) {
                        foreach ($validatedItems as $key => $item) {
                            foreach ($request->items as $reqItemKey => $reqItem) {
                                if ($item['order_item_subitem_id'] === $reqItem['order_item_subitem_id'] && $subItem['id'] === $item['order_item_subitem_id']) {
                                    if ($item['amount'] > 0) {
                                        // if ($reqItem['keep_id']) {
                                        //     $keepItem = KeepItem::find($reqItem['keep_id']);
                                        //     $keepItem->update([
                                        //         'qty' => $reqItem['type'] === 'qty' ? $keepItem->qty + $item['amount'] : $keepItem->qty,
                                        //         'cm' => $reqItem['type'] === 'cm' ? $keepItem->cm + $item['amount'] : $keepItem->cm,
                                        //         'status' => 'Keep',
                                        //     ]);

                                        //     $keepItem->save();
                                        //     $keepItem->refresh();
                
                                        //     $associatedSubItem = OrderItemSubitem::where('id', $reqItem['order_item_subitem_id'])
                                        //                                             ->with(['productItem:id,inventory_item_id'])
                                        //                                             ->first();
                                        //     $tempInventoryItem = $associatedSubItem->productItem->inventory_item_id;
                                        //     $tempOrderItem = IventoryItem::find($tempInventoryItem);
                                            
                                        //     KeepHistory::create([
                                        //         'keep_item_id' => $reqItem['keep_id'],
                                        //         'order_item_id' => $reqItem['order_item_id'],
                                        //         'qty' => $reqItem['type'] === 'qty' ? round($item['amount'], 2) : 0,
                                        //         'cm' => $reqItem['type'] === 'cm' ? number_format((float) $item['amount'], 2, '.', '') : '0.00',
                                        //         'keep_date' => $keepItem->updated_at,
                                        //         'kept_balance' => $reqItem['type'] === 'qty' ? $tempOrderItem->current_kept_amt + $item['amount'] : $tempOrderItem->current_kept_amt,
                                        //         'kept_from_table' => $orderTableNames,
                                        //         'status' => 'Keep',
                                        //     ]);
                                            
                                        //     if ($reqItem['type'] === 'qty') {
                                        //         $tempOrderItem->increment('total_kept', $item['amount']);
                                        //         $tempOrderItem->increment('current_kept_amt', $item['amount']);
                                        //     }

                                        //     $toBeKept = $reqItem['type'] === 'cm' ? 1 : $item['amount'];
                                        //     $keepQty = $toBeKept;

                                        //     $subItem->update([
                                        //         'serve_qty' => $subItem['serve_qty'] > ($subItem['item_qty'] * $orderItem->item_qty - $toBeKept) ? $subItem['serve_qty'] - $toBeKept : $subItem['serve_qty']
                                        //         // 'serve_qty' => $reqItem['type'] === 'cm' ? 1 : $toBeServed
                                        //     ]);

                                        // } else {
                                        
                                        $newKeep = KeepItem::create([
                                            'customer_id' => $request->customer_id,
                                            'order_item_subitem_id' => $item['order_item_subitem_id'],
                                            'qty' => $reqItem['type'] === 'qty' ? $item['amount'] : 0,
                                            'cm' => $reqItem['type'] === 'cm' ? $item['amount'] : 0,
                                            'remark' => $item['remark'] ?: null,
                                            'user_id' => $request->user_id,
                                            'kept_from_table' => $orderTableNames,
                                            'status' => 'Keep',
                                            'expired_from' => $item['expired_from'],
                                            'expired_to' => $item['expired_to'],
                                        ]);

                                        $associatedSubItem = OrderItemSubitem::where('id', $item['order_item_subitem_id'])
                                                                ->with(['productItem:id,inventory_item_id', 'productItem.inventoryItem:id,item_name'])
                                                                ->first();

                                        $inventoryItemName = $associatedSubItem->productItem->inventoryItem->item_name;

                                        $name = Customer::where('id', $request->customer_id)->first('full_name')->full_name;

                                        // dd($name);
                                        activity()->useLog('keep-item-from-customer')
                                                    ->performedOn($newKeep)
                                                    ->event('kept')
                                                    ->withProperties([
                                                        'edited_by' => auth()->user()->full_name,
                                                        'image' => auth()->user()->getFirstMediaUrl('user'),
                                                        'item_name' => $inventoryItemName,
                                                        'customer_name' => $name,
                                                    ])
                                                    ->log("'$inventoryItemName' is kept in $name's account.");
                            
                                                    
                                        $tempInventoryItem = $associatedSubItem->productItem->inventory_item_id;
                                        $tempOrderItem = IventoryItem::find($tempInventoryItem);

                                        KeepHistory::create([
                                            'keep_item_id' => $newKeep->id,
                                            'qty' => $reqItem['type'] === 'qty' ? round($item['amount'], 2) : 0.00,
                                            'cm' => $reqItem['type'] === 'cm' ? number_format((float) $item['amount'], 2, '.', '') : '0.00',
                                            'keep_date' => $newKeep->created_at,
                                            'kept_balance' => $tempOrderItem->current_kept_amt + $item['amount'],
                                            'user_id' => auth()->user()->id,
                                            'kept_from_table' => $orderTableNames,
                                            'status' => 'Keep',
                                        ]);
                                        
                                        if ($reqItem['type'] === 'qty') {
                                            $tempOrderItem->increment('total_kept', $item['amount']);
                                            $tempOrderItem->increment('current_kept_amt', $item['amount']);
                                        }

                                        if ($orderItem->status === 'Pending Serve') {
                                            $toBeServed = ($reqItem['totalKept'] + $item['amount']) - $subItem['serve_qty'];
                                            
                                            $subItem->increment('serve_qty', $reqItem['type'] === 'cm' ? 1 : $toBeServed);
                                        }
                                        // }

                                        $subItem->save();
                                        $subItem->refresh();

                                        if ($reqItem['keep_id']) ($keepSubItemQty = $subItem['item_qty']);
                                    }
            
                                    $totalItemQty += $subItem['item_qty'] * $orderItem->item_qty;
                                    $totalServedQty += $subItem['serve_qty'];  
                                    $hasServeQty = $item['amount'] > 0 || $hasServeQty ? true : false;
                                }
                            }
                        }
                    }

                    if ($hasServeQty) {
                        if ($keepQty > 0) {
                            $newOrderItemQty = $orderItem->item_qty - $keepQty;

                            $orderItem->update([
                                'item_qty' => $keepQty > 0 ? $newOrderItemQty : $orderItem->item_qty,
                                'status' => $totalServedQty === $keepSubItemQty * $newOrderItemQty ? 'Served' : 'Pending Serve'
                            ]);

                        } else {
                            $orderItem->update([
                                'status' => $totalServedQty === $totalItemQty ? 'Served' : 'Pending Serve'
                            ]);
                        }
                    }
                }

                $currentOrderLatestTable = $currentOrder->orderTable->sortByDesc('id')->first();

                if ($currentOrder && !in_array($currentOrderLatestTable->status, ['Pending Clearance', 'Order Cancelled', 'Order Voided'])) {
                    $statusArr = collect($currentOrder->orderItems->pluck('status')->unique());
                
                    $orderStatus = 'Pending Serve';
                    $orderTableStatus = 'Pending Order';
                
                    if ($statusArr->contains('Pending Serve')) {
                        $orderStatus = 'Pending Serve';
                        $orderTableStatus = 'Order Placed';
                    } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
                        $orderStatus = 'Order Served';
                        $orderTableStatus = 'All Order Served';
                    } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
                        $orderStatus = 'Order Served';
                        $orderTableStatus = 'All Order Served';
                    }
                    
                    $currentOrder->update(['status' => $orderStatus]);
                    
                    // Update all tables associated with this order
                    $currentOrder->orderTable->each(function ($tab) use ($orderTableStatus) {
                        $tab->table->update(['status' => $orderTableStatus]);
                        $tab->update(['status' => $orderTableStatus]);
                    });
                }
            }
            
            $customer = Customer::with([
                                    'keepItems' => function ($query) {
                                        $query->where('status', 'Keep')
                                                ->with([
                                                    'orderItemSubitem.productItem:id,inventory_item_id',
                                                    'orderItemSubitem.productItem.inventoryItem:id,item_name',
                                                    'waiter:id,full_name'
                                                ]);
                                    }
                                ])
                                ->find($request->current_customer_id);
            
            if ($customer) {
                foreach ($customer->keepItems as $key => $keepItem) {
                    $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
                    unset($keepItem->orderItemSubitem);
                    
                    $keepItem->image = $keepItem->orderItemSubitem->productItem 
                            ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                            : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');
                    
                    $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
                }
            }

            return response()->json($customer?->keepItems);
        });
        
        // $validated = $request->validate([
        //     'customer_id' => 'required|integer',
        //     'user_id' => 'required|integer',
        //     'order_id' => 'required|integer',
        //     'remark' => 'nullable|string',
        //     'expired_from' => 'nullable|date_format:Y-m-d H:i:s',
        //     'expired_to' => 'nullable|date_format:Y-m-d H:i:s',
        // ], [
        //     'required' => 'This field is required.'
        // ]);

        // $order_item = $request->input('item');
        // $sub_items = collect($order_item['sub_items'])->map(function ($query) use ($validated) {
        //     return [
        //         'customer_id' => $validated['customer_id'],
        //         'id' => $query['id'], 
        //         'qty' => $query['type'] === 'qty' ? (int)$query['amount'] : 0,
        //         'cm' => $query['type'] === 'cm' ? round($query['amount'], 2) : 0.00,
        //         'type' => $query['type'], 
        //         'remark' => $validated['remark'],
        //         'user_id' => $validated['user_id'],
        //         'status' => 'Keep',
        //         'expired_from' => $validated['expired_from'],
        //         'expired_to' => $validated['expired_to'],
        //         'order_id' => $validated['order_id']
        //     ];
        // });
        // dd($sub_items);
        
        // if (count($sub_items) > 0) {
        //     $order = Order::with(['orderTable.table', 'orderItems.subItems', 'orderItems.product'])->find($request->order_id);
        //     $orderItems = $order->orderItems;
                        
        //     foreach ($orderItems as $orderItemKey => $orderItem) {
        //         $totalItemQty = 0;
        //         $totalServedQty = 0;
        //         $hasServeQty = false;

        //         foreach ($orderItem->subItems as $subItemKey => $subItem) {
        //             foreach ($sub_items as $key => $item) {
        //                 foreach ($request->items as $reqItemKey => $reqItem) {
        //                     if ($item['id'] === $reqItem['order_item_subitem_id'] && $subItem['id'] === $item['order_item_subitem_id']) {
        //                         if ($item['amount'] > 0) {
        //                             if ($reqItem['keep_id']) {
        //                                 $keepItem = KeepItem::find($reqItem['keep_id']);
        //                                 $keepItem->update([
        //                                     'qty' => $reqItem['type'] === 'qty' ? $keepItem->qty + $item['amount'] : $keepItem->qty,
        //                                     'cm' => $reqItem['type'] === 'cm' ? $keepItem->cm + $item['amount'] : $keepItem->cm,
        //                                     'status' => 'Keep',
        //                                 ]);

        //                                 $keepItem->save();
        //                                 $keepItem->refresh();
            
        //                                 KeepHistory::create([
        //                                     'keep_item_id' => $reqItem['keep_id'],
        //                                     'order_item_id' => $reqItem['order_item_id'],
        //                                     'qty' => $reqItem['type'] === 'qty' ? round($item['amount'], 2) : 0.00,
        //                                     'cm' => $reqItem['type'] === 'cm' ? round($item['amount'], 2) : 0.00,
        //                                     'keep_date' => $keepItem->updated_at,
        //                                     'status' => 'Keep',
        //                                 ]);
        //                             } else {
        //                                 $newKeep = KeepItem::create([
        //                                     'customer_id' => $request->customer_id,
        //                                     'order_item_subitem_id' => $item['order_item_subitem_id'],
        //                                     'qty' => $reqItem['type'] === 'qty' ? $item['amount'] : 0,
        //                                     'cm' => $reqItem['type'] === 'cm' ? $item['amount'] : 0,
        //                                     'remark' => $item['remark'] ?: '',
        //                                     'user_id' => $request->user_id,
        //                                     'status' => 'Keep',
        //                                     'expired_from' => $item['expired_from'],
        //                                     'expired_to' => $item['expired_to'],
        //                                 ]);
            
        //                                 KeepHistory::create([
        //                                     'keep_item_id' => $newKeep->id,
        //                                     'qty' => $reqItem['type'] === 'qty' ? round($item['amount'], 2) : 0.00,
        //                                     'cm' => $reqItem['type'] === 'cm' ? round($item['amount'], 2) : 0.00,
        //                                     'keep_date' => $newKeep->created_at,
        //                                     'status' => 'Keep',
        //                                 ]);
        //                             }

        //                             if ($orderItem->status === 'Pending Serve') {
        //                                 $subItem->increment('serve_qty', $reqItem['type'] === 'cm' ? 1 : $item['amount']);
        //                             }
        //                             $subItem->save();
        //                             $subItem->refresh();
        //                         }
        
        //                         $totalItemQty += $subItem['item_qty'] * $orderItem->item_qty;
        //                         $totalServedQty += $subItem['serve_qty'];
        //                         $hasServeQty = $item['amount'] > 0 || $hasServeQty ? true : false;
        //                     }
        //                 }
        //             }
        //         }

        //         if ($hasServeQty) {
        //             $orderItem->update(['status' => $totalServedQty === $totalItemQty ? 'Served' : 'Pending Serve']);
        //         }
        //     }
        //     $order->refresh();

        //     if ($order) {
        //         $statusArr = collect($order->orderItems->pluck('status')->unique());
            
        //         $orderStatus = 'Pending Serve';
        //         $orderTableStatus = 'Pending Order';
            
        //         if ($statusArr->contains('Pending Serve')) {
        //             $orderStatus = 'Pending Serve';
        //             $orderTableStatus = 'Order Placed';
        //         } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
        //             $orderStatus = 'Order Served';
        //             $orderTableStatus = 'All Order Served';
        //         } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
        //             $orderStatus = 'Order Served';
        //             $orderTableStatus = 'All Order Served';
        //         }
                
        //         $order->update(['status' => $orderStatus]);
                
        //         // Update all tables associated with this order
        //         $order->orderTable->each(function ($tab) use ($orderTableStatus) {
        //             $tab->table->update(['status' => $orderTableStatus]);
        //             $tab->update(['status' => $orderTableStatus]);
        //         });
        //     }
        // }
        
        // $customer = Customer::with([
        //                         'keepItems' => function ($query) {
        //                             $query->select('id', 'customer_id', 'order_item_subitem_id', 'user_id', 'qty', 'cm', 'remark', 'status', 'expired_to', 'created_at')
        //                                     ->where('status', 'Keep')
        //                                     ->with([
        //                                         'orderItemSubitem.productItem:id,inventory_item_id',
        //                                         'orderItemSubitem.productItem.inventoryItem:id,item_name',
        //                                         'waiter:id,full_name'
        //                                     ]);
        //                         }
        //                     ])
        //                     ->find($request->customer_id);
        
        // foreach ($customer->keepItems as $key => $keepItem) {
        //     $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
        //     unset($keepItem->orderItemSubitem);
            
        //     $keepItem->image = $keepItem->orderItemSubitem->productItem 
        //             ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
        //             : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');
            
        //     $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
        // }

        // return response()->json($customer->keepItems);


    }

    /**
     * Get the order's customer.
     */
    public function getCustomerDetails(string $id) 
    {
        $customer = Customer::with([
                                'rank:id,name',
                                'keepItems' => function ($query) {
                                    $query->where('status', 'Keep')
                                            ->with([
                                                'orderItemSubitem.productItem:id,inventory_item_id',
                                                'orderItemSubitem.productItem.inventoryItem:id,item_name',
                                                'waiter:id,full_name'
                                            ]);
                                }
                            ])
                            ->find($id);

        $customer->image = $customer->getFirstMediaUrl('customer');
        if ($customer->rank) {
            $customer->rank->image = $customer->rank->getFirstMediaUrl('ranking');
        }
        
        foreach ($customer->keepItems as $key => $keepItem) {
            $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
            $keepItem->order_no = $keepItem->orderItemSubitem->orderItem->order['order_no'];
            unset($keepItem->orderItemSubitem);

            $keepItem->image = $keepItem->orderItemSubitem->productItem 
                                ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                                : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

            $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');

        }

        return response()->json($customer);
    }

    /**
     * Get the customer's keep histories.
     */
    public function getCustomerKeepHistories(string $id) 
    {
        $keepHistories = KeepHistory::with([
                                        'keepItem.orderItemSubitem.productItem:id,inventory_item_id', 
                                        'keepItem.orderItemSubitem.productItem.inventoryItem:id,item_name', 
                                        'keepItem.waiter:id,full_name',
                                        'waiter:id,full_name'
                                    ])
                                    ->whereHas('keepItem', function ($query) use ($id) {
                                        $query->where('customer_id', $id);
                                    })
                                    ->orderByDesc('id')
                                    ->get()
                                    ->map(function ($history) {
                                        // Assign item_name and unset unnecessary relationship data
                                        if ($history->keepItem && $history->keepItem->orderItemSubitem) {
                                            $history->keepItem->item_name = $history->keepItem->orderItemSubitem->productItem->inventoryItem->item_name;
                                            unset($history->keepItem->orderItemSubitem); // Clean up the response

                                            $history->keepItem->image = $history->keepItem->orderItemSubitem->productItem 
                                                                ? $history->keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                                                                : $history->keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');
                                
                                                $history->keepItem->waiter->image = $history->keepItem->waiter->getFirstMediaUrl('user');
                                            }
                                            if ($history->waiter) {
                                                $history->waiter->image = $history->waiter?->getFirstMediaUrl('user');
                                            }
                                        return $history;
                                    });

        return response()->json($keepHistories);
    }

    /**
     * Add the customer's kept item(s) to current order.
     */
    public function addKeptItemToOrder(Request $request, string $id) 
    {
        return DB::transaction(function () use ($request, $id) {
            // Validate the form data
            $validator = Validator::make(
                $request->all(), 
                ['return_qty' => 'required|integer'], 
                [
                    'return_qty.required' => 'This field is required.',
                    'return_qty.integer' => 'This field must be an integer.',
                ]
            );

            if ($validator->fails()) {
                // Collect the errors for the entire form and return them
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // If validation passes, you can proceed with processing the validated form data
            $validatedData = $validator->validated();

            if ($validatedData) {
                $keepItem = KeepItem::with(['orderItemSubitem:id,order_item_id,product_item_id', 
                                                        'orderItemSubitem.productItem:id,product_id,inventory_item_id',
                                                        'orderItemSubitem.productItem.inventoryItem', 
                                                        'orderItemSubitem.orderItem:id'])
                                    ->find($id);

                $productItem = $keepItem->orderItemSubitem->productItem;
                $inventoryItem = $productItem->inventoryItem;

                $currentOrder = Order::with(['orderTable.table'])->find($request->order_id);
                $orderTableNames = implode(", ", $currentOrder->orderTable->map(fn ($order_table) => $order_table['table']['table_no'])->toArray());
                // dd($request->all());

                $newOrderItem = OrderItem::create([
                    'order_id' => $request->order_id,
                    'user_id' => $request->user_id,
                    'type' => 'Keep',
                    'product_id' => $productItem->product_id,
                    'keep_item_id' => $id,
                    'item_qty' => $request->return_qty,
                    'amount_before_discount' => 0.00,
                    'discount_id' => null,
                    'discount_amount' => 0.00,
                    'amount' => 0.00,
                    'status' => 'Served',
                ]);
                
                OrderItemSubitem::create([
                    'order_item_id' => $newOrderItem->id,
                    'product_item_id' => $productItem->id,
                    'item_qty' => 1,
                    'serve_qty' => $request->return_qty,
                ]);

                KeepHistory::create([
                    'keep_item_id' => $id,
                    'order_item_id' => $newOrderItem->id,
                    'qty' => $request->type === 'qty' ? round($request->return_qty, 2) : 0.00,
                    'cm' => $request->type === 'cm' ? number_format((float) $keepItem->cm, 2, '.', '') : '0.00',
                    'keep_date' => $keepItem->created_at,
                    'kept_balance' => $request->type === 'qty' ? $inventoryItem->current_kept_amt - $request->return_qty : $inventoryItem->current_kept_amt,
                    'user_id' => auth()->user()->id,
                    'kept_from_table' => $keepItem->kept_from_table,
                    'redeemed_to_table' => $orderTableNames,
                    'status' => 'Served',
                ]);

                $order = Order::with(['orderTable.table', 'customer:id,full_name'])->find($request->order_id);

                activity()->useLog('return-kept-item')
                            ->performedOn($newOrderItem)
                            ->event('updated')
                            ->withProperties([
                                'edited_by' => auth()->user()->full_name,
                                'image' => auth()->user()->getFirstMediaUrl('user'),
                                'item_name' => $keepItem->item_name,
                                'customer_name' => $order->customer->full_name
                            ])
                            ->log(":properties.item_name is returned to :properties.customer_name.");

                if ($request->type === 'qty') {
                    $inventoryItem->decrement('total_kept', $request->return_qty);
                    $inventoryItem->decrement('current_kept_amt', $request->return_qty);

                    $keepItem->update([
                        'qty' => ($keepItem->qty - $request->return_qty) > 0 ? $keepItem->qty - $request->return_qty : 0.00,
                        'status' => ($keepItem->qty - $request->return_qty) > 0 ? 'Keep' : 'Returned'
                    ]);
                } else {
                    $keepItem->update([
                        'cm' => 0.00,
                        'status' => 'Returned'
                    ]);
                }
                $order->update(['status' => 'Pending Serve']);
                    
                // Update all tables associated with this order
                $order->orderTable->each(function ($tab) {
                    $tab->table->update(['status' => 'Order Placed']);
                    $tab->update(['status' => 'Order Placed']);
                });
            }

            $customer = Customer::with([
                                    'keepItems' => function ($query) {
                                        $query->where('status', 'Keep')
                                                ->with([
                                                    'orderItemSubitem.productItem:id,inventory_item_id',
                                                    'orderItemSubitem.productItem.inventoryItem:id,item_name',
                                                    'waiter:id,full_name'
                                                ]);
                                    }
                                ])
                                ->find($request->customer_id);

            foreach ($customer->keepItems as $key => $keptItem) {
                $keptItem->item_name = $keptItem->orderItemSubitem->productItem->inventoryItem['item_name'];
                unset($keptItem->orderItemSubitem);

                $keptItem->image = $keptItem->orderItemSubitem->productItem 
                        ? $keptItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                        : $keptItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

                $keptItem->waiter->image = $keptItem->waiter->getFirstMediaUrl('user');
            }

            return response()->json($customer->keepItems);
        });
    }

    /**
     * Update the current order's selected customer.
     */
    public function updateOrderCustomer(Request $request, string $id) 
    {
        $validatedData = $request->validate(
            ['customer_id' => 'required|integer'], 
            ['required' => 'This field is required.']
        );

        $order = Order::with([
                            'customer:id',
                            'customer.rewards:id,customer_id,ranking_reward_id,status',
                        ])
                        ->find($id);
        
        if ($order->customer && $order->voucher) {
            $this->removeOrderVoucher($order);
        }

        $order->update(['customer_id' => $validatedData['customer_id']]);

        return redirect()->back();
    }

    // /**
    //  * Get the payment details of the order.
    //  */
    // public function getOrderPaymentDetails(string $id) 
    // {
    //     $order = Order::with('payment')->find($id);
    //     $taxes = Setting::whereIn('name', ['SST', 'Service Tax'])->pluck('value', 'name');

    //     $data = [
    //         'payment_details' => $order->payment,
    //         'taxes' => $taxes
    //     ];

    //     return response()->json($data);
    // }

    /**
     * Get all taxes.
     */
    public function getAllTaxes() 
    {
        $taxes = Setting::where('type', 'tax')->pluck('value', 'name');

        return response()->json($taxes);
    }

    /**
     * Update order's payment status.
     */
    public function updateOrderPayment(Request $request) 
    {
        return $request->split_bill_id && $request->split_bill_id !== 'current'
            ? $this->processSplitBillPayment($request)
            : $this->processNormalPayment($request);
    }

    private function processNormalPayment(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $order = Order::with([
                                'orderTable' => function ($query) {
                                    $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided']);
                                },
                                'orderTable.table', 
                                'customer:id,point,total_spending,ranking', 
                                'customer.rewards:id,customer_id,ranking_reward_id,status',
                                'orderItems' => fn($query) => 
                                    // $query->where('product.commItem.configComms')->where('status', 'Served')
                                    $query->whereHas('product', fn ($subQuery) =>
                                        $subQuery->whereHas('commItem', fn ($innerQuery) =>
                                            $innerQuery->whereHas('configComms')
                                                    ->where('status', 'Active')
                                        )
                                    )->with([
                                        'product' => fn ($orderItemQuery) =>
                                            $orderItemQuery->whereHas('commItem', fn ($orderQuery) =>
                                                $orderQuery->where('status', 'Active')
                                                        ->whereHas('configComms')
                                            )->with([
                                                'commItem.configComms',
                                            ])
                                    ])->where('status', 'Served'),
                            ])
                            ->where('id', $request->order_id)
                            ->first();

            // Process payment
            $paymentResponse = $this->processPayment($request, $order);
            
            return response()->json($paymentResponse);
        
        });
    }

    private function processSplitBillPayment(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $originalOrder = Order::with([
                                        'orderTable' => function ($query) {
                                            $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided']);
                                        },
                                        'orderTable.table',
                                        'customer:id,point,total_spending,ranking', 
                                        'customer.rewards:id,customer_id,ranking_reward_id,status',
                                        'orderItems' => fn($query) => $query->where('status', 'Served')
                                    ])
                                    ->find($request->order_id);

            $splitBill = $request->split_bill;
            $totalSplitOrderAmount = 0.00;
            $totalSplitOrderDiscountedAmount = 0.00;

            // Create new order for the split bill
            $newOrder = Order::create([
                'order_no' => RunningNumberService::getID('order'),
                'pax' => $splitBill['pax'] ?? $originalOrder->pax,
                'user_id' => $request->user_id,
                'customer_id' => $splitBill['customer_id'] ?? $originalOrder->customer_id,
                'amount' => 0.00,
                'total_amount' => 0.00,
                'status' => 'Order Completed',
                'voucher_id' => $originalOrder->voucher_id,
            ]);

            foreach ($request->tables as $orderTable) {
                OrderTable::create([
                    'table_id' => $orderTable['table_id'],
                    'pax' => $splitBill['pax'] ?? $originalOrder->pax,
                    'user_id' => $request->user_id,
                    'status' => $orderTable['status'],
                    'order_id' => $newOrder->id,                
                ]);
            }

            // Process each item in the split bill
            foreach ($splitBill['order_items'] as $item) {
                $balanceQty = $item['balance_qty'];
                $originalQty = $item['original_qty'];
                $originalItem = OrderItem::with('product')->find($item['id']);

                // $balancePercentage = round($balanceQty / $originalQty * 100, 2);// Calculate per-unit amounts
                $amountBeforeDiscountPerUnit = $originalItem->product->price;
                $currentProductDiscount = $originalItem->product->discountSummary($originalItem->product->discount_id)?->first();
                $amountPerUnit = round($currentProductDiscount ? $currentProductDiscount['price_after'] : $amountBeforeDiscountPerUnit, 2);
                $discountPerUnit = $amountBeforeDiscountPerUnit - $amountPerUnit;

                if ($balanceQty === $originalQty) {
                    $originalItem->update(['order_id' => $newOrder->id]);
                    
                    $totalSplitOrderAmount += $originalItem->amount;
                    $totalSplitOrderDiscountedAmount += $originalItem->discount_amount;

                } else {
                    // Create new order item for the split order
                    $newOrderItem = OrderItem::create([
                        'order_id' => $newOrder->id,
                        'user_id' => $originalItem->user_id,
                        'type' => $originalItem->type,
                        'product_id' => $originalItem->product_id,
                        'product_name' => $originalItem->product_name,
                        'item_qty' => $balanceQty,
                        'price' => $originalItem->price,
                        'amount_before_discount' => round($amountBeforeDiscountPerUnit * $balanceQty, 2),
                        'discount_id' => $originalItem->discount_id,
                        'discount_amount' => round($discountPerUnit * $balanceQty, 2),
                        'amount' => round($amountPerUnit * $balanceQty, 2),
                        'status' => $originalItem->status,
                        'bucket' => $originalItem->bucket,
                    ]);

                    $totalSplitOrderAmount += round($amountPerUnit * $balanceQty, 2);
                    $totalSplitOrderDiscountedAmount += round($discountPerUnit * $balanceQty, 2);

                    $newStatusArr = collect();
                    
                    $originalItem->subItems->each(function ($subItem) use ($balanceQty, $newOrderItem, &$newStatusArr) {
                        $newSubItemServeQty = $balanceQty * $subItem->item_qty;
                        $newQtyServed = min($newSubItemServeQty, $subItem->serve_qty);
                        
                        OrderItemSubitem::create([
                            'order_item_id' => $newOrderItem->id,
                            'product_item_id' => $subItem->product_item_id,
                            'item_qty' => $subItem->item_qty,
                            'serve_qty' => $newQtyServed,
                        ]);

                        // Update original subitem
                        $subItem->decrement('serve_qty', $newQtyServed);
                        
                        if ($newQtyServed > 0) {
                            $newStatusArr->push($newQtyServed < ($balanceQty * $subItem->item_qty) ? 'Pending Serve' : 'Served');
                        }
                    });

                    // Update original item status based on subitems
                    $remainingQty = $originalItem->item_qty - $balanceQty;
                    $originalItemStatus = $this->determineItemStatus($newStatusArr);

                    $originalItem->update([
                        'item_qty' => $remainingQty,
                        'amount_before_discount' => round($amountBeforeDiscountPerUnit * $remainingQty, 2),
                        'discount_amount' => round($discountPerUnit * $remainingQty, 2),
                        'amount' => round($amountPerUnit * $remainingQty, 2),
                        'status' => $originalItemStatus,
                    ]);
                }
            }

            // Update the new order totals
            $newOrder->update([
                'amount' => $totalSplitOrderAmount,
                'total_amount' => $totalSplitOrderAmount,
                'discount_amount' => $totalSplitOrderDiscountedAmount,
            ]);

            // Update the original order
            $originalOrder->refresh();
            $originalOrder->update([
                'amount' => $originalOrder->orderItems->where('status', 'Served')->sum('amount'),
                'total_amount' => $originalOrder->orderItems->where('status', 'Served')->sum('amount'),
                'discount_amount' => $originalOrder->orderItems->where('status', 'Served')->sum('discount_amount'),
            ]);

            // Process payment for the split order
            $paymentResponse = $this->processPayment($request, $newOrder);

            return response()->json(array_merge($paymentResponse, [
                'updatedCurrentBill' => $this->getUpdatedCurrentBill($originalOrder)
            ]));
        });
    }

    private function processPayment(Request $request, Order $order)
    {
        $order->update(['status' => 'Order Completed']);

        $statusArr = collect($order->orderTable->pluck('status')->unique());
        
        if ($order->status === 'Order Completed' && ($statusArr->count() === 1 && ($statusArr->first() === 'All Order Served' || $statusArr->first() === 'Order Placed' || $statusArr->first() === 'Pending Order'))) {
            $currentShiftTransaction = ShiftTransaction::where('status', 'opened')->first();
            $paymentMethodSales = [];
            
            $settings = Setting::select(['name', 'type', 'value', 'point'])->whereIn('type', ['tax', 'point'])->get();
            $taxes = $settings->filter(fn($setting) => $setting['type'] === 'tax')->pluck('value', 'name');
            $pointConversion = $settings->filter(fn($setting) => $setting['type'] === 'point')->first();
        
            $subTotal = $order->amount;
            $sstAmount = round($subTotal * ($taxes['SST'] / 100), 2);
            $serviceTaxAmount = round($subTotal * ($taxes['Service Tax'] / 100), 2);
        
            // Calculate voucher discount amount
            // if ($order->voucher) {
            //     $voucherDiscountedAmount = $order->voucher->reward_type === 'Discount (Percentage)' 
            //             ? $subTotal * ($order->voucher->discount / 100)
            //             : $order->voucher->discount;
            // } else {
            //     $voucherDiscountedAmount = 0.00;
            // }

            $orderVoucher = $order->voucher;
            $appliedDiscounts = $request->collect('discounts');
            $appliedBillDiscounts = [];

            if ($appliedDiscounts->count() > 0) {
                if ($appliedDiscounts->filter(fn ($discount) => $discount['type'] === 'voucher')->count() === 1) {
                    $voucherDiscount = $appliedDiscounts->filter(fn ($discount) => $discount['type'] === 'voucher')[0];
    
                    $this->updateOrderVoucher($order, $voucherDiscount);

                    $voucherDiscountedAmount = $voucherDiscount['reward_type'] === 'Discount (Percentage)' 
                            ? $subTotal * ($voucherDiscount['discount'] / 100)
                            : $voucherDiscount['discount'];
    
                } else {
                    $this->updateOrderVoucher($order, null);
                    $voucherDiscountedAmount = 0.00;
                }
                
                // Calculate bill discount(s) amount
                if ($appliedDiscounts->filter(fn ($discount) => $discount['type'] === 'bill' || $discount['type'] === 'manual')->count() > 0) {
                    $billDiscounts = $appliedDiscounts->filter(fn ($discount) => $discount['type'] === 'bill' || $discount['type'] === 'manual');
                    $billDiscountedAmount = 0.00;

                    foreach ($billDiscounts as $key => $d) {
                        if ($d['type'] === 'bill') {
                            $discount = BillDiscount::find($d['id']);
                            $billDiscountedAmount += $discount->discount_type === 'amount'
                                    ? $discount->discount_rate
                                    : $subTotal * ($discount->discount_rate / 100);
                            
                            array_push($appliedBillDiscounts, $discount->id);
    
                            // if (!$order->customer) continue;
    
                            $hasCustomerLimit = $discount->customer_usage > 0;
                            $hasTotalLimit = $discount->total_usage > 0;
                            
                            // Skip if no limits to enforce
                            if (!$hasCustomerLimit && !$hasTotalLimit) continue;
                            
                            $existingCustomerRecord = $order->customer
                                    ? BillDiscountUsage::where([
                                                            ['bill_discount_id', $discount->id],
                                                            ['customer_id', $order->customer_id]
                                                        ])
                                                        ->first()
                                    : null;
                                                                        
                            // Check limits
                            $canApply = true;
                            $shouldDecrement = false;
            
                            if (
                                $hasCustomerLimit && 
                                $existingCustomerRecord && 
                                $existingCustomerRecord->customer_usage >= $discount->customer_usage
                            ) {
                                $canApply = false;
                            }
                            
                            if ($hasTotalLimit) {
                                if ($discount->remaining_usage <= 0) {
                                    $canApply = false;
                                } else {
                                    $shouldDecrement = true;
                                }
                            }
                            
                            if (!$canApply) continue;
                                                                        
                            if ($existingCustomerRecord) {
                                $existingCustomerRecord->increment('customer_usage');
                                
                            } else {
                                if ($order->customer) {
                                    BillDiscountUsage::create([
                                        'bill_discount_id' => $discount->id,
                                        'customer_id' => $order->customer_id,
                                        'customer_usage' => 1,
                                        'total_usage' => 0,
                                    ]);
                                }
                            }
            
                            if ($shouldDecrement) {
                                $discount->decrement('remaining_usage');
                            }
                        } else {
                            $discountRate = $d['rate'];
                            $billDiscountedAmount += $subTotal * ($discountRate / 100);
                            $manualDiscount = [
                                'discount_type' => 'manual',
                                'discount_rate' => $discountRate,
                            ];
                            
                            array_push($appliedBillDiscounts, $manualDiscount);
                        }
                    }

                } else {
                    $billDiscountedAmount = 0.00;
                }

            } else {
                if ($orderVoucher) {
                    $this->updateOrderVoucher($order, null);
                }
                $voucherDiscountedAmount = 0.00;
                $billDiscountedAmount = 0.00;
            }
            
            $calculatedSum = $subTotal + $sstAmount + $serviceTaxAmount - $voucherDiscountedAmount - $billDiscountedAmount;
            $grandTotal = $this->priceRounding($calculatedSum >= 0.00 ? $calculatedSum : 0.00);

            $roundingDiff = $grandTotal - $calculatedSum;
            $totalPoints = round(($grandTotal / $pointConversion['value']) * $pointConversion['point'], 2);
            $amountPaid = collect($request->payment_methods)->sum('amount');

            $payment = Payment::create([
                'transaction_id' => $currentShiftTransaction->id,
                'order_id' => $order->id,
                'table_id' => $order->orderTable->pluck('table.id'),
                'receipt_no' => RunningNumberService::getID('payment'),
                'receipt_start_date' => $order->created_at,
                'receipt_end_date' => now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                'total_amount' => $subTotal,
                'rounding' => $roundingDiff,
                'sst_amount' => $sstAmount,
                'service_tax_amount' => $serviceTaxAmount,
                'discount_id' => $order->voucher_id,
                'discount_amount' => $voucherDiscountedAmount,
                'bill_discounts' => count($appliedBillDiscounts) > 0 ? $appliedBillDiscounts : null, // array of bill discount selected
                'bill_discount_total' => $billDiscountedAmount, // total amount of bill discount
                'grand_total' => $grandTotal,
                'amount_paid' => $amountPaid,
                'change' => $request->change,
                'point_earned' => $totalPoints,
                'pax' => $order->pax,
                'status' => 'Successful',
                'customer_id' => $order->customer_id,
                'handled_by' => $request->user_id,
            ]);
        
            foreach ($request->payment_methods as $payMethod) {
                PaymentDetail::create([
                    'payment_id' => $payment->id,
                    'payment_method' => $payMethod['method'],
                    'amount' => $payMethod['amount'],
                ]);

                $paymentMethodSales[$payMethod['method']] = $payMethod['amount'];
            }
        
            // Handle sale history and commissions for "Normal" order items
            $order->orderItems->where('type', 'Normal')->each(function ($item) {
                $configCommItem = $item->product->commItem;
        
                if ($configCommItem) {
                    $commissionType = $configCommItem->configComms->comm_type;
                    $commissionRate = $configCommItem->configComms->rate;
                    $commissionAmount = $commissionType === 'Fixed amount per sold product' 
                            ? $commissionRate * $item->item_qty
                            : $item->product->price * $item->item_qty * ($commissionRate / 100);
        
                    EmployeeCommission::create([
                        'user_id' => $item->user_id,
                        'type' => $commissionType,
                        'rate' => $commissionRate,
                        'order_item_id' => $item->id,
                        'comm_item_id' => $configCommItem->id,
                        'amount' => $commissionAmount,
                    ]);
                }
        
                SaleHistory::create([
                    'order_id' => $item->order_id,
                    'product_id' => $item->product_id,
                    'total_price' => $item->amount,
                    'qty' => (int) $item->item_qty,
                ]);
            });

            // Log::debug('order item', ['order item' => $order->orderItems]);
        
            // Handle customer points if applicable
            $customer = $order->customer;
            $response = [];
            
            // Add the accumulated points earned from the order to the customer
            if ($customer) {
                $oldPointBalance = $customer->point;
                $newPointBalance = $oldPointBalance + $totalPoints;
                $oldTotalSpending = $customer->total_spending;
                $newTotalSpending = $oldTotalSpending + $grandTotal;
                $oldRanking = $customer->ranking;
        
                // Dispatch jobs for additional rewards
                // Bus::chain([
                //     new UpdateTier($customer->id, $newPointBalance, $newTotalSpending),
                //     new GiveEntryReward($oldRanking, $customer->id, $oldTotalSpending),
                //     new GiveBonusPoint($payment->id, $totalPoints, $oldPointBalance, $customer->id, $request->user_id)
                // ])->dispatch();

                $customer->update([
                    'point' => $newPointBalance,
                    'total_spending' => $newTotalSpending,
                ]);
                
                $pointExpirationDays = Setting::where([
                                                    ['name', 'Point Expiration'],
                                                    ['type', 'expiration']
                                                ])
                                                ->first(['id', 'value']);

                $pointExpiredDate = now()->addDays((int)$pointExpirationDays->value);
                
                $afterReimbursePoint = $oldPointBalance < 0 
                        ? $oldPointBalance + $totalPoints 
                        : $totalPoints;

                PointHistory::create([
                    'product_id' => null,
                    'payment_id' => $payment->id,
                    'type' => 'Earned',
                    'point_type' => 'Order',
                    'qty' => 0,
                    'amount' => $afterReimbursePoint <= 0 ? 0 : $afterReimbursePoint,
                    'old_balance' => $oldPointBalance,
                    'new_balance' => $customer->point,
                    'expire_balance' => $afterReimbursePoint <= 0 ? 0 : $afterReimbursePoint,
                    'expired_at' => $pointExpiredDate,
                    'customer_id' => $customer->id,
                    'handled_by' => $request->user_id,
                    'redemption_date' => now()
                ]);

                // Determine the new rank based on the new total spending 
                // $newRankingDetails = Ranking::where('min_amount', '<=', $newTotalSpending)
                //                     ->select('id', 'name')
                //                     ->orderBy('min_amount', 'desc')
                //                     ->first();
                
                // $newRankingDetails->image = $newRankingDetails->getFirstMediaUrl('ranking');

                $response = [
                    'newPointBalance' => $newPointBalance,
                ];
            }

            // Update payment status
            $payment->update([
                'receipt_end_date' => now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                'status' => 'Successful'
            ]);

            $cashSales = $currentShiftTransaction->cash_sales + ($paymentMethodSales['Cash'] ?? 0.00);
            $cardSales = $currentShiftTransaction->card_sales + ($paymentMethodSales['Card'] ?? 0.00);
            $ewalletSales = $currentShiftTransaction->ewallet_sales + ($paymentMethodSales['E-Wallet'] ?? 0.00);
            $grossSales = $currentShiftTransaction->gross_sales + array_sum($paymentMethodSales);

            $sstAmount = $currentShiftTransaction->sst_amount + $payment->sst_amount;
            $serviceTaxAmount = $currentShiftTransaction->service_tax_amount + $payment->service_tax_amount;
            $totalDiscount = $currentShiftTransaction->total_discount + $payment->discount_amount + $payment->bill_discount_total;
            $netSales = $grossSales - $sstAmount - $serviceTaxAmount - $currentShiftTransaction->total_refund - $currentShiftTransaction->total_void - $totalDiscount;

            $currentShiftTransaction->update([
                'cash_sales' => $cashSales,
                'card_sales'=> $cardSales,
                'ewallet_sales'=> $ewalletSales,
                'gross_sales'=> $grossSales,
                'sst_amount'=> $sstAmount,
                'service_tax_amount'=> $serviceTaxAmount,
                'total_discount'=> $totalDiscount,
                'net_sales'=> $netSales,
            ]);
        
            // Update the total amount of the order based on the payment's grandtotal
            $order->update(['total_amount' => $grandTotal]);
        
            // Update order tables
            $order->orderTable->each(function ($tab) use ($request) {
                if (!in_array($tab->status, ['Order Cancelled', 'Order Voided'])) {
                    if ($request->split_bill_id && $request->split_bill_id !== 'current') {
                        $orderTableStatusArr = collect($tab->table->orderTables->whereNotIn('status', ['Order Cancelled', 'Order Voided', 'Order Completed'])->pluck('status')->unique());

                        $newTableStatus = $orderTableStatusArr->count() === 1 && $orderTableStatusArr->contains('Pending Clearance')
                                ? 'Pending Clearance'
                                : $orderTableStatusArr->filter(fn ($status) => $status !== 'Pending Clearance')->first();
                        
                        $tab->table->update(['status' => $newTableStatus]);

                    } else {
                        $tab->table->update(['status' => 'Pending Clearance']);
                    }

                    $tab->update(['status' => 'Pending Clearance']);
                }
            });

            // Get kick drawer data
            $response = array_merge($response, $this->kickDrawer());

            // POST CT Einvoice
            // $this->storeAtCtInvoice($payment, $order->orderItems);
        }

        return $response ?? 'Payment Unsuccessfull.';
    }    

    protected function storeAtCtInvoice(Payment $payment, Collection $order_items)
    {

        $payout = PayoutConfig::first();

        // in json
        $items = [];
        foreach ($order_items as $item) {
            $items[] = [
                'id' => $item->id,
                'item_name' => $item->product->product_name,
                'qty' => $item->item_qty,
                'price' => $item->product->price,
                'subtotal' => $item->item_qty * $item->product->price, // Optional
                'classification_id' => 22 // default classification ID (022 - Others)
            ];
        }

        $params = [
            'invoice_no' => $payment->receipt_no,
            'amount' => (float)$payment->total_amount,
            'sst_amount' => $payment->sst_amount,
            'service_tax_amount' => $payment->service_tax_amount,
            'total_amount' => $payment->total_amount + $payment->sst_amount + $payment->service_tax_amount,
            'date_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'status' => 'pending',
            'items' => $items,
        ];
        
        Log::debug('data', [
            'url' => $payout->url . 'api/store-invoice',
            'params' => $params
        ]);


        $response = Http::withHeaders([
            'CT-API-KEY' => $payout->api_key,
            'MERCHANT-ID' => $payout->merchant_id,
            'Content-Type' => 'application/json',  // 明确指定 JSON
            'Accept' => 'application/json',
        ])->post($payout->url . 'api/store-invoice', $params);
        
        Log::debug('response', ['response' => $response->status()]);
    }

    private function getUpdatedCurrentBill($originalOrder)
    {
        return [
            'order_items' => $originalOrder->orderItems->map(function ($item) {
                return [
                    ...$item->toArray(),
                    'transfer_qty' => $item->item_qty,
                    'balance_qty' => $item->item_qty,
                    'original_qty' => $item->item_qty,
                    'transfer_status' => '',
                    'selected' => false,
                ];
            })->toArray(),
            'amount' => $originalOrder->amount,
        ];
    }

    private function determineItemStatus(Collection $statusArr)
    {
        if ($statusArr->contains('Pending Serve')) {
            return 'Pending Serve';
        } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served'])) {
            return 'Served';
        } elseif ($statusArr->count() === 2 && $statusArr->contains('Pending Serve') && $statusArr->contains('Served')) {
            return 'Pending Serve';
        }
        return 'Cancelled';
    }

    /**
     * Get the payments made on specified currently occupied table.
     */
    public function getOccupiedTablePayments(Request $request) 
    {
        $orderTables = OrderTable::with([
                                        'table', 
                                        'order.voucher:id,reward_type,discount', 
                                        'order.payment.customer', 
                                        // 'order.orderItems' => fn($query) => $query->whereNotIn('status', ['Cancelled']),
                                        'order.orderItems.product',
                                        'order.orderItems.subItems.productItem.inventoryItem',
                                    ])
                                    ->whereIn('table_id', $request->orderTableIds)
                                    ->where('status', 'Pending Clearance')
                                    ->orderByDesc('updated_at')
                                    ->get()
                                    ->map(function ($orderTable) {
                                        $orderTable->order->orderItems->each(function ($item) {
                                            if ($item['keep_item_id']) {
                                                $item['item_name'] = $item->subItems[0]->productItem->inventoryItem['item_name'];
                                            }

                                            unset($item->subItems[0]->productItem);

                                            return $item;
                                        });

                                        return $orderTable;
                                    });

        foreach ($orderTables as $orderTable) {
            if ($orderTable->order) {  
                foreach ($orderTable->order->orderItems as $orderItem) {
                    if ($orderItem->product) { 
                        $orderItem->product->image = $orderItem->product->getFirstMediaUrl('product');
                        $orderItem->product->discount_item = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first(); 
                    }
                }
            }
        }

        // Get unique orders and include each order's payment details
        $uniqueOrders = $orderTables->pluck('order')->unique('id')->map(function ($order) {
            if($order->customer){
                $order->customer->image = $order->customer->getFirstMediaUrl('customer');
            }
            return [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'pax' => $order->pax,
                'payment' => $order->payment,
                'voucher' => $order->voucher,
                'order_items' => $order->orderItems,
                'customer' => $order->customer,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
            ];
        })->values();

        $taxes = Setting::whereIn('name', ['SST', 'Service Tax'])->pluck('value', 'name');

        $data = [
            'table_orders' => $uniqueOrders,
            'taxes' => $taxes
        ];

        return response()->json($data);
    }

    /**
     * Get all redeemable items.
     */
    public function getRedeemableItems() {
        $redeemables = Product::select('id','product_name', 'point', 'availability')
                                ->with([
                                    'productItems:id,product_id,inventory_item_id,qty', 
                                    'productItems.inventoryItem:id,inventory_id,item_name,stock_qty,status',
                                    'productItems.inventoryItem.inventory:id,name',
                                ])
                                ->where('is_redeemable', true)
                                ->get()
                                ->map(function ($product) {
                                    $product->image = $product->getFirstMediaUrl('product');
                                    $product_items = $product->productItems;
                                    $minStockCount = 0;
    
                                    if (count($product_items) > 0) {
                                        $stockCountArr = [];
    
                                        foreach ($product_items as $key => $value) {
                                            $inventory_item = IventoryItem::select('stock_qty')->find($value['inventory_item_id']);
                                            $stockQty = $inventory_item->stock_qty;
                                            $stockCount = (int)bcdiv($stockQty, (int)$value['qty']);
    
                                            array_push($stockCountArr, $stockCount);
                                        }
                                        $minStockCount = min($stockCountArr);
                                    }
                                    $product['stock_left'] = $minStockCount;
                                    unset($product->productItems);
    
                                    return $product;
                                });

        // $redeemables->each(function($redeemable){
        //     $redeemable->image = $redeemable->getFirstMediaUrl('product');
        // });
        
        return response()->json($redeemables);
    }

    /**
     * Get customer's redemption histories.
     */
    public function getCustomerPointHistories(string $id)
    {
        $pointHistories = PointHistory::with([
                                            'payment:id,order_id,point_earned',
                                            'payment.order:id,order_no',
                                            'redeemableItem:id,product_name',
                                            'customer:id,ranking',
                                            'customer.rank:id,name',
                                            'handledBy:id,full_name'
                                        ]) 
                                        ->where('customer_id', $id)
                                        ->orderBy('created_at','desc')
                                        ->get();

        $pointHistories->each(function ($record) {
            $record->image = $record->redeemableItem?->getFirstMediaUrl('product');
            $record->waiter_image = $record->handledBy->getFirstMediaUrl('user');
        });

        return response()->json($pointHistories);
    }

    /**
     * Redeem item and add to current order.
     */
    public function redeemItemToOrder(Request $request, string $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $validatedData = $request->validate(
                [
                    // 'order_id' => 'required|integer',
                    'user_id' => 'required|integer',
                    'customer_id' => 'required|integer',
                    // 'redeemable_item_id' => 'required|integer',
                    'redeem_qty' => 'required|integer',
                    'selected_item' => 'required|array',
                ], 
                ['required' => 'This field is required.']
            );

            $fixedOrderDetails = $request->matching_order_details;
            $addNewOrder = $fixedOrderDetails['current_order_completed'];

            $tableString = $this->getTableName($fixedOrderDetails['tables']);
            $pointSpent = 0;

            $waiter = User::select(['id', 'full_name'])->find($validatedData['user_id']);
            $waiter->image = $waiter->getFirstMediaUrl('user');
            $customer = Customer::select(['id', 'full_name'])->find($validatedData['customer_id']);

            if ($validatedData) {
                if ($addNewOrder) {
                    $newOrder = Order::create([
                        'order_no' => RunningNumberService::getID('order'),
                        'pax' => $fixedOrderDetails['pax'],
                        'user_id' => $fixedOrderDetails['assigned_waiter'],
                        'customer_id' => $fixedOrderDetails['customer_id'],
                        'amount' => 0.00,
                        'total_amount' => 0.00,
                        'status' => 'Pending Serve',
                    ]);
            
                    foreach ($fixedOrderDetails['tables'] as $selectedTable) {
                        $table = Table::find($selectedTable);
                        $table->update([
                            'status' => 'Pending Order',
                            'order_id' => $newOrder->id
                        ]);
                
                        OrderTable::create([
                            'table_id' => $selectedTable,
                            'pax' => $fixedOrderDetails['pax'],
                            'user_id' => $request->user_id,
                            'status' => 'Pending Order',
                            'order_id' => $newOrder->id
                        ]);
                    }
                    $newOrder->refresh();
                }

                $redeemableItem = Product::with([
                                                'productItems:id,product_id,inventory_item_id,qty',
                                                'productItems.inventoryItem:id,item_name,stock_qty,item_cat_id,inventory_id,low_stock_qty,current_kept_amt'
                                            ])
                                            ->find($validatedData['selected_item']['id']);

                $newOrderItem = OrderItem::create([
                    'order_id' => $addNewOrder ? $newOrder->id : $id,
                    'user_id' => $validatedData['user_id'],
                    'type' => 'Redemption',
                    'product_id' => $redeemableItem->id,
                    'item_qty' => $validatedData['redeem_qty'],
                    'amount_before_discount' => 0.00,
                    'discount_id' => null,
                    'discount_amount' => 0.00,
                    'amount' => 0,
                    'status' => 'Pending Serve',
                ]);

                activity()->useLog('Order')
                                ->performedOn($newOrderItem)
                                ->event('place to order')
                                ->withProperties([
                                    'waiter_name' => $waiter->full_name, 
                                    'table_name' => $tableString,
                                    'waiter_image' => $waiter->image,
                                ])
                                ->log("placed an order for :properties.table_name.");

                activity()->useLog('redeem-product')
                            ->performedOn($newOrderItem)
                            ->event('redeemed')
                            ->withProperties([
                                'edited_by' => auth()->user()->full_name,
                                'image' => auth()->user()->getFirstMediaUrl('user'),
                                'customer_name' => $customer->full_name,
                                'item_name' => $redeemableItem->product_name
                            ])
                            ->log("$customer->full_name has redeemed $redeemableItem->product_name.");
                
                $redeemableItem->productItems->each(function ($item) use ($newOrderItem) {
                    $inventoryItem = $item->inventoryItem;

                    // Deduct stock
                    $stockToBeSold = $newOrderItem->item_qty * $item->qty;
                    $oldStockQty = $inventoryItem->stock_qty;
                    $newStockQty = $oldStockQty - $stockToBeSold;

                    $newStatus = match(true) {
                        $newStockQty == 0 => 'Out of stock',
                        $newStockQty <= $inventoryItem->low_stock_qty => 'Low in stock',
                        default => 'In stock'
                    };

                    $inventoryItem->update([
                        'stock_qty' => $newStockQty,
                        'status' => $newStatus
                    ]);
                    $inventoryItem->refresh();

                    StockHistory::create([
                        'inventory_id' => $inventoryItem->inventory_id,
                        'inventory_item' => $inventoryItem->item_name,
                        'old_stock' => $oldStockQty,
                        'in' => 0,
                        'out' => $stockToBeSold,
                        'current_stock' => $inventoryItem->stock_qty,
                        'kept_balance' => $inventoryItem->current_kept_amt,
                    ]);

                    OrderItemSubitem::create([
                        'order_item_id' => $newOrderItem->id,
                        'product_item_id' => $item->id,
                        'item_qty' => $item->qty,
                        'serve_qty' => 0,
                    ]);
                });

                $customer = Customer::select(['id', 'point'])->find($validatedData['customer_id']);
                $pointSpent = $validatedData['redeem_qty'] * $redeemableItem->point;

                PointHistory::create([
                    'product_id' => $redeemableItem->id,
                    'payment_id' => null,
                    'type' => 'Used',
                    'point_type' => 'Redeem', 
                    'qty' => $validatedData['redeem_qty'],
                    'amount' => $pointSpent,
                    'old_balance' => $customer->point,
                    'new_balance' => $customer->point - $pointSpent,
                    'customer_id' => $customer->id,
                    'handled_by' => $validatedData['user_id'],
                    'redemption_date' => now()
                ]);

                $usableHistories = PointHistory::where(function ($query) {
                                                        $query->where(function ($subQuery) {
                                                                $subQuery->where([
                                                                            ['type', 'Earned'],
                                                                            ['expire_balance', '>', 0],
                                                                        ])
                                                                        ->whereNotNull('expired_at')
                                                                        ->whereDate('expired_at', '>', now());
                                                            })
                                                            ->orWhere(function ($subQuery) {
                                                                $subQuery->where([
                                                                            ['type', 'Adjusted'],
                                                                            ['expire_balance', '>', 0]
                                                                        ])
                                                                        ->whereNotNull('expired_at')
                                                                        ->whereColumn('new_balance', '>', 'old_balance')
                                                                        ->whereDate('expired_at', '>', now());
                                                            });
                                                    })
                                                    ->where('customer_id', $customer->id)
                                                    ->orderBy('expired_at', 'asc') // earliest expiry first
                                                    ->get();

                $remainingPoints = $pointSpent;

                foreach ($usableHistories as $history) {
                    if ($remainingPoints <= 0) break;

                    $deductAmount = min($remainingPoints, $history->expire_balance);
                    $history->expire_balance -= $deductAmount;
                    $history->save();

                    $remainingPoints -= $deductAmount;
                }

                $customer->decrement('point', $pointSpent);

                $order = Order::with(['orderTable.table'])->find($addNewOrder ? $newOrder->id : $id);
                
                if ($order) {
                    $statusArr = collect($order->orderItems->pluck('status')->unique());
                    $orderStatus = 'Pending Serve';
                    $orderTableStatus = 'Pending Order';
                
                    if ($statusArr->contains('Pending Serve')) {
                        $orderStatus = 'Pending Serve';
                        $orderTableStatus = 'Order Placed';
                    } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
                        $orderStatus = 'Order Served';
                        $orderTableStatus = 'All Order Served';
                    } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
                        $orderStatus = 'Order Served';
                        $orderTableStatus = 'All Order Served';
                    }

                    $order->update(['status' => $orderStatus]);
                    
                    // Update all tables associated with this order
                    $order->orderTable->each(function ($tab) use ($orderTableStatus) {
                        $tab->table->update(['status' => $orderTableStatus]);
                        $tab->update(['status' => $orderTableStatus]);
                    });
                };
            }

            return response()->json([
                'pointSpent' => $pointSpent,
                'customerPoint' => $customer->point
            ]);
        });
    }

    /**
     * Redeem entry reward and add to current order.
     */
    public function redeemEntryRewardToOrder(Request $request, string $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $validatedData = $request->validate(
                [
                    'user_id' => 'required|integer',
                    'customer_id' => 'required|integer',
                    'customer_reward_id' => 'required|integer',
                ], 
                [
                    'required' => 'This field is required.',
                    'integer' => 'This field must be an integer.',
                ]
            );

            $fixedOrderDetails = $request->matching_order_details;
            $addNewOrder = $fixedOrderDetails['current_order_completed'];

            $tableString = $this->getTableName($fixedOrderDetails['tables']);

            $waiter = User::select(['id', 'full_name'])->find($validatedData['user_id']);
            $waiter->image = $waiter->getFirstMediaUrl('user');
            $customer = Customer::select(['id', 'full_name'])->find($validatedData['customer_id']);

            if ($validatedData) {
                $selectedReward = CustomerReward::select('id', 'customer_id', 'ranking_reward_id', 'status')
                                                ->with([
                                                    'rankingReward:id,reward_type,discount,free_item,item_qty',
                                                    'rankingReward.product:id,product_name',
                                                    'rankingReward.product.productItems:id,product_id,inventory_item_id,qty',
                                                    'rankingReward.product.productItems.inventoryItem:id,item_name,stock_qty,inventory_id,low_stock_qty,current_kept_amt'
                                                ])
                                                ->find($validatedData['customer_reward_id']);

                $tierReward = $selectedReward->rankingReward;
                $freeProduct = $tierReward->product;
                $isVoucherReplaced = false;

                if ($tierReward->reward_type === 'Free Item') {
                    if ($addNewOrder) {
                        $newOrder = Order::create([
                            'order_no' => RunningNumberService::getID('order'),
                            'pax' => $fixedOrderDetails['pax'],
                            'user_id' => $fixedOrderDetails['assigned_waiter'],
                            'customer_id' => $fixedOrderDetails['customer_id'],
                            'amount' => 0.00,
                            'total_amount' => 0.00,
                            'status' => 'Pending Serve',
                        ]);
                
                        foreach ($fixedOrderDetails['tables'] as $selectedTable) {
                            $table = Table::find($selectedTable);
                            $table->update([
                                'status' => 'Pending Order',
                                'order_id' => $newOrder->id
                            ]);
                    
                            OrderTable::create([
                                'table_id' => $selectedTable,
                                'pax' => $fixedOrderDetails['pax'],
                                'user_id' => $validatedData['user_id'],
                                'status' => 'Pending Order',
                                'order_id' => $newOrder->id
                            ]);
                        }
                        $newOrder->refresh();
                    }

                    $newOrderItem = OrderItem::create([
                        'order_id' => $addNewOrder ? $newOrder->id : $id,
                        'user_id' => $validatedData['user_id'],
                        'type' => 'Redemption',
                        'product_id' => $freeProduct->id,
                        'item_qty' => $tierReward->item_qty,
                        'amount_before_discount' => 0.00,
                        'discount_id' => null,
                        'discount_amount' => 0.00,
                        'amount' => 0,
                        'status' => 'Pending Serve',
                    ]);

                    activity()->useLog('Order')
                                    ->performedOn($newOrderItem)
                                    ->event('place to order')
                                    ->withProperties([
                                        'waiter_name' => $waiter->full_name, 
                                        'table_name' => $tableString,
                                        'waiter_image' => $waiter->image,
                                    ])
                                    ->log("placed an order for :properties.table_name.");

                    activity()->useLog('redeem-tier-reward')
                                ->performedOn($newOrderItem)
                                ->event('cancelled')
                                ->withProperties([
                                    'edited_by' => auth()->user()->full_name,
                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                    'customer_name' => $customer->full_name,
                                    'item_name' => $freeProduct->product_name
                                ])
                                ->log("$customer->full_name has redeemed $freeProduct->product_name.");
                    
                    $freeProduct->productItems->each(function ($item) use ($newOrderItem) {
                        $inventoryItem = $item->inventoryItem;

                        // Deduct stock
                        $stockToBeSold = $newOrderItem->item_qty * $item->qty;
                        $oldStockQty = $inventoryItem->stock_qty;
                        $newStockQty = $oldStockQty - $stockToBeSold;

                        $newStatus = match(true) {
                            $newStockQty == 0 => 'Out of stock',
                            $newStockQty <= $inventoryItem->low_stock_qty => 'Low in stock',
                            default => 'In stock'
                        };

                        $inventoryItem->update([
                            'stock_qty' => $newStockQty,
                            'status' => $newStatus
                        ]);
                        $inventoryItem->refresh();

                        StockHistory::create([
                            'inventory_id' => $inventoryItem->inventory_id,
                            'inventory_item' => $inventoryItem->item_name,
                            'old_stock' => $oldStockQty,
                            'in' => 0,
                            'out' => $stockToBeSold,
                            'current_stock' => $inventoryItem->stock_qty,
                            'kept_balance' => $inventoryItem->current_kept_amt,
                        ]);

                        OrderItemSubitem::create([
                            'order_item_id' => $newOrderItem->id,
                            'product_item_id' => $item->id,
                            'item_qty' => $item->qty,
                            'serve_qty' => 0,
                        ]);
                    });

                    $order = Order::with(['orderTable.table'])->find($addNewOrder ? $newOrder->id : $id);
                    
                    if ($order) {
                        $statusArr = collect($order->orderItems->pluck('status')->unique());
                        $orderStatus = 'Pending Serve';
                        $orderTableStatus = 'Pending Order';
                    
                        if ($statusArr->contains('Pending Serve')) {
                            $orderStatus = 'Pending Serve';
                            $orderTableStatus = 'Order Placed';
                        } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
                            $orderStatus = 'Order Served';
                            $orderTableStatus = 'All Order Served';
                        } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
                            $orderStatus = 'Order Served';
                            $orderTableStatus = 'All Order Served';
                        }

                        $order->update(['status' => $orderStatus]);
                        $selectedReward->update(['status' => 'Redeemed']);

                        
                        // Update all tables associated with this order
                        $order->orderTable->each(function ($tab) use ($orderTableStatus) {
                            $tab->table->update(['status' => $orderTableStatus]);
                            $tab->update(['status' => $orderTableStatus]);
                        });
                    };
                };

                if (in_array($tierReward->reward_type, ['Discount (Amount)', 'Discount (Percentage)'])) {
                    $order = Order::with([
                                        'customer:id,point,total_spending,ranking', 
                                        'customer.rewards:id,customer_id,ranking_reward_id,status',
                                    ])
                                    ->find($id);

                    $oldOrderVoucherId = $order->voucher_id;
                    $voucher = [
                        'id' => $tierReward->id,
                        'customer_reward_id' => $selectedReward->id,
                    ];

                    $this->updateOrderVoucher($order, $voucher);

                    if ($oldOrderVoucherId && $oldOrderVoucherId !== $tierReward->id) {
                        $isVoucherReplaced = true;
                    }
                };

                $amountDiscountSummary = $isVoucherReplaced 
                        ? "The currently applied reward has been replaced with 'RM $tierReward->discount Discount' for this order."
                        : "'RM $tierReward->discount Discount' has been applied to this order.";

                $percentageDiscountSummary = $isVoucherReplaced 
                        ? "The currently applied reward has been replaced with '$tierReward->discount% Discount' for this order."
                        : "'$tierReward->discount% Discount' has been applied to this order.";

                $summary = match ($tierReward->reward_type) {
                    'Discount (Amount)' => $amountDiscountSummary,
                    'Discount (Percentage)' => $percentageDiscountSummary,
                    'Free Item' => "'$freeProduct?->product_name' has been added to this customer's order.",
                };
            }
            
            return response()->json($summary);
        });
    }

    /**
     * Get customer's tier rewards.
     */
    public function getCustomerTierRewards(string $id)
    {
        $customer = Customer::select('id')
                            ->with([
                                'rewards:id,customer_id,ranking_reward_id,status,updated_at',
                                'rewards.rankingReward:id,ranking_id,reward_type,min_purchase,discount,min_purchase_amount,bonus_point,free_item,item_qty,updated_at',
                                'rewards.rankingReward.ranking:id,name',
                                'rewards.rankingReward.product:id,product_name,availability',
                                'rewards.rankingReward.product.productItems'
                            ])
                            ->find($id);
                            
        foreach ($customer->rewards as $key => $reward) {
            if ($reward->rankingReward->reward_type === 'Free Item') {
                $product = $reward->rankingReward->product;
                $product_items = $product->productItems;
                $minStockCount = 0;
    
                if (count($product_items) > 0) {
                    $stockCountArr = [];
    
                    foreach ($product_items as $key => $value) {
                        $inventory_item = IventoryItem::select('stock_qty')->find($value['inventory_item_id']);
    
                        $stockQty = $inventory_item->stock_qty;
                        
                        $stockCount = (int)bcdiv($stockQty, (int)$value['qty']);
                        array_push($stockCountArr, $stockCount);
                    }
                    $minStockCount = min($stockCountArr);
                }
                $product['stock_left'] = $minStockCount; 
                unset($product->productItems);
            }
        };

        return response()->json($customer->rewards);
    }

    /**
     * Remove the voucher applied to the order and reinstate back to customer's reward list.
     */
    private function updateOrderVoucher($order, $appliedVoucher)
    {
        $orderVoucher = $order->voucher;

        // Case 1: No voucher applied - remove existing voucher if present
        if (!$appliedVoucher) {
            if ($orderVoucher) {
                $this->removeOrderVoucher($order);
            }
            return;
        }

        // Case 2: Voucher applied
        $isDifferentVoucher = !$orderVoucher || $orderVoucher->id !== $appliedVoucher['id'];
    
        // Remove old voucher if it's different from the new one
        if ($orderVoucher && $isDifferentVoucher) {
            $this->removeOrderVoucher($order);
        }
    
        // Apply new voucher if it's different or if there was no voucher before
        if ($isDifferentVoucher) {
            $selectedReward = CustomerReward::find($appliedVoucher['customer_reward_id']);
            $order->update(['voucher_id' => $appliedVoucher['id']]);
            $selectedReward->update(['status' => 'Redeemed']);
        }
    }

    /**
     * Remove the voucher applied to the order and reinstate back to customer's reward list.
     */
    private function removeOrderVoucher($order)
    {
        $customer = $order->customer;
        $removalTarget = $customer->rewards->where('ranking_reward_id', $order->voucher_id)->first();
            
        if ($removalTarget) {
            $order->update(['voucher_id' => null]);
            $removalTarget->update(['status' => 'Active']);
        }
    }

    public function getTableKeepItem(string $id)
    {
        $orderTables = OrderTable::with([
                                        'table',
                                        'order.payment.customer',
                                        'order.orderItems' => fn($query) => $query->where('status', 'Served')->orWhere('status', 'Pending Serve'),
                                        'order.orderItems.product.category:id,keep_type',
                                        'order.orderItems.subItems.productItem.inventoryItem',
                                        'order.orderItems.subItems.keepItems.oldestKeepHistory' => function ($query) {
                                            $query->where('status', 'Keep');
                                        },
                                        'order.orderItems.keepItem.oldestKeepHistory'  => function ($query) {
                                            $query->where('status', 'Keep');
                                        }
                                    ])
                                    ->where('table_id', $id)
                                    ->where(function ($query){
                                        $query->where('status', 'Pending Clearance')
                                            ->orWhere('status', 'All Order Served')
                                            ->orWhere('status', 'Order Placed');
                                    })
                                    ->orderByDesc('updated_at')
                                    ->get()
                                    ->map(function ($orderTable) {
                                        $orderTable->order->orderItems->each(function ($item) {
                                            if ($item['keep_item_id']) {
                                                $item['item_name'] = $item->subItems[0]->productItem->inventoryItem['item_name'];
                                            }

                                            // unset($item->subItems[0]->productItem);

                                            return $item;
                                        });

                                        return $orderTable;
                                    }); 

        //get images
        foreach ($orderTables as $orderTable) {
            if ($orderTable->order) {
                foreach ($orderTable->order->orderItems as $orderItem) {
                    if ($orderItem->product) {
                        $orderItem->product->image = $orderItem->product->getFirstMediaUrl('product');
                    }
                }
            }
        }

        $allCustomers = Customer::select('id', 'full_name', 'email', 'phone', 'status')
                                ->where(function ($query) {
                                    $query->where('status', '!=', 'void')
                                        ->orWhereNull('status'); // Handle NULL cases
                                })
                                ->get();
                                
        $allCustomers->each(function($customer){
            $customer->image = $customer->getFirstMediaUrl('customer');
        });

        $uniqueOrders = $orderTables->pluck('order')->unique('id')->map(function ($order) {
            if ($order->customer) {
                $order->customer->image = $order->customer->getFirstMediaUrl('customer');
            }
            return [
                'order_time' => Carbon::parse($order->created_at)->format('d/m/Y, H:i'),
                'order_no' => $order->order_no,
                'order_items' => $order->orderItems,
                'customer' => $order->customer,
            ];
        })->values();
        
        return response()->json([
            'uniqueOrders' => $uniqueOrders,
            'allCustomers' => $allCustomers,
        ]);
    }

    public function getTableKeepHistories(string $id) 
    {
        // Fetch order tables with necessary relationships
        $orderTables = OrderTable::with([
                                    'table',
                                    'order.orderItems' => fn($query) => $query->whereIn('status', ['Served', 'Pending Serve']),
                                    'order.orderItems.subItems.productItem.inventoryItem',
                                    'order.orderItems.subItems.keepItems.keepHistories' => fn($query) => $query->where('status', 'Keep'),
                                    'order.orderItems.subItems.keepItems.waiter:id,full_name',
                                    'order.orderItems.subItems.keepItems.customer:id,full_name',
                                    'order.orderItems.subItems.keepItems.orderItemSubitem.productItem:id,inventory_item_id',
                                    'order.orderItems.subItems.keepItems.orderItemSubitem.productItem.inventoryItem:id,item_name',
                                ])
                                ->where('table_id', $id)
                                ->whereIn('status', ['Pending Clearance', 'All Order Served', 'Order Placed'])
                                ->orderByDesc('updated_at')
                                ->get();
    
        // Collect unique orders and map keep histories
        $uniqueOrders = $orderTables->pluck('order')->unique('id')->map(function ($order) {
            return [
                'order_time' => Carbon::parse($order->created_at)->format('d/m/Y, H:i'),
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'keep_histories' => $order->orderItems->flatMap(fn($item) =>
                    $item->subItems->flatMap(fn($subItem) =>
                        $subItem->keepItems->flatMap(fn($keepItem) =>
                            $keepItem->keepHistories->map(function ($history) use ($keepItem, $subItem) {
                                $keepItem->customer['image'] = $keepItem->customer->getFirstMediaUrl('customer');
                                $keepItem->waiter['image'] = $keepItem->waiter->getFirstMediaUrl('user');

                                return array_merge($history->toArray(), [
                                    'keep_item' => [
                                        'id' => $keepItem->id,
                                        'sub_item_id' => $keepItem->sub_item_id,
                                        'product_item_id' => $subItem->productItem->id,
                                        'inventory_item_name' => $subItem->productItem->inventoryItem->item_name,
                                        'remark' => $keepItem->remark,
                                        'expired_from' => $keepItem->expired_from,
                                        'expired_to' => $keepItem->expired_to,
                                        'created_at' => $keepItem->created_at,
                                        'updated_at' => $keepItem->updated_at,
                                        'waiter' => $keepItem->waiter,
                                        'customer' => $keepItem->customer,
                                        'image' => $subItem->productItem 
                                                ? $subItem->productItem->product->getFirstMediaUrl('product') 
                                                : $subItem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory'),
                                    ]
                                ]);
                            })
                        )
                    )
                )->values(),
                'customer' => $order->customer ? [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'image' => $order->customer->getFirstMediaUrl('customer')
                ] : null,
            ];
        })->values();
    
        return response()->json($uniqueOrders);
    }

    public function reactivateExpiredItems(Request $request)
    {
        $targetItem = KeepHistory::with('keepItem')->find($request->input('id'))->first();

        KeepHistory::create([
            'keep_item_id' => $targetItem->keepItem->id,
            'qty' => $targetItem->keepItem->qty,
            'cm' => number_format((float) $targetItem->keepItem->cm, 2, '.', ''),
            'keep_date' => Carbon::parse($request->input('expiry_date'))->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
            'user_id' => auth()->user()->id,
            'kept_from_table' => $targetItem->keepItem->kept_from_table,
            'status' => 'Extended'
        ]);
        
        $item = KeepItem::find($targetItem->keepItem->id)
                        ->with('orderItemSubitem.productItem.inventoryItem')
                        ->first();

        $item->update([
            'expired_to' => Carbon::parse($request->input('expiry_date'))->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
            'status' => 'Keep',
        ]);

        activity()->useLog('extend-expiration-date')
                    ->performedOn($item)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'item' => $item->orderItemSubitem->productItem->inventoryItem->item_name,
                    ])
                    ->log("Kept item ':properties.item''s expiration date is updated.");

        
        // Deduct qty from inventory item stock based on kept item qty (only for kept items with qty)
        if ($item->qty > $item->cm) {
            $inventoryItem = $item->orderItemSubitem->productItem->inventoryItem;
            $expiredKeepQty = $item->qty;
            $oldStock = $inventoryItem->stock_qty;

            $newStock = $oldStock - $expiredKeepQty;
            $newKeptBalance = $inventoryItem->current_kept_amt + $expiredKeepQty;
            $newTotalKept = $inventoryItem->total_kept + $expiredKeepQty;

            $newStatus = match(true) {
                $newStock == 0 => 'Out of stock',
                $newStock <= $inventoryItem->low_stock_qty => 'Low in stock',
                default => 'In stock'
            };

            $inventoryItem->update([
                'stock_qty' => $newStock, 
                'status' => $newStatus,
                'current_kept_amt' => $newKeptBalance, 
                'total_kept' => $newTotalKept, 
            ]);

            StockHistory::create([
                'inventory_id' => $inventoryItem->inventory_id,
                'inventory_item' => $inventoryItem->item_name,
                'old_stock' => $oldStock,
                'in' => 0.00,
                'out' => $expiredKeepQty,
                'current_stock' => $newStock,
                'kept_balance' => $newKeptBalance
            ]);
            
            if($newStatus === 'Out of stock'){
                Notification::send(User::where('position', 'admin')->get(), new InventoryOutOfStock($inventoryItem->item_name, $inventoryItem->id));
            };

            if($newStatus === 'Low in stock'){
                Notification::send(User::where('position', 'admin')->get(), new InventoryRunningOutOfStock($inventoryItem->item_name, $inventoryItem->id));
            }
        }

        return redirect()->back();
    }

    public function expireKeepItem(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $keepItem = KeepItem::with('orderItemSubitem.productItem.inventoryItem')
                                ->where('id', $request->id)
                                ->first(); 

            // if (now()->greaterThanOrEqualTo(Carbon::parse($keepItem->expired_to)->endOfDay())) { 
                $keepItem->update(['status' => 'Expired']); 

                activity()->useLog('expire-kept-item')
                            ->performedOn($keepItem)
                            ->event('updated')
                            ->withProperties([
                                'edited_by' => auth()->user()->full_name,
                                'image' => auth()->user()->getFirstMediaUrl('user'),
                                'item_name' => $keepItem->orderItemSubitem->productItem->inventoryItem->item_name,
                            ])
                            ->log(":properties.item_name is expired.");

                KeepHistory::create([
                    'keep_item_id' => $keepItem->id,
                    'qty' => $keepItem->qty,
                    'cm' => $keepItem->cm,
                    'keep_date' => $keepItem->created_at,
                    'user_id' => auth()->user()->id,
                    'kept_from_table' => $keepItem->kept_from_table,
                    'status' => 'Expired',
                ]);

                $inventoryItem = $keepItem->orderItemSubitem->productItem->inventoryItem;
                if ($keepItem->qty > $keepItem->cm) {
                    $expiredKeepItem = $keepItem->qty;
                    $oldStock = $inventoryItem->stock_qty;

                    $newStock = $oldStock + $expiredKeepItem;
                    $newKeptBalance = $inventoryItem->current_kept_amt - $expiredKeepItem;
                    $newTotalKept = $inventoryItem->total_kept - $expiredKeepItem;

                    $newStatus = match(true) {
                        $newStock == 0 => 'Out of stock',
                        $newStock <= $inventoryItem->low_stock_qty => 'Low in stock',
                        default => 'In stock'
                    };

                    $inventoryItem->update([
                        'stock_qty' => $newStock, 
                        'status' => $newStatus,
                        'current_kept_amt' => $newKeptBalance, 
                        'total_kept' => $newTotalKept, 
                    ]);

                    StockHistory::create([
                        'inventory_id' => $inventoryItem->inventory_id,
                        'inventory_item' => $inventoryItem->item_name,
                        'old_stock' => $oldStock,
                        'in' => $expiredKeepItem,
                        'out' => 0,
                        'current_stock' => $newStock,
                        'kept_balance' => $newKeptBalance
                    ]);

                    if($newStatus === 'Out of stock'){
                        Notification::send(User::where('position', 'admin')->get(), new InventoryOutOfStock($inventoryItem->item_name, $inventoryItem->id));
                    };

                    if($newStatus === 'Low in stock'){
                        Notification::send(User::where('position', 'admin')->get(), new InventoryRunningOutOfStock($inventoryItem->item_name, $inventoryItem->id));
                    }
                }
            // } 

            $customer = Customer::with([
                'keepItems' => function ($query) {
                    $query->select('id', 'customer_id', 'order_item_subitem_id', 'user_id', 'qty', 'cm', 'remark', 'status', 'expired_to', 'created_at')
                            ->where('status', 'Keep')
                            ->with([
                                'orderItemSubitem.productItem:id,inventory_item_id',
                                'orderItemSubitem.productItem.inventoryItem:id,item_name',
                                'waiter:id,full_name'
                            ]);
                }
            ])
            ->find($request->customer_id);

            foreach ($customer->keepItems as $key => $keepItem) {
                $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
                unset($keepItem->orderItemSubitem);

                $keepItem->image = $keepItem->orderItemSubitem->productItem 
                        ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                        : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

                $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
            }

            return response()->json($customer->keepItems);
        });
    }

    public function deleteKeptItem(Request $request)
    {
        $validatedData = $request->validate([
            'id' => ['required'],
            'customer_id' => ['required'],
            'remark' => ['required', 'string'],
            'remark_description' => ['max:255'],
        ], [
            'remark.required' => 'The remark field is required.',
            'remark.string' => 'The remark must be a valid string.',
            'remark_description.max' => 'The remark description may not be greater than 255 characters.',
        ]);

        $remarkDesc = $validatedData['remark_description'] ? ': ' . $validatedData['remark_description'] : '';

        $targetItem = KeepItem::where('id',$validatedData['id'])
                                ->with([
                                    'orderItemSubitem:id,product_item_id',
                                    'orderItemSubitem.productItem:id,inventory_item_id',
                                    'orderItemSubitem.productItem.inventoryItem:id,item_name'
                                ])
                                ->first();

        $targetItem->update([
            // 'qty' => 0.00,
            'status' => 'Deleted',
        ]);

        activity()->useLog('delete-kept-item')
                    ->performedOn($targetItem)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'item_name' => $targetItem->orderItemSubitem->productItem->inventoryItem->item_name,
                    ])
                    ->log(":properties.item_name is deleted.");

        KeepHistory::create([
            'keep_item_id' => $targetItem->id,
            'qty' => $targetItem->qty,
            'cm' => number_format((float) $targetItem->cm, 2, '.', ''),
            'keep_date' => $targetItem->created_at,
            'remark' => $validatedData['remark'] . $remarkDesc,
            'user_id' => auth()->user()->id,
            'kept_from_table' => $targetItem->kept_from_table,
            'status' => 'Deleted',
        ]);
        
        // Deduct kept item qty and insert back into inventory item stock (only for kept items with qty)
        if ($targetItem->qty > $targetItem->cm) {
            $inventoryItem = $targetItem->orderItemSubitem->productItem->inventoryItem;
            $deletedKeepQty = $targetItem->qty;
            $oldStock = $inventoryItem->stock_qty;

            $newStock = $oldStock + $deletedKeepQty;
            $newKeptBalance = $inventoryItem->current_kept_amt - $deletedKeepQty;
            $newTotalKept = $inventoryItem->total_kept - $deletedKeepQty;

            $newStatus = match(true) {
                $newStock == 0 => 'Out of stock',
                $newStock <= $inventoryItem->low_stock_qty => 'Low in stock',
                default => 'In stock'
            };

            $inventoryItem->update([
                'stock_qty' => $newStock, 
                'status' => $newStatus,
                'current_kept_amt' => $newKeptBalance, 
                'total_kept' => $newTotalKept, 
            ]);

            StockHistory::create([
                'inventory_id' => $inventoryItem->inventory_id,
                'inventory_item' => $inventoryItem->item_name,
                'old_stock' => $oldStock,
                'in' => $deletedKeepQty,
                'out' => 0,
                'current_stock' => $newStock,
                'kept_balance' => $newKeptBalance
            ]);
            
            if($newStatus === 'Out of stock'){
                Notification::send(User::where('position', 'admin')->get(), new InventoryOutOfStock($inventoryItem->item_name, $inventoryItem->id));
            };

            if($newStatus === 'Low in stock'){
                Notification::send(User::where('position', 'admin')->get(), new InventoryRunningOutOfStock($inventoryItem->item_name, $inventoryItem->id));
            }
        }

        $customer = Customer::with([
            'keepItems' => function ($query) {
                $query->select('id', 'customer_id', 'order_item_subitem_id', 'user_id', 'qty', 'cm', 'remark', 'status', 'expired_to', 'created_at')
                        ->where('status', 'Keep')
                        ->with([
                            'orderItemSubitem.productItem:id,inventory_item_id',
                            'orderItemSubitem.productItem.inventoryItem:id,item_name',
                            'waiter:id,full_name'
                        ]);
            }
        ])
        ->find($request->customer_id);

        foreach ($customer->keepItems as $key => $keepItem) {
            $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
            unset($keepItem->orderItemSubitem);

            $keepItem->image = $keepItem->orderItemSubitem->productItem 
                    ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                    : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

            $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
        }

        return response()->json($customer->keepItems);
    }

    public function pendingServeItems(String $id)
    {
        $currentTable = OrderTable::where('table_id', $id)
                                    ->whereNotIn('status', ['Order Completed', 'Order Cancelled', 'Order Voided'])
                                    ->whereHas('order.orderItems', function ($query) {
                                        $query->where('status', 'Pending Serve');
                                    })
                                    ->with([
                                        'order:id,order_no,created_at,customer_id',
                                        'order.orderItems' => function ($query) {
                                            $query->where('status', 'Pending Serve')
                                                ->select('id', 'amount_before_discount', 'amount', 'product_id', 'item_qty', 'order_id', 'status');
                                        },
                                        'order.orderItems.product:bucket,id,price,product_name,point',
                                        'order.orderItems.subItems:id,item_qty,product_item_id,serve_qty,order_item_id',
                                        'order.orderItems.subItems.productItem:id,product_id,inventory_item_id',
                                        'order.orderItems.subItems.productItem.inventoryItem:id,item_name',
                                        'order.customer:id,full_name'
                                    ])
                                    ->select('table_id', 'order_id', 'id')
                                    ->orderByDesc('id')
                                    ->get();

        // if($currentTable->order->customer) {
        //     $currentTable->order->customer->image = $currentTable->order->customer_id ? $currentTable->order->customer->getFirstMediaUrl('customer') : null;
        // }

        $currentTable->each(function ($table) {
            if ($table->order) {
                if ($table->order->customer_id) {
                    $table->order->customer->image = $table->order->customer->getFirstMediaUrl('customer');
                }

                foreach ($table->order->orderItems as $orderItem) {
                    $orderItem->image = $orderItem->product->getFirstMediaUrl('product');
                }
            }
        });
    
        return response()->json($currentTable);
    }

    public function editKeptItemDetail(Request $request)
    {
        $validatedData = $request->validate([
            'id' => ['required'],
            'kept_amount' => ['required', 'numeric'],
            'remark' => ['nullable', 'max:60'],
            'expired_to' => ['required']
        ], [
            'kept_amount.required' => 'The amount is required.',
            'kept_amount.numeric' => 'The amount must be an integer.',
            'remark.max' => 'The remark may not be longer than 60 characters.',
            'expired_to.required' => 'Expiry date is required.',
            // 'expired_to.datetime' => 'Invalid input.',
        ]);
        
        $targetItem = KeepItem::with([
                                    'orderItemSubitem:id,product_item_id',
                                    'orderItemSubitem.productItem:id,inventory_item_id',
                                    'orderItemSubitem.productItem.inventoryItem:id,item_name'
                                ])
                                ->where('id', $validatedData['id'])
                                ->first();

        $targetItem->update([
            'qty' => $request->input('keptIn') === 'qty' ? round($validatedData['kept_amount'], 2) : 0.00,
            'cm' => $request->input('keptIn') === 'cm' ? round($validatedData['kept_amount'], 2) : 0.00,
            'remark' => $validatedData['remark'] ?? $validatedData['remark'],
            'expired_to' => Carbon::parse($validatedData['expired_to'])->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
        ]);

        KeepHistory::create([
            'keep_item_id' => $validatedData['id'],
            'qty' => $request->input('keptIn') === 'qty' ? round($validatedData['kept_amount'], 2) : 0.00,
            'cm' => $request->input('keptIn') === 'cm' ? number_format((float) $validatedData['kept_amount'], 2, '.', '') : '0.00',
            'keep_date' => $targetItem->expired_to,
            'user_id' => auth()->user()->id,
            'kept_from_table' => $targetItem->kept_from_table,
            'remark' => $targetItem->remark,
            'status' => 'Edited',
        ]);

        activity()->useLog('update-kept-item')
                    ->performedOn($targetItem)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'item' => $targetItem->orderItemSubitem->productItem->inventoryItem->item_name,
                    ])
                    ->log("Kept item ':properties.item''s details has been updated.");

        $customer = Customer::with([
            'keepItems' => function ($query) {
                $query->select('id', 'customer_id', 'order_item_subitem_id', 'user_id', 'qty', 'cm', 'remark', 'status', 'expired_to', 'created_at')
                        ->where('status', 'Keep')
                        ->with([
                            'orderItemSubitem.productItem:id,inventory_item_id',
                            'orderItemSubitem.productItem.inventoryItem:id,item_name',
                            'waiter:id,full_name'
                        ]);
            }
        ])
        ->find($request->customer_id);

        foreach ($customer->keepItems as $key => $keepItem) {
            $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
            unset($keepItem->orderItemSubitem);

            $keepItem->image = $keepItem->orderItemSubitem->productItem 
            ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
            : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

            $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
        }

        return response()->json($customer->keepItems);
    }

    public function mergeTable(Request $request) 
    {
        $validatedData = $request->validate([
            'customer_id' => 'nullable',
            'id' => 'required|integer',
            'tables' => 'required|array',
        ]);

        return DB::transaction(function () use ($validatedData, $request) {
            $tables = collect();

            foreach ($validatedData['tables'] as $key => $table) {
                $tables->push($table);
            }
            
            $filteredTables = $tables->filter(fn ($table) => $table['order_id']);
            $uniqueFilteredTables = $filteredTables->unique('order_id');
            $hasMultipleOrderId = $uniqueFilteredTables->count() > 1 && $uniqueFilteredTables->filter(fn ($table) => $table['status'] !== 'Pending Clearance')->count() > 1;

            if ($uniqueFilteredTables->count() === 1) {
                $currentTableFiltered = $filteredTables->first(fn ($table) => $table['order_id']);
                $currentOrderId = $currentTableFiltered['order_id'];
            }

            if ($uniqueFilteredTables->count() > 1 && $uniqueFilteredTables->filter(fn ($table) => $table['status'] !== 'Pending Clearance')->count() === 1) {
                $currentTableFiltered = $filteredTables->first(fn ($table) => $table['order_id'] &&  $table['status'] !== 'Pending Clearance');
                $currentOrderId = $currentTableFiltered['order_id'];
            }

            // dd($hasMultipleOrderId, $currentOrderId ?? null, $filteredTables, $uniqueFilteredTables, $request['tables']);

            // Get the latest order tables and calculate totals in a single pass
            $totalPax = 0;
            $totalAmount = 0;
            $statusArr = collect();
            // $temp = collect();

            foreach ($uniqueFilteredTables as $table) {
                if (empty($table['order_tables'])) {
                    continue;
                }

                // Find the latest order_table without creating intermediate collections
                $latestOrderTable = null;
                $latestTimestamp = null;
                
                foreach ($table['order_tables'] as $orderTable) {
                    $timestamp = $orderTable['created_at'] ?? 0;
                    if ($latestTimestamp === null || $timestamp > $latestTimestamp) {
                        $latestTimestamp = $timestamp;
                        $latestOrderTable = $orderTable;
                    }
                }
                
                // need to add condition to not include adding the total amount for order that have already been paid 
                // and order table/ table is completed, cancelled, voided, or pending clearance
                if ($latestOrderTable && !in_array($latestOrderTable['status'], ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided'])) {
                    // $temp->push($latestOrderTable);
                    $totalPax += (int)($latestOrderTable['pax'] ?? 0);
                    $totalAmount += (float)($latestOrderTable['order']['total_amount'] ?? 0);

                    if (count($latestOrderTable['order']['order_items']) > 0) {
                        $statusArr->push(...collect($latestOrderTable['order']['order_items'])->pluck('status')->unique());
                    }
                }
            }

            $orderStatus = 'Pending Serve';
            $orderTableStatus = 'Pending Order';
        
            if ($statusArr->contains('Pending Serve')) {
                $orderStatus = 'Pending Serve';
                $orderTableStatus = 'Order Placed';
            } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
                $orderStatus = 'Order Served';
                $orderTableStatus = 'All Order Served';
            } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
                $orderStatus = 'Order Served';
                $orderTableStatus = 'All Order Served';
            }

            //step 1: create new order
            if ($hasMultipleOrderId) {
                $newOrder = Order::create([
                    'order_no' => RunningNumberService::getID('order'),
                    'pax' => $totalPax,
                    'user_id' => auth()->user()->id,
                    'customer_id' => $validatedData['customer_id'],
                    'amount' => $totalAmount,
                    'total_amount' => $totalAmount,
                    'status' => $orderStatus,
                ]);

                $uniqueOrderIdArray = $uniqueFilteredTables->pluck('order_id');

                $uniqueOrderIdArray->each(function ($uniqueOrder) {
                    $tempOrder = Order::with([
                                            'orderTable' => fn ($query) => $query->where('status' , 'Pending Clearance'),
                                            'customer:id',
                                            'customer.rewards:id,customer_id,ranking_reward_id,status',
                                        ])
                                        ->find($uniqueOrder);

                    if ($tempOrder && !in_array($tempOrder->status, ['Order Completed', 'Order Cancelled', 'Order Voided'])) {
                        $tempOrder->update(['status' => 'Order Merged']);
                        // $tempOrder->orderTable->update(['order_id', $newOrder->id]);
        
                        if ($tempOrder->customer && $tempOrder->voucher) { // need to add removal flow for all scenario when merging
                            $this->removeOrderVoucher($tempOrder);
                        }
                    }
                });
            }

            $designatedOrderId = $currentOrderId ?? $newOrder->id;

            //step 2: update listed tables to order merged & create new order table
            foreach($request['tables'] as $table){
                $currentTable = Table::find($table['id']);
                $currentTable->update([
                    'order_id' => $designatedOrderId,
                    'status' => $orderTableStatus,
                    'is_locked' => true,
                    'locked_by' => auth()->user()->id,
                ]);

                // Find the latest order_table without creating intermediate collections
                $newestOrderTable = null;
                $newestTimestamp = null;
                
                foreach ($table['order_tables'] as $orderTable) {
                    $timestamp = $orderTable['created_at'] ?? 0;
                    if ($newestTimestamp === null || $timestamp > $newestTimestamp) {
                        $newestTimestamp = $timestamp;
                        $newestOrderTable = $orderTable;
                    }
                }

                if (empty($table['order_tables']) || in_array($newestOrderTable['status'], ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided'])) {
                    OrderTable::create([
                        'table_id' => $table['id'],
                        'pax' => $totalPax,
                        'user_id' => auth()->user()->id,
                        'status' => $orderTableStatus,
                        'order_id' => $designatedOrderId,                
                    ]);
                    
                } else {
                    $mergingTable = OrderTable::find($newestOrderTable['id']);
                    $mergingTable->update([
                        'status' => $orderTableStatus,
                        'order_id' => $designatedOrderId
                    ]);
                    $mergingTable->save();

                    $orderItems = collect($newestOrderTable['order']['order_items']);

                    $orderItems->each(function ($item) use ($designatedOrderId) {
                        $orderItem = OrderItem::find($item['id']);
                        $orderItem->update(['order_id' => $designatedOrderId]);
                        $orderItem->refresh();
                    });
                }
            }

            // Passing back updated/new order details
            $designatedOrder = Order::with([
                        'orderItems.product.discount:id,name',
                        'orderItems.product.productItems.inventoryItem',
                        'orderItems.productDiscount:id,discount_id,price_before,price_after',
                        'orderItems.productDiscount.discount:id,name',
                        'orderItems.handledBy:id,full_name',
                        'orderItems.subItems.keepItems.keepHistories' => function ($query) {
                            $query->where('status', 'Keep')->latest()->offset(1)->limit(100);
                        },
                        'orderItems.subItems.keepItems.oldestKeepHistory' => function ($query) {
                            $query->where('status', 'Keep');
                        },
                        'orderItems.keepItem:id,qty,cm,remark,expired_from,expired_to', 
                        'orderItems.keepItem.oldestKeepHistory:id,keep_item_id,qty,cm,status',
                        'orderItems.keepItem.keepHistories' => function ($query) {
                            $query->where('status', 'Keep')->oldest()->offset(1)->limit(100);
                        },
                        'waiter:id,full_name',
                        'customer:id,full_name,email,phone,point',
                        'orderTable:id,order_id,table_id,status',
                        'orderTable.table:id,table_no',
                        'payment:id,order_id',
                        'voucher:id,reward_type,discount'
                    ])
                    ->find($designatedOrderId);

            if ($designatedOrder->orderItems) {
                foreach ($designatedOrder->orderItems as $orderItem) {
                    $orderItem->product->image = $orderItem->product->getFirstMediaUrl('product');
                    $orderItem->handledBy->image = $orderItem->handledBy->getFirstMediaUrl('user');
                    $orderItem->product->discount_item = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first();
                    unset($orderItem->product->discountItems);
                }
            }

            if ($designatedOrder->customer) {
                $designatedOrder->customer->image = $designatedOrder->customer->getFirstMediaUrl('customer');
            }

            return response()->json($designatedOrder);
        });
    }

    public function splitTable(Request $request) 
    {
        return DB::transaction(function () use ($request) {
            $splitType = $request->splitType;

            if ($splitType === 'unmerge') {
                $tablesToSplit = $request->tables['tables_to_split'];

                foreach ($tablesToSplit as $table) {
                    foreach ($table['order_tables'] as $orderTable) {
                        $existingOrderTable = OrderTable::with('table')->find($orderTable['id']);

                        $existingOrderTable->update([
                            'status' => $existingOrderTable->status === 'Pending Clearance' ? 'Order Completed' : 'Order Cancelled'
                        ]);
                        
                        $existingOrderTable->table->update([
                            'status' => 'Empty Seat',
                            'order_id' => null,
                            'is_locked' => false,
                        ]);

                    }
                }
            }

            if ($splitType === 'reassign') {
                $currentTable = $request->currentTable;
                $currentTableItems = collect($currentTable['order_items']);
                $totalCurrentOrderAmount = 0.00;
                $totalCurrentOrderDiscountedAmount = 0.00;
        
                // Current table
                $currentTableItems->each(function ($item) use (&$totalCurrentOrderAmount, &$totalCurrentOrderDiscountedAmount) {
                    $balanceQty = $item['balance_qty'];
                    $originalQty = $item['original_qty'];
                    $remainingQty = $originalQty - $balanceQty;
                    $orderItem = OrderItem::with('product')->find($item['id']);
                    
                    // $transferPercentage = round(($originalQty - $balanceQty) / $originalQty * 100, 2);
                    // $balancePercentage = round($balanceQty / $originalQty * 100, 2);
                    // Calculate per-unit amounts
                    $amountBeforeDiscountPerUnit = $orderItem->product->price;
                    $currentProductDiscount = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first();
                    $amountPerUnit = round($currentProductDiscount ? $currentProductDiscount['price_after'] : $amountBeforeDiscountPerUnit, 2);
                    $discountPerUnit = $amountBeforeDiscountPerUnit - $amountPerUnit;
                    
                    if ($balanceQty == 0) {
                        $totalCurrentOrderAmount -= $item['amount'];
                        $totalCurrentOrderDiscountedAmount -= $item['discount_amount'];
        
                    } elseif ($balanceQty < $originalQty) {
                        $totalCurrentOrderAmount -= round($amountPerUnit * $remainingQty, 2);
                        $totalCurrentOrderDiscountedAmount -= round($discountPerUnit * $remainingQty, 2);
        
                        $newStatusArr = collect();
                        $orderItem->subItems->each(function ($subItem) use (&$balanceQty, &$newOrderItem, &$newStatusArr) {
                            $newSubItemServeQty = $balanceQty * $subItem['item_qty'];
                            $newQtyServed = $newSubItemServeQty < $subItem['serve_qty'] ? $newSubItemServeQty : $subItem['serve_qty'];
                            if ($balanceQty == 0) {
                                $newStatusArr->push('Cancelled');
                            } else {
                                $newStatusArr->push($newQtyServed < $newSubItemServeQty ? 'Pending Serve' : 'Served');
                            }
        
                            $subItem->update(['serve_qty' => $newQtyServed]);
                        });
        
                        if ($newStatusArr->contains('Cancelled')) {
                            $newStatus = 'Cancelled';
                        } elseif ($newStatusArr->count() === 1 && in_array($newStatusArr->first(), ['Pending Serve', 'Served'])) {
                            $newStatus = $newStatusArr->first();
                        } elseif ($newStatusArr->count() === 2 && $newStatusArr->contains('Pending Serve') && $newStatusArr->contains('Served')) {
                            $newStatus = 'Pending Serve';
                        }
                    
                        $orderItem->update([
                            'item_qty' => $balanceQty,
                            'amount_before_discount' => round($amountBeforeDiscountPerUnit * $balanceQty, 2),
                            'discount_id' => $item['discount_id'],
                            'discount_amount' => round($discountPerUnit * $balanceQty, 2),
                            'amount' => round($amountPerUnit * $balanceQty, 2),
                            'status' => $newStatus,
                        ]);
                    }
                });
            
                $targetTables = $request->targetTables;
                
                // Target tables
                foreach ($targetTables as $key => $targetTable) {
                    $totalTargetOrderAmount = 0.00;
                    $totalTargetOrderDiscountedAmount = 0.00;
        
                    $filteredTargetTableItems = collect($targetTable['order_items'])->filter(fn ($item) => $item['balance_qty'] > 0);
        
                    $newOrder = Order::create([
                        'order_no' => RunningNumberService::getID('order'),
                        'pax' => $currentTable['pax'],
                        'user_id' => $request->user()->id,
                        'customer_id' => $targetTable['new_customer'] ? $targetTable['new_customer']['id'] : null,
                        'amount' => 0.00,
                        'total_amount' => 0.00,
                        'status' => 'Pending Serve',
                    ]);
        
                    foreach ($targetTable['order_tables'] as $targetOrderTables) {
                        $existingOrderTable = OrderTable::find($targetOrderTables['id']);
                        if ($targetOrderTables['status'] === 'Pending Clearance') {
                            $existingOrderTable->update(['status' => 'Order Completed']);
                        } else {
                            $existingOrderTable->update(['order_id' => $newOrder->id]);
                        }
                    }
        
                    $filteredTargetTableItems->each(function ($item) use (&$totalCurrentOrderAmount, 
                        &$totalCurrentOrderDiscountedAmount, 
                        &$totalTargetOrderAmount, 
                        &$totalTargetOrderDiscountedAmount, 
                        &$newOrder) 
                    {
                        $balanceQty = $item['balance_qty'];
                        $originalQty = $item['original_qty'];
                        $orderItem = OrderItem::with('product')->find($item['id']);

                        // $balancePercentage = round($balanceQty / $originalQty * 100, 2);
                        // Calculate per-unit amounts
                        $amountBeforeDiscountPerUnit = $orderItem->product->price;
                        $currentProductDiscount = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first();
                        $amountPerUnit = round($currentProductDiscount ? $currentProductDiscount['price_after'] : $amountBeforeDiscountPerUnit, 2);
                        $discountPerUnit = $amountBeforeDiscountPerUnit - $amountPerUnit;
        
                        if ($balanceQty === $originalQty) {
                            $orderItem->update(['order_id' => $newOrder->id]);
                            
                            $totalTargetOrderAmount += $orderItem->amount;
                            $totalTargetOrderDiscountedAmount += $orderItem->discount_amount;
        
                        } else {
                            $newOrderItem = OrderItem::create([
                                'order_id' => $newOrder->id,
                                'user_id' => $item['user_id'],
                                'type' => $item['type'],
                                'product_id' => $item['product_id'],
                                'item_qty' => $balanceQty,
                                'amount_before_discount' => round($amountBeforeDiscountPerUnit * $balanceQty, 2),
                                'discount_id' => $item['discount_id'],
                                'discount_amount' => round($discountPerUnit * $balanceQty, 2),
                                'amount' => round($amountPerUnit * $balanceQty, 2),
                                'status' => $item['status']
                            ]);
                            
                            $totalTargetOrderAmount += round($amountPerUnit * $balanceQty, 2);
                            $totalTargetOrderDiscountedAmount += round($discountPerUnit * $balanceQty, 2);
        
                            $newStatusArr = collect();
                            $orderItem->subItems->each(function ($subItem) use (&$balanceQty, &$newOrderItem, &$newStatusArr) {
                                $newSubItemServeQty = $balanceQty * $subItem['item_qty'];
                                // $newQtyServed = $newSubItemServeQty < $subItem['serve_qty'] ? $newSubItemServeQty : $subItem['serve_qty'];
        
                                if ($balanceQty > 0) {
                                    $newStatusArr->push('Served');
                                    // $newStatusArr->push($newQtyServed < $newSubItemServeQty ? 'Pending Serve' : 'Served');
                                }
        
                                OrderItemSubitem::create([
                                    'order_item_id' => $newOrderItem->id,
                                    'product_item_id' => $subItem['product_item_id'],
                                    'item_qty' => $subItem['item_qty'],
                                    'serve_qty' => $newSubItemServeQty,
                                ]);
        
                                // $subItem->decrement('serve_qty', $newQtyServed);
                            });
        
                            if ($newStatusArr->contains('Pending Serve')) {
                                $newStatus = 'Pending Serve';
                            } elseif ($newStatusArr->count() === 1 && in_array($newStatusArr->first(), ['Pending Serve', 'Served'])) {
                                $newStatus = $newStatusArr->first();
                            } elseif ($newStatusArr->count() === 2 && $newStatusArr->contains('Pending Serve') && $newStatusArr->contains('Served')) {
                                $newStatus = 'Pending Serve';
                            }
                        
                            $newOrderItem->update(['status' => $newStatus]);
                        }
                    });
                
                    if ($newOrder) {
                        $statusArr = collect($newOrder->orderItems->pluck('status')->unique());
                        $orderStatus = 'Pending Serve';
                        $orderTableStatus = 'Pending Order';
                    
                        if ($statusArr->contains('Pending Serve')) {
                            $orderStatus = 'Pending Serve';
                            $orderTableStatus = 'Order Placed';
                        } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
                            $orderStatus = 'Order Served';
                            $orderTableStatus = 'All Order Served';
                        } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
                            $orderStatus = 'Order Served';
                            $orderTableStatus = 'All Order Served';
                        }
                        
                        $newOrder->update([
                            'amount' => $newOrder->amount + $totalTargetOrderAmount,
                            'total_amount' => $newOrder->amount + $totalTargetOrderAmount,
                            'discount_amount' => $newOrder->discount_amount + $totalTargetOrderDiscountedAmount,
                            'status' => $orderStatus
                        ]);
                        
                        // Update all tables associated with this order
                        $newOrder->orderTable->each(function ($tab) use ($orderTableStatus) {
                            $tab->table->update([
                                'status' => $orderTableStatus,
                                'order_id' => $tab->order_id,
                                'is_locked' => false,
                            ]);
                            $tab->update(['status' => $orderTableStatus]);
                        });
                    }
                };
                
                $currentOrder = Order::with('orderTable.table')->find($currentTable['order_id']);
                if ($currentOrder) {
                    $currentStatusArr = collect($currentOrder->orderItems->pluck('status')->unique());
                    $currentOrderStatus = 'Pending Serve';
                    $currentOrderTableStatus = 'Pending Order';
                
                    if ($currentStatusArr->contains('Pending Serve')) {
                        $currentOrderStatus = 'Pending Serve';
                        $currentOrderTableStatus = 'Order Placed';
                    } elseif ($currentStatusArr->count() === 1 && in_array($currentStatusArr->first(), ['Served', 'Cancelled'])) {
                        $currentOrderStatus = 'Order Served';
                        $currentOrderTableStatus = 'All Order Served';
                    } elseif ($currentStatusArr->count() === 2 && $currentStatusArr->contains('Served') && $currentStatusArr->contains('Cancelled')) {
                        $currentOrderStatus = 'Order Served';
                        $currentOrderTableStatus = 'All Order Served';
                    }
                    
                    $currentOrder->update([
                        'amount' => $currentOrder->amount + $totalCurrentOrderAmount,
                        'total_amount' => $currentOrder->amount + $totalCurrentOrderAmount,
                        'discount_amount' => $currentOrder->discount_amount + $totalCurrentOrderDiscountedAmount,
                        'status' => $currentOrderStatus
                    ]);
                    
                    // Update all tables associated with this order
                    $currentOrder->orderTable->each(function ($tab) use ($currentOrderTableStatus) {
                        $tab->table->update(['status' => $currentOrderTableStatus]);
                        $tab->update(['status' => $currentOrderTableStatus]);
                    });
                }
            }

            return redirect()->back();
        });
    }

    private function getAllCustomers() {
        $users = Customer::where(function ($query) {
                            $query->where('status', '!=', 'void')
                                    ->orWhereNull('status'); // Handle NULL cases
                        })->get();

        foreach($users as $user){
            $user->image = $user->getFirstMediaUrl('customer');
        }

        return $users;
    }

    public function getAllCustomer(){
        $users = $this->getAllCustomers();

        return response()->json($users);
    }

    public function createCustomerFromOrder(CustomerRequest $request)
    {
        $validatedData = $request->validated();

        $defaultRank = Ranking::where('name', 'Member')->first(['id', 'name']);

        Customer::create([
            'uuid' => RunningNumberService::getID('customer'),
            'full_name' => $validatedData['full_name'],
            'dial_code' => '+60',
            'phone' => $validatedData['phone'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'ranking' => $defaultRank->id,
            'point' => 0,
            'total_spending' => 0.00,
            'first_login' => '0',
            'status' => 'verified',
        ]);

        $customers = Customer::orderBy('full_name')
                                ->get(['id', 'full_name', 'phone'])
                                ->map(function ($customer) {
                                    $customer->image = $customer->getFirstMediaUrl('customer');

                                    return $customer;
                                });
       

        return response()->json($customers);
    }

    // Transfer selected order items between tables
    public function transferTable(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $currentTable = $request->currentTable;
            $targetTable = $request->targetTable;

            $currentTableItems = $request->currentTable['order_items'];
            $targetTableItems = $request->targetTable['order_items'];
            $filteredCurrentTableItems = collect($currentTableItems)->filter(fn ($item) => $item['transfer_status'] === 'transferred' || $item['transfer_qty'] !== $item['original_qty']);
            $filteredTargetTableItems = collect($targetTableItems)->filter(fn ($item) => $item['transfer_status'] === 'transferred' || $item['transfer_qty'] !== $item['original_qty']);

            $totalCurrentOrderAmount = 0.00;
            $totalCurrentOrderDiscountedAmount = 0.00;
            $totalTargetOrderAmount = 0.00;
            $totalTargetOrderDiscountedAmount = 0.00;

            $filteredCurrentTableItems->each(function ($item) use (&$totalCurrentOrderAmount, 
                &$totalCurrentOrderDiscountedAmount, 
                &$totalTargetOrderAmount, 
                &$totalTargetOrderDiscountedAmount, 
                &$currentTable,
                $request) 
            {
                $balanceQty = $item['balance_qty'];
                $originalQty = $item['original_qty'];
                $remainingQty = $originalQty - $balanceQty;
                $orderItem = OrderItem::with('product')->find($item['id']);

                // $transferPercentage = round($balanceQty / $originalQty, 2);
                // $balancePercentage = round(($originalQty - $balanceQty) / $originalQty, 2);
                // Calculate per-unit amounts
                $amountBeforeDiscountPerUnit = $orderItem->product->price;
                $currentProductDiscount = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first();
                $amountPerUnit = round($currentProductDiscount ? $currentProductDiscount['price_after'] : $amountBeforeDiscountPerUnit, 2);
                $discountPerUnit = $amountBeforeDiscountPerUnit - $amountPerUnit;

                if (in_array($currentTable['status'], ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided']) || $currentTable['status'] === 'Empty Seat') {
                    $newOrder = Order::create([
                        'order_no' => RunningNumberService::getID('order'),
                        'pax' => $currentTable['pax'],
                        'user_id' => $request->user()->id,
                        'customer_id' => $currentTable['customer_id'] ?? null,
                        'amount' => 0.00,
                        'total_amount' => 0.00,
                        'status' => 'Pending Serve',
                    ]);

                    $currentTable['order_id'] = $newOrder->id;
            
                    foreach ($currentTable['tables'] as $selectedTable) {
                        $table = Table::find($selectedTable['id']);
                        $table->update([
                            'status' => 'Pending Order',
                            'order_id' => $newOrder->id
                        ]);
                
                        OrderTable::create([
                            'table_id' => $selectedTable['id'],
                            'pax' => $currentTable['pax'],
                            'user_id' => $request->user()->id,
                            'status' => 'Pending Order',
                            'order_id' => $newOrder->id
                        ]);
                    }
                }

                if ($item['order_id'] !== $currentTable['order_id']) {
                    if ($balanceQty === $originalQty) {
                        $orderItem->update(['order_id' => $currentTable['order_id']]);
                        
                        $totalCurrentOrderAmount += $orderItem->amount;
                        $totalCurrentOrderDiscountedAmount += $orderItem->discount_amount;
                        $totalTargetOrderAmount -= $orderItem->amount;
                        $totalTargetOrderDiscountedAmount -= $orderItem->discount_amount;

                    } else {
                        $newOrderItem = OrderItem::create([
                            'order_id' => $currentTable['order_id'],
                            'user_id' => $item['user_id'],
                            'type' => $item['type'],
                            'product_id' => $item['product_id'],
                            'item_qty' => $balanceQty,
                            'amount_before_discount' => $amountBeforeDiscountPerUnit * $balanceQty,
                            'discount_id' => $item['discount_id'],
                            'discount_amount' => $discountPerUnit * $balanceQty,
                            'amount' => $amountPerUnit * $balanceQty,
                            'status' => $item['status']
                        ]);
                        
                        $totalCurrentOrderAmount += $amountPerUnit * $balanceQty;
                        $totalCurrentOrderDiscountedAmount += $discountPerUnit * $balanceQty;

                        $orderItem->update([
                            'item_qty' => $originalQty - $balanceQty,
                            'amount_before_discount' => $amountBeforeDiscountPerUnit * $remainingQty,
                            'discount_id' => $item['discount_id'],
                            'discount_amount' => $discountPerUnit * $remainingQty,
                            'amount' => $amountPerUnit * $remainingQty,
                        ]);

                        // $totalTargetOrderAmount -= $amountPerUnit * $remainingQty;
                        // $totalTargetOrderDiscountedAmount -= $discountPerUnit * $remainingQty;

                        $orderItem->subItems->each(function ($subItem) use (&$originalQty, &$balanceQty, &$newOrderItem) {
                            $newSubItemServeQty = $balanceQty * $subItem['item_qty'];
                            $newQtyServed = $newSubItemServeQty < $subItem['serve_qty'] ? $newSubItemServeQty : $subItem['serve_qty'];

                            OrderItemSubitem::create([
                                'order_item_id' => $newOrderItem->id,
                                'product_item_id' => $subItem['product_item_id'],
                                'item_qty' => $subItem['item_qty'],
                                'serve_qty' => $newQtyServed,
                            ]);

                            // $subItem->decrement('serve_qty', $newQtyServed);
                        });
                    }

                } else {
                    if ($balanceQty !== $originalQty) {
                        $orderItem->update([
                            'item_qty' => $originalQty - $balanceQty,
                            'amount_before_discount' => $amountBeforeDiscountPerUnit * $remainingQty,
                            'discount_id' => $item['discount_id'],
                            'discount_amount' => $discountPerUnit * $remainingQty,
                            'amount' => $amountPerUnit * $remainingQty,
                        ]);

                        $totalCurrentOrderAmount -= $amountPerUnit * $remainingQty;
                        $totalCurrentOrderDiscountedAmount -= $discountPerUnit * $remainingQty;

                        $orderItem->subItems->each(function ($subItem) use (&$originalQty, &$balanceQty, &$newOrderItem) {
                            $newSubItemServeQty = ($originalQty - $balanceQty) * $subItem['item_qty'];
                            $newQtyServed = $newSubItemServeQty < $subItem['serve_qty'] ? $newSubItemServeQty : $subItem['serve_qty'];

                            $subItem->decrement('serve_qty', (int)$newQtyServed);
                        });
                    }
                }
            });
        
            $filteredTargetTableItems->each(function ($item) use (&$totalCurrentOrderAmount, 
                &$totalCurrentOrderDiscountedAmount, 
                &$totalTargetOrderAmount, 
                &$totalTargetOrderDiscountedAmount, 
                &$targetTable,
                $request) 
            {
                $balanceQty = $item['balance_qty'];
                $originalQty = $item['original_qty'];
                $remainingQty = $originalQty - $balanceQty;
                $orderItem = OrderItem::with('product')->find($item['id']);

                // $transferPercentage = round($balanceQty / $originalQty, 2);
                // $balancePercentage = round(($originalQty - $balanceQty) / $originalQty, 2);
                // Calculate per-unit amounts
                $amountBeforeDiscountPerUnit = $orderItem->product->price;
                $currentProductDiscount = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first();
                $amountPerUnit = round($currentProductDiscount ? $currentProductDiscount['price_after'] : $amountBeforeDiscountPerUnit, 2);
                $discountPerUnit = $amountBeforeDiscountPerUnit - $amountPerUnit;

                if (in_array($targetTable['status'], ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided']) || $targetTable['status'] === 'Empty Seat') {
                    $newOrder = Order::create([
                        'order_no' => RunningNumberService::getID('order'),
                        'pax' => $targetTable['pax'],
                        'user_id' => $request->user()->id,
                        'customer_id' => $targetTable['customer_id'] ?? null,
                        'amount' => 0.00,
                        'total_amount' => 0.00,
                        'status' => 'Pending Serve',
                    ]);

                    $targetTable['order_id'] = $newOrder->id;
            
                    foreach ($targetTable['tables'] as $selectedTable) {
                        $table = Table::find($selectedTable['id']);
                        $table->update([
                            'status' => 'Pending Order',
                            'order_id' => $newOrder->id
                        ]);
                
                        OrderTable::create([
                            'table_id' => $selectedTable['id'],
                            'pax' => $targetTable['pax'],
                            'user_id' => $request->user()->id,
                            'status' => 'Pending Order',
                            'order_id' => $newOrder->id
                        ]);
                    }
                }

                if ($item['order_id'] !== $targetTable['order_id']) {
                    if ($balanceQty === $originalQty) {
                        $orderItem->update(['order_id' => $targetTable['order_id']]);
                        
                        $totalTargetOrderAmount += $orderItem->amount;
                        $totalTargetOrderDiscountedAmount += $orderItem->discount_amount;
                        $totalCurrentOrderAmount -= $orderItem->amount;
                        $totalCurrentOrderDiscountedAmount -= $orderItem->discount_amount;

                    } else {
                        $newOrderItem = OrderItem::create([
                            'order_id' => $targetTable['order_id'],
                            'user_id' => $item['user_id'],
                            'type' => $item['type'],
                            'product_id' => $item['product_id'],
                            'item_qty' => $balanceQty,
                            'amount_before_discount' => $amountBeforeDiscountPerUnit * $balanceQty,
                            'discount_id' => $item['discount_id'],
                            'discount_amount' => $discountPerUnit * $balanceQty,
                            'amount' => $amountPerUnit * $balanceQty,
                            'status' => $item['status']
                        ]);
                        
                        $totalTargetOrderAmount += $amountPerUnit * $balanceQty;
                        $totalTargetOrderDiscountedAmount += $discountPerUnit * $balanceQty;

                        $orderItem->update([
                            'item_qty' => $originalQty - $balanceQty,
                            'amount_before_discount' => $amountBeforeDiscountPerUnit * $remainingQty,
                            'discount_id' => $item['discount_id'],
                            'discount_amount' => $discountPerUnit * $remainingQty,
                            'amount' => $amountPerUnit * $remainingQty,
                        ]);

                        // $totalCurrentOrderAmount -= $amountPerUnit * $remainingQty;
                        // $totalCurrentOrderDiscountedAmount -= $discountPerUnit * $remainingQty;

                        $orderItem->subItems->each(function ($subItem) use (&$balanceQty, &$newOrderItem) {
                            $newSubItemServeQty = $balanceQty * $subItem['item_qty'];
                            // $newQtyServed = $newSubItemServeQty < $subItem['serve_qty'] ? $newSubItemServeQty : $subItem['serve_qty'];

                            OrderItemSubitem::create([
                                'order_item_id' => $newOrderItem->id,
                                'product_item_id' => $subItem['product_item_id'],
                                'item_qty' => $subItem['item_qty'],
                                'serve_qty' => $newSubItemServeQty,
                            ]);

                            // $subItem->decrement('serve_qty', $newQtyServed);
                        });
                    }

                } else {
                    if ($balanceQty !== $originalQty) {
                        $orderItem->update([
                            'item_qty' => $originalQty - $balanceQty,
                            'amount_before_discount' => $amountBeforeDiscountPerUnit * $remainingQty,
                            'discount_id' => $item['discount_id'],
                            'discount_amount' => $discountPerUnit * $remainingQty,
                            'amount' => $amountPerUnit * $remainingQty,
                        ]);

                        $totalTargetOrderAmount -= $amountPerUnit * $remainingQty;
                        $totalTargetOrderDiscountedAmount -= $discountPerUnit * $remainingQty;

                        $orderItem->subItems->each(function ($subItem) use (&$originalQty, &$balanceQty, &$newOrderItem) {
                            $newSubItemServeQty = ($originalQty - $balanceQty) * $subItem['item_qty'];
                            $newQtyServed = $newSubItemServeQty < $subItem['serve_qty'] ? $newSubItemServeQty : $subItem['serve_qty'];

                            // $subItem->decrement('serve_qty', (int)$newQtyServed);
                        });
                    }
                }
            });
            
            $currentOrder = Order::with([
                                    'orderTable' => fn ($query) => $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided', 'Order Merged']), 
                                    'orderTable.table'])
                                ->find($currentTable['order_id']);
            $targetOrder = Order::with([
                                    'orderTable' => fn ($query) => $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided', 'Order Merged']), 
                                    'orderTable.table'])
                                ->find($targetTable['order_id']);

            if ($currentOrder) {
                $statusArr = collect($currentOrder->orderItems->pluck('status')->unique());
                $orderStatus = 'Pending Serve';
                $orderTableStatus = 'Pending Order';
            
                if ($statusArr->contains('Pending Serve')) {
                    $orderStatus = 'Pending Serve';
                    $orderTableStatus = 'Order Placed';
                } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
                    $orderStatus = 'Order Served';
                    $orderTableStatus = 'All Order Served';
                } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
                    $orderStatus = 'Order Served';
                    $orderTableStatus = 'All Order Served';
                }
                
                $currentOrder->update([
                    'amount' => $currentOrder->amount + $totalCurrentOrderAmount,
                    'total_amount' => $currentOrder->amount + $totalCurrentOrderAmount,
                    'discount_amount' => $currentOrder->discount_amount + $totalCurrentOrderDiscountedAmount,
                    'status' => $orderStatus
                ]);
                
                // Update all tables associated with this order
                $currentOrder->orderTable->each(function ($tab) use ($orderTableStatus) {
                    $tab->table->update(['status' => $orderTableStatus]);
                    $tab->update(['status' => $orderTableStatus]);
                });
            }
            
            if ($targetOrder) {
                $statusArr = collect($targetOrder->orderItems->pluck('status')->unique());
                $orderStatus = 'Pending Serve';
                $orderTableStatus = 'Pending Order';
            
                if ($statusArr->contains('Pending Serve')) {
                    $orderStatus = 'Pending Serve';
                    $orderTableStatus = 'Order Placed';
                } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
                    $orderStatus = 'Order Served';
                    $orderTableStatus = 'All Order Served';
                } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
                    $orderStatus = 'Order Served';
                    $orderTableStatus = 'All Order Served';
                }
                
                $targetOrder->update([
                    'amount' => $targetOrder->amount + $totalTargetOrderAmount,
                    'total_amount' => $targetOrder->amount + $totalTargetOrderAmount,
                    'discount_amount' => $targetOrder->discount_amount + $totalTargetOrderDiscountedAmount,
                    'status' => $orderStatus
                ]);
                
                // Update all tables associated with this order
                $targetOrder->orderTable->each(function ($tab) use ($orderTableStatus) {
                    $tab->table->update(['status' => $orderTableStatus]);
                    $tab->update(['status' => $orderTableStatus]);
                });
            }

            return redirect()->back();
        });
    }

    // Direct transfer all items of the order to the target table and void order
    public function transferTableOrder(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $currentTable = $request->current_table;
            $currentMatchedTables = $request->current_matched_tables;
            $targetTable = $request->target_table;
            $targetMatchedTables = $request->target_matched_tables;
            $targetTableHasOrder = !!$targetTable['order_id'];
            $tables = collect([$currentTable, $targetTable]);

            $filteredTables = $tables->filter(fn ($table) => $table['order_id']);
            // $hasMultipleOrderId = $filteredTables->unique('order_id')->count() > 1;

            if ($filteredTables->unique('order_id')->count() == 1) {
                $currentTableFiltered = $filteredTables->first(fn ($table) => $table['order_id']);
                $currentOrderId = $currentTableFiltered['order_id'];
            }

            // Get the latest order tables and calculate totals in a single pass
            $totalPax = 0;
            $totalAmount = 0;
            $statusArr = collect();
            $temp = collect();

            foreach ($filteredTables->unique('order_id') as $table) {
                if (empty($table['order_tables'])) {
                    continue;
                }

                // Find the latest order_table without creating intermediate collections
                $latestOrderTable = null;
                $latestTimestamp = null;
                
                foreach ($table['order_tables'] as $orderTable) {
                    $timestamp = $orderTable['created_at'] ?? 0;
                    if ($latestTimestamp === null || $timestamp > $latestTimestamp) {
                        $latestTimestamp = $timestamp;
                        $latestOrderTable = $orderTable;
                    }
                }
                
                if ($latestOrderTable && !in_array($latestOrderTable['status'], ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided'])) {
                    $temp->push($latestOrderTable);
                    $totalPax += (int)($latestOrderTable['pax'] ?? 0);
                    $totalAmount += (float)($latestOrderTable['order']['total_amount'] ?? 0);

                    $statusArr->push(...collect($latestOrderTable['order']['order_items'])->pluck('status')->unique());
                }
            }

            $orderStatus = 'Pending Serve';
            $orderTableStatus = 'Pending Order';
        
            if ($statusArr->contains('Pending Serve')) {
                $orderStatus = 'Pending Serve';
                $orderTableStatus = 'Order Placed';
            } elseif ($statusArr->count() === 1 && in_array($statusArr->first(), ['Served', 'Cancelled'])) {
                $orderStatus = 'Order Served';
                $orderTableStatus = 'All Order Served';
            } elseif ($statusArr->count() === 2 && $statusArr->contains('Served') && $statusArr->contains('Cancelled')) {
                $orderStatus = 'Order Served';
                $orderTableStatus = 'All Order Served';
            }

            // dd($temp, $statusArr, $orderStatus, $orderTableStatus);

            //step 1: create new order
            if ($targetTableHasOrder && !in_array($targetTable['status'], ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided'])) {
                $targetOrder = Order::find($targetTable['order_id']);
                $targetOrder->update([
                    'amount' => $totalAmount,
                    'total_amount' => $totalAmount,
                    'status' => $orderStatus,
                ]);
            
                $currentOrder = Order::with([
                                        'orderItems',
                                        'orderTable' => fn ($query) => $query->where('status' , 'Pending Clearance'),
                                        'customer:id',
                                        'customer.rewards:id,customer_id,ranking_reward_id,status',
                                    ])
                                    ->find($currentTable['order_id']);

                if ($currentOrder) {
                    $currentOrder->update(['status' => 'Order Cancelled']);
                    $currentOrder->orderItems->each(function ($item) use ($targetTable) {
                        $orderItem = OrderItem::find($item['id']);
                        $orderItem->update(['order_id' => $targetTable['order_id']]);
                        $orderItem->refresh();
                    });

                    if ($currentOrder->customer && $currentOrder->voucher) {
                        $this->removeOrderVoucher($currentOrder);
                    }
                };

                foreach ($currentMatchedTables as $key => $table) {
                    $currentMatchedTable = Table::find($table['id']);
                    $currentMatchedTable->update([
                        'order_id' => null,
                        'status' => 'Empty Seat',
                        'is_locked' => false
                    ]);
                    
                    foreach ($table['order_tables'] as $orderTable) {
                        $currentMatchedOrderTable = OrderTable::find($orderTable['id']);
                        
                        $matchedOrderTableStatus = $currentMatchedOrderTable->status === 'Pending Clearance'
                                ? $currentMatchedOrderTable['order']['status']
                                : 'Order Cancelled';
                        
                        $currentMatchedOrderTable->update(['status' => $matchedOrderTableStatus]);
                        $currentMatchedOrderTable->save();
                    }
                }


            } else {
                foreach ($targetMatchedTables as $key => $table) {
                    $targetMatchedTable = Table::find($table['id']);
                    $targetMatchedTable->update([
                        'order_id' => $currentTable['order_id'],
                        'status' => $orderTableStatus
                    ]);
                    
                    OrderTable::create([
                        'table_id' => $table['id'],
                        'pax' => $totalPax,
                        'user_id' => auth()->user()->id,
                        'status' => $orderTableStatus,
                        'order_id' => $currentTable['order_id'],            
                    ]);
                }

                foreach ($currentMatchedTables as $key => $table) {
                    $currentMatchedTable = Table::find($table['id']);
                    $currentMatchedTable->update([
                        'order_id' => null,
                        'status' => 'Empty Seat',
                        'is_locked' => false
                    ]);
                    
                    foreach ($table['order_tables'] as $orderTable) {
                        $currentMatchedOrderTable = OrderTable::find($orderTable['id']);
                        
                        $matchedOrderTableStatus = $currentMatchedOrderTable->status === 'Pending Clearance'
                                ? $currentMatchedOrderTable['order']['status']
                                : 'Order Cancelled';
                        
                        $currentMatchedOrderTable->update([
                            'status' => $matchedOrderTableStatus,
                        ]);
                        $currentMatchedOrderTable->save();
                    }
                }
            }
        });
    }

    public function getBillDiscount(Request $request)
    {
        $billDiscounts = BillDiscount::where('status', 'active')
                                        ->orderByDesc('id')
                                        ->with(['billDiscountUsages' => function($query) use ($request) {
                                            $query->where('customer_id', $request->current_customer_id);
                                        }])
                                        ->withCount(['billDiscountUsages as current_total_usage_count' => function($query) {
                                            $query->select(DB::raw('COALESCE(SUM(customer_usage), 0)'));
                                        }])        
                                        ->get()
                                        ->map(function ($discount) {
                                            // Since there's at most one usage per customer, we can use first()
                                            $discount['current_customer_usage'] = $discount->billDiscountUsages->first() ?? null;
                                            unset($discount->billDiscountUsages);
                                            
                                            return $discount;
                                        });

        return response()->json($billDiscounts);
    }

    public function getAutoAppliedDiscounts(Request $request, string $id)
    {
        $billDiscounts = BillDiscount::where([
                                            ['status', 'active'],
                                            ['is_auto_applied', 1]
                                        ])
                                        ->orderByDesc('id')
                                        ->with(['billDiscountUsages' => function($query) use ($request) {
                                            $query->where('customer_id', $request->current_customer_id);
                                        }])
                                        ->withCount(['billDiscountUsages as current_total_usage_count' => function($query) {
                                            $query->select(DB::raw('COALESCE(SUM(customer_usage), 0)'));
                                        }])        
                                        ->get()
                                        ->map(function ($discount) {
                                            // Since there's at most one usage per customer, we can use first()
                                            $discount['current_customer_usage'] = $discount->billDiscountUsages->first() ?? null;
                                            unset($discount->billDiscountUsages);
                                            
                                            return $discount;
                                        });

        $currentAppliedVoucher = RankingReward::select(['id','ranking_id','reward_type','min_purchase','discount','min_purchase_amount','bonus_point','free_item','item_qty','updated_at'])
                                                ->with([
                                                    'ranking:id,name',
                                                    'product:id,product_name,availability',
                                                    'product.productItems'
                                                ])
                                                ->find($id);

        return response()->json($billDiscounts);
    }

    public function handleTableUnlockOnly(Request $request)
    {
        // Log::info('UNLOCK route hit');

        // ✅ Don't do any Eloquent for now. Just confirm log shows.

        // return response()->noContent();
        // $tableId = $data['id'];

        // $orderTables = OrderTable::select('id', 'table_id', 'status', 'updated_at', 'order_id')
        //                             ->with('table:id,table_no,status')
        //                             ->where('table_id', $tableId)
        //                             ->orderByDesc('updated_at')
        //                             ->get();

        // // Find the first non-pending clearance table
        // $currentOrderTable = $orderTables->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided'])
        //                                     ->firstWhere('status', '!=', 'Pending Clearance') 
        //                     ?? $orderTables->first();
                            
        // if ($currentOrderTable) {
        //     // Lazy load relationships only for the selected table
        //     $currentOrderTable->load([
        //         'order.orderTable' => function ($query) {
        //             $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided'])
        //                     ->select('id', 'order_id', 'table_id', 'status');
        //         },
        //         'order.orderTable.table:id,table_no',
        //     ]);

        // }

        // $tableIdArray = $currentOrderTable->order->orderTable->map(fn ($ot) => $ot['table']['id'])->toArray();

        // $tables = Table::whereIn('id', $tableIdArray)->get();

        // $tables->each(fn ($table) => 
        //     $table->update(['is_locked' => false])
        // );
        // dd([
        //     'data' => $data,
        //     'tableIdArray' => $tableIdArray,
        //     'tables' => $tables,
        // ]);
        // return response()->json([
        //     'data' => $data,
        //     'tableIdArray' => $tableIdArray,
        //     'tables' => $tables,
        // ]);

    }
    
    public function handleTableLock(Request $request)
    {
        $action = $request->action;

        if (in_array($action, ['lock', 'unlock'])) {
            Table::whereIn('id', $request->table_id_array)
                    ->update([
                        'is_locked' => $action === 'lock',
                        'locked_by' => $action === 'lock' ? auth()->user()->id : null,
                    ]);

            return redirect()->back();
        }

        if ($action === 'unlock-all') {
            Table::where('is_locked', true)
                    ->update([
                        'is_locked' => false,
                        'locked_by' => null,
                    ]);
            
            return redirect()->back();
        }

        return redirect()->back()->withErrors('Error handling table lock state');
    }
    
    public function getExpiringPointHistories(string $id)
    {
        $expiringNotificationTimer = Setting::where([
                                                ['name', 'Point Expiration Notification'],
                                                ['type', 'expiration']
                                            ])
                                            ->first(['id', 'value']);

        $expiringPointHistories = PointHistory::where('customer_id', $id)
                                                ->where(function ($query) use ($expiringNotificationTimer) {
                                                    $query->where(function ($subQuery) use ($expiringNotificationTimer) {
                                                            $subQuery->where([
                                                                        ['type', 'Earned'],
                                                                        ['expire_balance', '>', 0],
                                                                    ])
                                                                    ->whereNotNull('expired_at')
                                                                    ->whereDate('expired_at', '>', now())
                                                                    ->whereDate('expired_at', '<', now()->addDays((int)$expiringNotificationTimer->value));
                                                        })
                                                        ->orWhere(function ($subQuery) use ($expiringNotificationTimer) {
                                                            $subQuery->where([
                                                                        ['type', 'Adjusted'],
                                                                        ['expire_balance', '>', 0]
                                                                    ])
                                                                    ->whereNotNull('expired_at')
                                                                    ->whereColumn('new_balance', '>', 'old_balance')
                                                                    ->whereDate('expired_at', '>', now())
                                                                    ->whereDate('expired_at', '<', now()->addDays((int)$expiringNotificationTimer->value));
                                                        });
                                                })
                                                ->get(['id', 'expire_balance', 'expired_at']);

        return response()->json($expiringPointHistories);
    }

    public function printReceipt(Request $request)
    {
        // 1. Extract base64 data
        // $base64Image = $request->input('image');
        // $image = str_replace('data:image/jpeg;base64,', '', $base64Image);
        // $image = str_replace(' ', '+', $image);
        // $imageData = base64_decode($image);

        // // Define the public path
        // $filename = 'receipt.jpeg';
        // $publicPath = public_path('order');
        // $fullPath = "$publicPath/$filename";

        // // Save the file
        // file_put_contents($fullPath, $imageData);

        // dd(extension_loaded('imagick'));
        // 1. Use normalized path
        // $inputPath = public_path('order/tux.png');
        // $outputPath = public_path('order/tuxd.jpg');
        
        // // 1. Open the original image (with transparency)
        // $src = imagecreatefrompng($inputPath);
        // if (!$src) {
        //     throw new \Exception("Failed to open PNG.");
        // }

        // // 2. Create a truecolor image with white background
        // $width = imagesx($src);
        // $height = imagesy($src);
        // $dst = imagecreatetruecolor($width, $height);
        // $white = imagecolorallocate($dst, 255, 255, 255);
        // imagefill($dst, 0, 0, $white);

        // // 3. Copy and flatten original onto new image
        // imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);

        // // 4. Save to a new file (flattened)
        // imagejpeg($dst, $outputPath);

        // // 5. Clean up
        // imagedestroy($src);
        // imagedestroy($dst);
        
        // dd(file_exists(public_path('order/tuxd.png')), is_readable(public_path('order/tuxd.png')), getimagesize(public_path('order/tuxd.png')), public_path('order/tuxd.png'));
        
        $order = $request->order;
        $merchant = $request->merchant;
        $url = $request->url;
        $has_voucher_applied = $request->has_voucher_applied;
        $applied_discounts = $request->applied_discounts;
        $table_names = $request->table_names;

        try {
            // $img = EscposImage::load($outputPath, false);
            // $connector = new NetworkPrintConnector("192.168.0.77", 9100);
            // $printer = new Printer($connector);

            // dd($img);
            // $printer->graphics($img); // or $printer->graphics($img); for better quality
            // $printer->text("Carlsberg event.\n");
            // $printer->feed();
            // $printer->cut();
            // $printer->close();
            
            $connector = new NetworkPrintConnector("192.168.0.77", 9100);
            $printer = new Printer($connector);

            // Set condensed font for 48-character width (Font B)
            $printer->setFont(Printer::FONT_B);
            
            // ===== HEADER SECTION =====
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer->setEmphasis(true);
            $printer->text($merchant['merchant_name'] . "\n");
            $printer->selectPrintMode();
            $printer->setEmphasis(false);
            $printer->text($this->formatAddress($merchant['merchant_address_line1'], $merchant['merchant_address_line2']) . "\n");
            $printer->text("Phone: " . $merchant['merchant_contact'] . "\n");
            $printer->feed();
            
            // Amount and date
            $printer->setEmphasis(true);
            $printer->text("INVOICE\n");
            $printer->setEmphasis(false);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->feed();
            
            // ===== MEMBER INFO =====
            if ($order['customer']) {
                $customerName = $order['customer']['full_name'];

                $printer->text("$customerName\n");
                $this->printTwoColumns($printer, "Phone No.", $order['customer']['phone'] ? str_replace("+", "", $order['customer']['dial_code']) . $order['customer']['phone'] : '-');
                $this->printTwoColumns($printer, "Email", $order['customer']['email'] ?: '-');
                $this->printTwoColumns($printer, "Tier", $order['customer']['rank']['name']);
                $this->printTwoColumns($printer, "Points Earned", $order['payment']['point_history']['amount'] ?? 0);
                $this->printTwoColumns($printer, "Points Balance", $order['payment']['point_history']['new_balance'] ?? 0);
                $printer->feed();
            }
            
            // ===== RECEIPT INFO =====
            $this->printTwoColumns($printer, "Receipt No.", $order['payment']['receipt_no'] ?? '-');
            $this->printTwoColumns($printer, "Table No.", $table_names);
            $this->printTwoColumns($printer, "Pax", $order['payment']['pax'] ?? '-');
            $this->printTwoColumns($printer, "Date", Carbon::parse($order['created_at'])->format('d/m/Y H:i A'));
            
            // ===== ITEMS TABLE =====
            $filteredOrderItems = collect($order['order_items'])->filter(fn ($item) => $item['status'] === 'Served' && $item['item_qty'] > 0);

            $this->printDivider($printer);
            $this->printThreeColumns($printer, "QTY", "ITEM", "AMT (RM)");
            $this->printDivider($printer);

            foreach ($filteredOrderItems as $key => $item) {
                $itemQty = $item['item_qty'];

                // Item name
                $itemType = $item['type'] !== 'Normal' ? "(" . $item['type'] . ")" : '';
                $itemBucket = $item['product']['bucket'] === 'set' ? '(Set) ': '';
                $itemName = "$itemType$itemBucket" . $item['product']['product_name'];

                if ($item['discount_id']) {
                    // Item price
                    $amountBeforeDiscount = number_format( $item['type'] === 'Normal' ? $item['amount'] : 0, 2);
                    $discountName = $item['product_discount']['discount']['name'] . " Discount";
                    
                    $this->printThreeColumns($printer, $itemQty, $itemName, $amountBeforeDiscount);
                    $this->printThreeColumns($printer, '', $discountName, "- " . $item['discount_amount']);
                    
                } else {
                    // Item price
                    $totalAmount = number_format($item['amount'], 2);
                    
                    $this->printThreeColumns($printer, $itemQty, $itemName, $totalAmount);
                }
            }
            
            $this->printDivider($printer);
            
            // ===== TOTALS SECTION =====
            $this->printTwoColumnsRight($printer, "Subtotal", number_format($order['payment']['total_amount'] ?? 0, 2));

            if ($order['payment']['bill_discounts'] && $order['payment']['bill_discount_total'] > 0) {
                $this->printTwoColumnsRight($printer, "Bill Discount", "- " . number_format($order['payment']['bill_discount_total'] ?? 0, 2));
            }

            if ($order['payment']['voucher']) {
                $voucherDiscountExtTitle = $order['payment']['voucher']['reward_type'] === 'Discount (Percentage)' ? "(" . $order['payment']['voucher']['discount'] . "%)" : '';

                $this->printTwoColumnsRight($printer, "Voucher Discount $voucherDiscountExtTitle", "- " . number_format($order['payment']['discount_amount'] ?? 0, 2));
            }
            
            if ($order['payment']['service_tax_amount'] > 0) {
                $serviceTaxExtTitle = round(($order['payment']['service_tax_amount'] / $order['payment']['total_amount']) * 100);
                $this->printTwoColumnsRight($printer, "Service Tax ($serviceTaxExtTitle%)", number_format($order['payment']['service_tax_amount'] ?? 0, 2));
            }

            if ($order['payment']['sst_amount'] > 0) {
                $sstExtTitle = round(($order['payment']['sst_amount'] / $order['payment']['total_amount']) * 100);
                $this->printTwoColumnsRight($printer, "SST ($sstExtTitle%)", number_format($order['payment']['sst_amount'] ?? 0, 2));
            }
            
            $this->printTwoColumnsRight($printer, "Rounding", ($order['payment']['rounding'] < 0 ? '-' : '') . number_format(abs($order['payment']['rounding'] ?? 0), 2));

            $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
            $printer->setEmphasis(true);
            $this->printTwoColumnsRight($printer, "NET TOTAL", $order['payment']['grand_total'] ?? '0.00', 10, 14);
            $printer->selectPrintMode();
            $printer->setEmphasis(false);

            $this->printDivider($printer);
            $printer->feed();
            
            // ===== DISCOUNT SUMMARY =====
            if ($has_voucher_applied) {
                $printer->text("------------*** DISCOUNT SUMMARY ***------------");
                $this->printWrappedTwoColumnsRight($printer, "DISCOUNTS", "AMT (RM)");

                foreach ($applied_discounts as $key => $discount) {
                    $this->printWrappedTwoColumnsRight($printer, $discount['discount_summary'], "- " . number_format($discount['discount_amount'], 2));

                }
                
                $printer->feed();
            }
            
            // ===== FOOTER SECTION =====
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Scan QR below to request your e-Invoice\n");
            $printer->feed();
            
            // Generate QR code (size 5 works well with 48-char width)
            $printer->qrCode($url, Printer::QR_ECLEVEL_L, 5);
            $printer->feed();
            
            $printer->text("Thank you for your visit!\n");
            $printer->text("Order invoice generated by\nSTOXPOS\n");
            $printer->feed();
            $printer->text("------*** Printed: " . now()->format('d/m/Y H:i A') . " ***------"); // 28 + 6
            $printer->feed(3);
            
            // ===== FINISH PRINTING =====
            $printer->cut(Printer::CUT_FULL);
            $printer->close();

            return response()->json('success');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // ===== HELPER METHODS =====
    private function printDivider(Printer $printer, $length = 48)
    {
        $printer->text(str_repeat("-", $length) . "\n");
    }

    private function printTwoColumns(Printer $printer, $left, $right, $leftWidth = 15, $rightWidth = 33)
    {
        $printer->text(sprintf("%-{$leftWidth}s%-{$rightWidth}s\n", 
            substr($left, 0, $leftWidth), 
            substr(" : $right", 0, $rightWidth)
        ));
    }

    private function printTwoColumnsRight(Printer $printer, $left, $right, $leftWidth = 36, $rightWidth = 12)
    {
        $printer->text(sprintf("%-{$leftWidth}s%{$rightWidth}s\n", 
            substr($left, 0, $leftWidth),
            substr($right, 0, $rightWidth)
        ));
    }

    private function printWrappedTwoColumnsRight(Printer $printer, $left, $right, $leftWidth = 36, $rightWidth = 12)
    {
        $wrappedLines = wordwrap($left, $leftWidth, "\n", true);
        $lines = explode("\n", $wrappedLines);
        
        // First line with both columns
        $printer->text(sprintf("%-{$leftWidth}s%{$rightWidth}s\n",
            substr($lines[0], 0, $leftWidth),
            substr($right, 0, $rightWidth)
        ));
        
        // Subsequent lines (if any) without right column
        for ($i = 1; $i < count($lines); $i++) {
            $printer->text(sprintf("%-{$leftWidth}s\n",
                substr($lines[$i], 0, $leftWidth)
            ));
        }
    }

    private function printThreeColumns(Printer $printer, $col1, $col2, $col3, $col1Width = 6, $col2Width = 30, $col3Width = 12)
    {
        // Split the item name into multiple lines if needed
        $wrappedLines = wordwrap($col2, $col2Width, "\n", true);
        $lines = explode("\n", $wrappedLines);
        
        // Print first line with all columns
        $printer->text(sprintf("%-{$col1Width}s%-{$col2Width}s%{$col3Width}s\n",
            substr($col1, 0, $col1Width),
            substr($lines[0], 0, $col2Width),
            substr($col3, 0, $col3Width)
        ));
        
        // Print remaining lines (if any) with empty first column
        for ($i = 1; $i < count($lines); $i++) {
            $printer->text(sprintf("%-{$col1Width}s%-{$col2Width}s\n",
                '', // Empty first column
                substr($lines[$i], 0, $col2Width)
            ));
        }
    }

    public function formatAddress($address1, $address2, $maxLineLength = 32) {
        $formatted = "";
        
        // Process address line 1
        if (!empty($address1)) {
            $formatted .= wordwrap($address1, $maxLineLength, "\n", true);
        }
        
        // Process address line 2 if exists
        if (!empty($address2)) {
            if (!empty($formatted)) $formatted .= "\n";
            $formatted .= wordwrap($address2, $maxLineLength, "\n", true);
        }
        
        return $formatted;
    }

    // ===== TEST =====
    // ===== RAW HELPER METHODS =====
    private function printTwoColumnsRaw($addText, $left, $right, $leftWidth = 15, $rightWidth = 33)
    {
        $left = substr($left, 0, $leftWidth);
        $right = substr(" : $right", 0, $rightWidth);
        $addText(sprintf("%-{$leftWidth}s%-{$rightWidth}s", $left, $right));
    }

    private function printTwoColumnsRightRaw($addText, $left, $right, $leftWidth = 36, $rightWidth = 12)
    {
        $left = substr($left, 0, $leftWidth);
        $right = substr($right, 0, $rightWidth);
        $addText(sprintf("%-{$leftWidth}s%{$rightWidth}s", $left, $right));
    }

    private function printThreeColumnsRaw($addText, $col1, $col2, $col3, $col1Width = 6, $col2Width = 30, $col3Width = 12)
    {
        $wrappedLines = wordwrap($col2, $col2Width, "\n", true);
        $lines = explode("\n", $wrappedLines);
        
        // First line
        $col1 = substr($col1, 0, $col1Width);
        $line1 = substr($lines[0], 0, $col2Width);
        $col3 = substr($col3, 0, $col3Width);
        $addText(sprintf("%-{$col1Width}s%-{$col2Width}s%{$col3Width}s", $col1, $line1, $col3));
        
        // Additional lines
        for ($i = 1; $i < count($lines); $i++) {
            $line = substr($lines[$i], 0, $col2Width);
            $addText(sprintf("%-{$col1Width}s%-{$col2Width}s", '', $line));
        }
    }

    public function getPreviewReceiptLayout(Request $request)
    {
        $order = $request->order;
        $taxes = $request->taxes;
        $table_names = $request->table_names;
        $merchant = ConfigMerchant::first(['id', 'merchant_name', 'merchant_contact', 'merchant_address_line1', 'merchant_address_line2', 'postal_code', 'area', 'state']);
        $pointConversion = Setting::where('type', 'point')->first(['name', 'type', 'value', 'point']);

        $discounts = [];
        $billDiscountAmount = 0.00;
        $voucherDiscount = null;
        $voucherDiscountAmount = 0.00;
        $totalDiscountAmount = 0.00;
        $hasBillDiscount = false;

        foreach ($request->applied_discounts as $discount) {
            $discountAmount = 0.00;
            $discountSummary = '';

            switch ($discount['type']) {
                case 'bill':
                    $discountSummary = $discount['name'] ?? 'Bill Discount';
                    $discountRate = (float)($discount['discount_rate'] ?? 0);

                    $discountAmount = $discount['discount_type'] === 'amount'
                        ? $discountRate
                        : $order['total_amount'] * ($discountRate / 100);

                    $billDiscountAmount += $discountAmount;
                    $hasBillDiscount = true;
                    break;

                case 'voucher':
                    $rankName = $discount['ranking']['name'] ?? 'Unknown Rank';
                    $discountRate = (float)($discount['discount'] ?? 0);

                    $voucherRate = $discount['reward_type'] === 'Discount (Percentage)' 
                        ? "$discountRate%" 
                        : `RM $discountRate`;
                        
                    $discountSummary = "$voucherRate Discount (Entry Reward for $rankName)";
                    
                    $discountAmount = $discount['reward_type'] === 'Discount (Percentage)' 
                        ? $order['total_amount'] * ($discount['discount'] / 100)
                        : $discount['discount'];

                    $voucherDiscount = $discount;
                    $voucherDiscountAmount += $discountAmount;
                    break;

                case 'manual':
                    $discountRate = (float)($discount['rate'] ?? 0);
                    $discountSummary = "$discountRate% Discount";
                    $discountAmount = $order['total_amount'] * ($discountRate / 100);
                    $hasBillDiscount = true;
                    break;
            }

            $discounts[] = [
                'discount_summary' => $discountSummary,
                'discount_amount' => $discountAmount
            ];

            $totalDiscountAmount += $discountAmount;
        }

        $has_voucher_applied = count($discounts) > 0;
        $totalDiscountAmount = round($totalDiscountAmount, 2);

        // Calculate taxes
        $sstAmount = round((float)$order['total_amount'] * ((float)($taxes['SST'] ?? 0) / 100), 2);
        $serviceTaxAmount = round((float)$order['total_amount'] * ((float)($taxes['Service Tax'] ?? 0) / 100), 2);

        // Calculate grand total and rounding
        $calculatedSum = (float)$order['total_amount'] + $sstAmount + $serviceTaxAmount - $totalDiscountAmount;
        $grandTotal = $this->priceRounding(max($calculatedSum, 0.00));
        $roundingDiff = $grandTotal - $calculatedSum;

        // Calculate points to be earned from this payment
        $totalPoints = round(($grandTotal / $pointConversion['value']) * $pointConversion['point'], 2);

        // Create a buffer to capture ESC/POS commands
        $buffer = '';
        
        // Helper function to add text with line breaks
        $addText = function($text) use (&$buffer) {
            $buffer .= $text . "\n";
        };
        
        // Helper function to add raw ESC/POS commands
        $addCommand = function($command) use (&$buffer) {
            $buffer .= $command;
        };
        
        // ===== ESC/POS INITIALIZATION =====
        $addCommand("\x1B\x40"); // Initialize printer
        $addCommand("\x1B\x21\x00"); // Normal text (clear all formatting)
        $addCommand("\x1B\x4D\x00"); // Select Font B (default)
        
        // ===== HEADER SECTION =====
        $addCommand("\x1B\x61\x01"); // Center alignment
        $addCommand("\x1B\x21\x30"); // Double height + width
        $addText($merchant['merchant_name']);
        $addCommand("\x1B\x21\x00"); // Normal text
        
        $addText($this->formatAddress($merchant['merchant_address_line1'], $merchant['merchant_address_line2']));
        $addText("Phone: " . $merchant['merchant_contact']);
        $addText(""); // Empty line
        
        // Invoice title
        $addCommand("\x1B\x21\x08"); // Bold
        $addText("INVOICE");
        $addCommand("\x1B\x21\x00"); // Normal text
        $addCommand("\x1B\x61\x00"); // Left alignment
        $addText(""); // Empty line
        
        // ===== MEMBER INFO =====
        if ($order['customer']) {
            $customerName = $order['customer']['full_name'];
            $customerRank = Ranking::where('id', $order['customer']['ranking'])->first(['name']);

            $addText("$customerName");
            
            $this->printTwoColumnsRaw($addText, "Phone No.", $order['customer']['phone'] ? str_replace("+", "", $order['customer']['dial_code']) . $order['customer']['phone'] : '-');
            $this->printTwoColumnsRaw($addText, "Email", $order['customer']['email'] ?: '-');
            $this->printTwoColumnsRaw($addText, "Tier", $customerRank['name']);
            $this->printTwoColumnsRaw($addText, "Points Earned", $totalPoints ?? 0);
            $this->printTwoColumnsRaw($addText, "Points Balance", ($order['customer']['point'] + $totalPoints) ?? 0);
            $addText(""); // Empty line
        }
        
        // ===== RECEIPT INFO =====
        $this->printTwoColumnsRaw($addText, "Receipt No.", '-');
        $this->printTwoColumnsRaw($addText, "Table No.", $table_names);
        $this->printTwoColumnsRaw($addText, "Pax", $order['pax'] ?? '-');
        $this->printTwoColumnsRaw($addText, "Date", Carbon::parse($order['created_at'])->format('d/m/Y H:i A'));
        
        // ===== ITEMS TABLE =====
        $addText(str_repeat("-", 48));
        $this->printThreeColumnsRaw($addText, "QTY", "ITEM", "AMT (RM)");
        $addText(str_repeat("-", 48));
        
        $filteredOrderItems = collect($order['order_items'])->filter(fn ($item) => $item['status'] === 'Served' && $item['item_qty'] > 0);
        
        foreach ($filteredOrderItems as $key => $item) {
            $itemQty = $item['item_qty'];
            $itemType = $item['type'] !== 'Normal' ? "(" . $item['type'] . ")" : '';
            $itemBucket = $item['product']['bucket'] === 'set' ? '(Set) ' : '';
            $itemName = "$itemType$itemBucket" . $item['product']['product_name'];
            
            if ($item['discount_id']) {
                $amountBeforeDiscount = number_format($item['type'] === 'Normal' ? $item['amount'] : 0, 2);
                $discountName = $item['product_discount']['discount']['name'] . " Discount";
                
                $this->printThreeColumnsRaw($addText, $itemQty, $itemName, $amountBeforeDiscount);
                $this->printThreeColumnsRaw($addText, '', $discountName, "- " . $item['discount_amount']);
            } else {
                $totalAmount = number_format($item['amount'], 2);
                $this->printThreeColumnsRaw($addText, $itemQty, $itemName, $totalAmount);
            }
        }
        
        $addText(str_repeat("-", 48));
        
        // ===== TOTALS SECTION =====
        $this->printTwoColumnsRightRaw($addText, "Subtotal", number_format($order['total_amount'] ?? 0, 2));
        
        if ($hasBillDiscount && $billDiscountAmount > 0) {
            $this->printTwoColumnsRightRaw($addText, "Bill Discount", "- " . number_format($order['payment']['bill_discount_total'] ?? 0, 2));
        }

        if ($voucherDiscount) {
            $voucherDiscountExtTitle = $voucherDiscount['reward_type'] === 'Discount (Percentage)' ? "(" . $voucherDiscount['discount'] . "%)" : '';
            $this->printTwoColumnsRightRaw($addText, "Voucher Discount $voucherDiscountExtTitle", "- " . number_format($voucherDiscountAmount ?? 0, 2));
        }

        if ($serviceTaxAmount > 0) {
            $serviceTaxExtTitle = round($taxes['Service Tax']);
            $this->printTwoColumnsRightRaw($addText, "Service Tax ($serviceTaxExtTitle%)", number_format($serviceTaxAmount ?? 0, 2));
        }
        
        if ($sstAmount > 0) {
            $sstExtTitle = round($taxes['SST']);
            $this->printTwoColumnsRightRaw($addText, "SST ($sstExtTitle%)", number_format($sstAmount ?? 0, 2));
        }

        $this->printTwoColumnsRightRaw($addText, "Rounding", ($roundingDiff < 0 ? '-' : '') . number_format(abs($roundingDiff ?? 0), 2));
        
        // NET TOTAL
        $addCommand("\x1B\x21\x30"); // Double height + width
        $addCommand("\x1B\x45\x01"); // Bold
        $this->printTwoColumnsRightRaw($addText, "NET TOTAL", $grandTotal ?? '0.00', 10, 14);
        $addCommand("\x1B\x21\x00"); // Normal text
        $addCommand("\x1B\x45\x00"); // Bold off
        
        $addText(str_repeat("-", 48));
        $addText(""); // Empty line
        
        // ===== DISCOUNT SUMMARY =====
        if ($has_voucher_applied) {
            $addText("------------*** DISCOUNT SUMMARY ***------------");
            $this->printTwoColumnsRightRaw($addText, "DISCOUNTS", "AMT (RM)");
            
            foreach ($discounts as $key => $discount) {
                $this->printTwoColumnsRightRaw($addText, $discount['discount_summary'], "- " . number_format($discount['discount_amount'], 2));
            }
            
            $addText(""); // Empty line
        }
        
        // ===== FOOTER SECTION =====
        $addCommand("\x1B\x61\x01"); // Center alignment
        $addText("------*** Printed: " . now()->format('d/m/Y H:i A') . " ***------");
        
        // Add some empty lines and cut
        $addText("\n\n\n\n\n");
        $addCommand("\x1D\x56\x01"); // Partial cut

        return $buffer;
    }

    public function getPreviewReceipt(Request $request)
    {
        // Get printer
        $printer = ConfigPrinter::where([
                                    ['name', 'Cashier'],
                                    ['status', 'active']
                                ])
                                ->first();

        // Get the complete ESC/POS commands
        $buffer = $this->getPreviewReceiptLayout($request);
        
        try {

            // Return base64 encoded version for JSON safety
            return response()->json([
                'success' => true,
                'data' => base64_encode($buffer), // Encode binary as base64
                'printer' => $printer,
                'message' => 'Print job sent'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Print receipt error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getReceipt(Request $request)
    {
        $order = $request->order;
        $merchant = $request->merchant;
        $url = $request->url;
        $has_voucher_applied = $request->has_voucher_applied;
        $applied_discounts = $request->applied_discounts;
        $table_names = $request->table_names;

        // Create a buffer to capture ESC/POS commands
        $buffer = '';
        
        // Helper function to add text with line breaks
        $addText = function($text) use (&$buffer) {
            $buffer .= $text . "\n";
        };
        
        // Helper function to add raw ESC/POS commands
        $addCommand = function($command) use (&$buffer) {
            $buffer .= $command;
        };
        
        // ===== ESC/POS INITIALIZATION =====
        $addCommand("\x1B\x40"); // Initialize printer
        $addCommand("\x1B\x21\x00"); // Normal text (clear all formatting)
        $addCommand("\x1B\x4D\x00"); // Select Font B (default)
        
        // ===== HEADER SECTION =====
        $addCommand("\x1B\x61\x01"); // Center alignment
        $addCommand("\x1B\x21\x30"); // Double height + width
        $addText($merchant['merchant_name']);
        $addCommand("\x1B\x21\x00"); // Normal text
        
        $addText($this->formatAddress($merchant['merchant_address_line1'], $merchant['merchant_address_line2']));
        $addText("Phone: " . $merchant['merchant_contact']);
        $addText(""); // Empty line
        
        // Invoice title
        $addCommand("\x1B\x21\x08"); // Bold
        $addText("INVOICE");
        $addCommand("\x1B\x21\x00"); // Normal text
        $addCommand("\x1B\x61\x00"); // Left alignment
        $addText(""); // Empty line
        
        // ===== MEMBER INFO =====
        if ($order['customer']) {
            $customerName = $order['customer']['full_name'];
            $addText("$customerName");
            
            $this->printTwoColumnsRaw($addText, "Phone No.", $order['customer']['phone'] ? str_replace("+", "", $order['customer']['dial_code']) . $order['customer']['phone'] : '-');
            $this->printTwoColumnsRaw($addText, "Email", $order['customer']['email'] ?: '-');
            $this->printTwoColumnsRaw($addText, "Tier", $order['customer']['rank']['name']);
            $this->printTwoColumnsRaw($addText, "Points Earned", $order['payment']['point_history']['amount'] ?? 0);
            $this->printTwoColumnsRaw($addText, "Points Balance", $order['payment']['point_history']['new_balance'] ?? 0);
            $addText(""); // Empty line
        }
        
        // ===== RECEIPT INFO =====
        $this->printTwoColumnsRaw($addText, "Receipt No.", $order['payment']['receipt_no'] ?? '-');
        $this->printTwoColumnsRaw($addText, "Table No.", $table_names);
        $this->printTwoColumnsRaw($addText, "Pax", $order['payment']['pax'] ?? '-');
        $this->printTwoColumnsRaw($addText, "Date", Carbon::parse($order['created_at'])->format('d/m/Y H:i A'));
        
        // ===== ITEMS TABLE =====
        $addText(str_repeat("-", 48));
        $this->printThreeColumnsRaw($addText, "QTY", "ITEM", "AMT (RM)");
        $addText(str_repeat("-", 48));
        
        $filteredOrderItems = collect($order['order_items'])->filter(fn ($item) => $item['status'] === 'Served' && $item['item_qty'] > 0);
        
        foreach ($filteredOrderItems as $key => $item) {
            $itemQty = $item['item_qty'];
            $itemType = $item['type'] !== 'Normal' ? "(" . $item['type'] . ")" : '';
            $itemBucket = $item['product']['bucket'] === 'set' ? '(Set) ' : '';
            $itemName = "$itemType$itemBucket" . $item['product']['product_name'];
            
            if ($item['discount_id']) {
                $amountBeforeDiscount = number_format($item['type'] === 'Normal' ? $item['amount'] : 0, 2);
                $discountName = $item['product_discount']['discount']['name'] . " Discount";
                
                $this->printThreeColumnsRaw($addText, $itemQty, $itemName, $amountBeforeDiscount);
                $this->printThreeColumnsRaw($addText, '', $discountName, "- " . $item['discount_amount']);
            } else {
                $totalAmount = number_format($item['amount'], 2);
                $this->printThreeColumnsRaw($addText, $itemQty, $itemName, $totalAmount);
            }
        }
        
        $addText(str_repeat("-", 48));
        
        // ===== TOTALS SECTION =====
        $this->printTwoColumnsRightRaw($addText, "Subtotal", number_format($order['payment']['total_amount'] ?? 0, 2));
        
        if ($order['payment']['bill_discounts'] && $order['payment']['bill_discount_total'] > 0) {
            $this->printTwoColumnsRightRaw($addText, "Bill Discount", "- " . number_format($order['payment']['bill_discount_total'] ?? 0, 2));
        }
        
        if ($order['payment']['voucher']) {
            $voucherDiscountExtTitle = $order['payment']['voucher']['reward_type'] === 'Discount (Percentage)' ? "(" . $order['payment']['voucher']['discount'] . "%)" : '';
            $this->printTwoColumnsRightRaw($addText, "Voucher Discount $voucherDiscountExtTitle", "- " . number_format($order['payment']['discount_amount'] ?? 0, 2));
        }
        
        if ($order['payment']['service_tax_amount'] > 0) {
            $serviceTaxExtTitle = round(($order['payment']['service_tax_amount'] / $order['payment']['total_amount']) * 100);
            $this->printTwoColumnsRightRaw($addText, "Service Tax ($serviceTaxExtTitle%)", number_format($order['payment']['service_tax_amount'] ?? 0, 2));
        }
        
        if ($order['payment']['sst_amount'] > 0) {
            $sstExtTitle = round(($order['payment']['sst_amount'] / $order['payment']['total_amount']) * 100);
            $this->printTwoColumnsRightRaw($addText, "SST ($sstExtTitle%)", number_format($order['payment']['sst_amount'] ?? 0, 2));
        }
        
        $this->printTwoColumnsRightRaw($addText, "Rounding", ($order['payment']['rounding'] < 0 ? '-' : '') . number_format(abs($order['payment']['rounding'] ?? 0), 2));
        
        // NET TOTAL
        $addCommand("\x1B\x21\x30"); // Double height + width
        $addCommand("\x1B\x45\x01"); // Bold
        $this->printTwoColumnsRightRaw($addText, "NET TOTAL", $order['payment']['grand_total'] ?? '0.00', 10, 14);
        $addCommand("\x1B\x21\x00"); // Normal text
        $addCommand("\x1B\x45\x00"); // Bold off
        
        $addText(str_repeat("-", 48));
        $addText(""); // Empty line
        
        // ===== DISCOUNT SUMMARY =====
        if ($has_voucher_applied) {
            $addText("------------*** DISCOUNT SUMMARY ***------------");
            $this->printTwoColumnsRightRaw($addText, "DISCOUNTS", "AMT (RM)");
            
            foreach ($applied_discounts as $key => $discount) {
                $this->printTwoColumnsRightRaw($addText, $discount['discount_summary'], "- " . number_format($discount['discount_amount'], 2));
            }
            
            $addText(""); // Empty line
        }
        
        // ===== FOOTER SECTION =====
        $addCommand("\x1B\x61\x01"); // Center alignment
        // $addText("Scan QR below to request your e-Invoice");
        // $addText(""); // Empty line
        
        // // Generate QR code
        // $qrData = $url;
        // $qrLen = strlen($qrData) + 3;
        // $pL = $qrLen % 256;
        // $pH = intval($qrLen / 256);

        // $buffer .= "\x1D\x28\x6B\x03\x00\x31\x43\x05"; // Module size (5)
        // $buffer .= "\x1D\x28\x6B\x03\x00\x31\x45\x31"; // Error correction level L
        // $buffer .= "\x1D\x28\x6B" . chr($pL) . chr($pH) . "\x31\x50\x30" . $qrData; // Store data
        // $buffer .= "\x1D\x28\x6B\x03\x00\x31\x51\x30"; // Print the QR
        
        $addText(""); // Empty line
        $addText("Thank you for your visit!");
        $addText("Order invoice generated by");
        $addText("STOXPOS");
        $addText(""); // Empty line
        $addText("------*** Printed: " . now()->format('d/m/Y H:i A') . " ***------");
        
        // Add some empty lines and cut
        $addText("\n\n\n\n\n");
        $addCommand("\x1D\x56\x01"); // Partial cut

        return $buffer;
    }

    public function getTestReceipt(Request $request)
    {
        // $printerIp = '192.168.0.77';
        // $printerPort = '9100';

        // $socket = fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
        // if (!$socket) {
        //     return "Error: $errstr ($errno)";
        // }

        // fwrite($socket, $buffer);
        // fclose($socket);

        // Get printer
        $printer = ConfigPrinter::where([
                                    ['name', 'Cashier'],
                                    ['status', 'active']
                                ])
                                ->first();

        // Get the complete ESC/POS commands
        $buffer = $this->getReceipt($request);
        
        try {

            // Return base64 encoded version for JSON safety
            return response()->json([
                'success' => true,
                'data' => base64_encode($buffer), // Encode binary as base64
                'printer' => $printer,
                'message' => 'Print job sent'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Print receipt error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function kickDrawer()
    {
        // Get printer
        $printer = ConfigPrinter::where([
                                    ['name', 'Cashier'],
                                    ['status', 'active']
                                ])
                                ->first();

        // Create a buffer to capture ESC/POS commands
        $buffer = '';
        
        // ===== ESC/POS INITIALIZATION =====
        $buffer .= "\x1B\x40"; // Initialize printer
        $buffer .= "\x1B\x70\x00\x78\x1E"; // Kick drawer 

        return [
            'data' => base64_encode($buffer), // Encode binary as base64
            'printer' => $printer,
        ];
    }


}
