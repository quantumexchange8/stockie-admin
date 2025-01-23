<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderTableRequest;
use App\Jobs\GiveBonusPoint;
use App\Jobs\GiveEntryReward;
use App\Jobs\UpdateTier;
use App\Models\Category;
use App\Models\ConfigIncentive;
use App\Models\ConfigIncentiveEmployee;
use App\Models\ConfigMerchant;
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
use App\Models\Point;
use App\Models\PointHistory;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Ranking;
use App\Models\SaleHistory;
use App\Models\StockHistory;
use App\Models\Table;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\InventoryOutOfStock;
use App\Notifications\InventoryRunningOutOfStock;
use App\Notifications\OrderAssignedWaiter;
use App\Notifications\OrderCheckInCustomer;
use App\Notifications\OrderPlaced;
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Request;
use App\Models\Zone;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Facades\Notification;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $zones = Zone::with([
                            'tables.orderTables' => fn ($query) => $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled']),
                            'tables.orderTables.order',
                            'tables.orderTables.order.orderItems.subItems:id,item_qty,order_item_id,serve_qty'
                        ])
                        ->select('id', 'name')
                        ->get()
                        ->map(function ($zone) {
                            return [
                                'text' => $zone->name,
                                'value' => $zone->id,
                                'tables' => $zone->tables->map(function ($table) {
                                    $pendingCount = $table->orderTables->reduce(function ($pendingCount, $orderTable) {
                                        $orderPendingCount = $orderTable->order->orderItems->where('status', 'Pending Serve')->reduce(function ($itemCount, $orderItem) {
                                            $itemTotalQty = collect($orderItem->subItems)->reduce(function ($subTotal, $subItem) use ($orderItem) {
                                                return $subTotal + ($subItem['item_qty'] * $orderItem['item_qty'] - $subItem['serve_qty']);
                                            }, 0);
                                            return $itemCount + $itemTotalQty;
                                        }, 0);
                                        return $pendingCount + $orderPendingCount;
                                    }, 0);
                    
                                    $table->pending_count = $pendingCount;
                                    return $table;
                                }),
                            ];
                        });
    
        
        // dd($zones);

        // $waiters = Waiter::orderBy('id')->get();
        $users = User::select(['id', 'full_name', 'position'])->orderBy('id')->get();
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
                                ->get()
                                ->map(function ($customer) {
                                    return [
                                        'text' => $customer->full_name,
                                        'value' => $customer->id,
                                        'image' => $customer->getFirstMediaUrl('customer'),
                                    ];
                                });

        $merchant = ConfigMerchant::select('id', 'merchant_name', 'merchant_contact', 'merchant_address_line1')->first();

        $merchant->image = $merchant->getFirstMediaUrl('merchant_settings');
        
        // Get the flashed messages from the session
        // $message = $request->session()->get('message');

        return Inertia::render('Order/Order', [
            // 'message' => $message ?? [],
            'zones' => $zones,
            'users' => $users,
            // 'orders' => $orders,
            'occupiedTables' => Table::where('status', '!=', 'Empty Seat')->get(),
            'customers' => $customers,
            'merchant' => $merchant
        ]);
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

        Notification::send(User::all(), new OrderCheckInCustomer($tableString, $waiter->full_name, $waiter->id));

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

        Notification::send(User::all(), new OrderAssignedWaiter($tableString, auth()->user()->id, $waiter->id));

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

                Notification::send(User::all(), new OrderPlaced($tableString, $waiter->full_name, $waiter->id));

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
                        $productItem = ProductItem::with('inventoryItem:id,item_name,stock_qty,item_cat_id,inventory_id,low_stock_qty')->find($value['id']);
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
                        ]);
                        
                        $serveQty = $serveNow ? $value['qty'] * $item['item_qty'] : 0;
                        OrderItemSubitem::create([
                            'order_item_id' => $new_order_item->id,
                            'product_item_id' => $value['id'],
                            'item_qty' => $value['qty'],
                            'serve_qty' => $serveQty,
                        ]);

                        if($newStatus === 'Out of stock'){
                            Notification::send(User::all(), new InventoryOutOfStock($inventoryItem->item_name, $inventoryItem->id));
                        };

                        if($newStatus === 'Low in stock'){
                            Notification::send(User::all(), new InventoryRunningOutOfStock($inventoryItem->item_name, $inventoryItem->id));
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
                                'productItems', 
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
        $currentOrderTable = $orderTables->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled'])
                                            ->firstWhere('status', '!=', 'Pending Clearance') 
                            ?? $orderTables->first();

        if (!!$currentOrderTable) {
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
                'order.customer:id,full_name,email,phone,point',
                'order.orderTable:id,order_id,table_id,status',
                'order.orderTable.table:id,table_no',
                'order.payment:id,order_id',
                'order.voucher:id,reward_type,discount'
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
            }

            // $currentOrderTable->order->map(function ($order) {
            //     $pendingItemsCount = $order->orderItems->where('status', 'Pending Serve')->count();
            //     $order->pending_count = $pendingItemsCount;
            //     return $order;
            // });

            // dd($currentOrderTable->order->orderItems->where('status', 'Pending Serve')->count());
            $data = [
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
        $existingOrder = Order::with([
                                    'orderItems.subItems.productItem.inventoryItem:id,item_name,stock_qty,low_stock_qty,inventory_id', 
                                    'orderTable.table',
                                ])->find($id);

        if ($existingOrder) {
            foreach ($existingOrder->orderItems as $item) {
                if ($item['status'] === 'Pending Serve') {
                    foreach ($item->subItems as $subItem) {
                        $inventoryItem = $subItem->productItem->inventoryItem;
                        
                        $qtySold = $subItem['serve_qty'];
                        $restoredQty = $item['item_qty'] * $subItem['item_qty'] - $qtySold;
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
                            ]);
                        }
                    }
                }

                $item->update(['status' => 'Cancelled']);
            }

            $existingOrder->update(['status' => 'Order Cancelled']);

            // Update all tables associated with this order
            $existingOrder->orderTable->each(function ($tab) {
                $tab->table->update([
                    'status' => 'Empty Seat',
                    'order_id' => null
                ]);
                $tab->update(['status' => 'Order Cancelled']);
            });
        }

        return redirect()->back();
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
                    'status' => 'Served',
                ]);
            }

            $order = Order::with(['orderTable', 'orderItems'])->find($orderItem->order_id);
            
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
                    return $table->status === 'Pending Clearance';
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
    public function getAllZones()
    {
        $zones = Zone::with([
                            'tables.orderTables' => fn ($query) => $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled']),
                            'tables.orderTables.order',
                        ])
                        ->select('id', 'name')
                        ->get()
                        ->map(function ($zone) {
                            return [
                                'text' => $zone->name,
                                'value' => $zone->id,
                                'tables' => $zone->tables->map(function ($table) {
                                    $pendingCount = $table->orderTables->reduce(function ($pendingCount, $orderTable) {
                                        $orderPendingCount = $orderTable->order->orderItems->where('status', 'Pending Serve')->reduce(function ($itemCount, $orderItem) {
                                            $itemTotalQty = collect($orderItem->subItems)->reduce(function ($subTotal, $subItem) use ($orderItem) {
                                                return $subTotal + ($subItem['item_qty'] * $orderItem['item_qty'] - $subItem['serve_qty']);
                                            }, 0);
                                            return $itemCount + $itemTotalQty;
                                        }, 0);
                                        return $pendingCount + $orderPendingCount;
                                    }, 0);
                    
                                    $table->pending_count = $pendingCount;
                                    return $table;
                                }),
                            ];
                        });
                            
        return response()->json($zones);
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

        if ($request->action_type === 'complete') {
            // if ($order->status === 'Order Served' || $order->status === 'Pending Serve') {
                $order->update(['status' => 'Order Completed']);
                // $order->refresh();
            // }

            $statusArr = collect($order->orderTable->pluck('status')->unique());
            if ($order->status === 'Order Completed' && ($statusArr->count() === 1 && ($statusArr->first() === 'All Order Served' || $statusArr->first() === 'Order Placed' || $statusArr->first() === 'Pending Order'))) {
                $settings = Setting::select(['name', 'type', 'value', 'point'])->whereIn('type', ['tax', 'point'])->get();
                $taxes = $settings->filter(fn($setting) => $setting['type'] === 'tax')->pluck('value', 'name');
                $pointConversion = $settings->filter(fn($setting) => $setting['type'] === 'point')->first();

                $subTotal = $order->amount;
                $sstAmount = round($subTotal * ($taxes['SST'] / 100), 2);
                $serviceTaxAmount = round($subTotal * ($taxes['Service Tax'] / 100), 2);

                if ($order->voucher) {
                    $voucherDiscountedAmount = $order->voucher->reward_type === 'Discount (Percentage)' 
                            ? $subTotal * ($order->voucher->discount / 100)
                            : $order->voucher->discount;
                } else {
                    $voucherDiscountedAmount = 0.00;
                }
                
                $grandTotal = $this->priceRounding($subTotal + $sstAmount + $serviceTaxAmount - ($order->voucher_id ? $voucherDiscountedAmount : 0));
                $roundingDiff = $grandTotal - ($subTotal + $sstAmount + $serviceTaxAmount - ($order->voucher_id ? $voucherDiscountedAmount : 0));
                $totalPoints = ($grandTotal / $pointConversion['value']) * $pointConversion['point'];
                
                $paymentData = [
                    'receipt_end_date' => now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                    'total_amount' => $subTotal,
                    'grand_total' => $grandTotal,
                    'rounding' => $roundingDiff,
                    'sst_amount' => $sstAmount,
                    'service_tax_amount' => $serviceTaxAmount,
                    'point_earned' => (int) round($totalPoints, 0, PHP_ROUND_HALF_UP),
                    'customer_id' => $request->customer_id,
                    'handled_by' => $request->user_id,
                    'discount_id' => $order->voucher_id,
                    'discount_amount' => $voucherDiscountedAmount
                ];

                $order->payment
                    ? $order->payment->update($paymentData)
                    : Payment::create($paymentData + [
                        'transaction_id' => null,
                        'order_id' => $order->id,
                        'table_id' => $order->orderTable->pluck('table.id'),
                        'receipt_no' => RunningNumberService::getID('payment'),
                        'receipt_start_date' => $order->created_at,
                        'pax' => $order->pax,
                        'status' => 'Pending'
                    ]);

                // Update the calculated total amount of the order
                // $order->update(['total_amount' => $grandTotal]);
            }
        }
        
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
                $order->orderTable->each(function ($tab) {
                    if ($tab->table['status'] !== 'Empty Seat' && $tab->table['order_id'] !== null) {
                        $tab->table->update([
                            'status' => 'Empty Seat',
                            'order_id' => null
                        ]);
                    }
                    $tab->update(['status' => 'Order Completed']);
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
            $query->where('status', 'Order Cancelled')
                ->orWhere(function ($subQuery) {
                    $subQuery->where('status', 'Order Completed')
                        ->whereHas('payment', function ($paymentQuery) {
                            $paymentQuery->where('status', 'Successful');
                        });
                });
        });

        $orders = $query->with([
                            'orderItems:id,order_id,type,product_id,item_qty,amount_before_discount,discount_id,discount_amount,amount,status', 
                            'orderItems.product:id,product_name,price,bucket,discount_id', 
                            'orderItems.product.discount:id,name', 
                            'orderItems.productDiscount:id,discount_id',
                            'orderItems.productDiscount.discount:id,name',
                            'orderTable:id,table_id,order_id', 
                            'orderTable.table:id,table_no', 
                            'waiter:id,full_name',
                            'customer:id,ranking,point',
                            'customer.rank:id,name',
                            'payment:id,order_id,receipt_no,receipt_end_date,total_amount,rounding,sst_amount,service_tax_amount,discount_id,discount_amount,grand_total,point_earned,pax,status',
                            'payment.pointHistory:id,payment_id,amount,new_balance',
                            'payment.voucher:id,reward_type,discount',
                        ])
                        ->orderBy('updated_at', 'desc')
                        ->get()
                        ->values();
        // dd($query->toSql(), $query->getBindings());

        $orders->each(function($order){
            foreach ($order->orderItems as $orderItem) {
                $orderItem->product->discount_item = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first();
                unset($orderItem->product->discountItems);
            }

            if($order->waiter){
                $order->waiter->image = $order->waiter->getFirstMediaUrl('user');
            };
        });

        $taxes = Setting::whereIn('name', ['SST', 'Service Tax'])->pluck('value', 'name');

        $data = [
            'orders' => $orders,
            'taxes' => $taxes
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
                                $productItem = ProductItem::with('inventoryItem:id,item_name,stock_qty,low_stock_qty,inventory_id')->find($subItem->product_item_id);
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

                                StockHistory::create([
                                    'inventory_id' => $inventoryItem->inventory_id,
                                    'inventory_item' => $inventoryItem->item_name,
                                    'old_stock' => $oldStockQty,
                                    'in' => $restoredQty,
                                    'out' => 0,
                                    'current_stock' => $inventoryItem->stock_qty,
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
            return response()->json(['errors' => $allItemErrors], 422);
        }

        if (count($validatedItems) > 0) {
            $order = Order::with(['orderTable.table', 'orderItems.subItems', 'orderItems.product'])->find($request->order_id);
            $currentOrder = Order::with(['orderTable.table', 'orderItems.subItems', 'orderItems.product'])->find($request->current_order_id);

            $orderItems = $order->orderItems;
            foreach ($orderItems as $orderItemKey => $orderItem) {
                $totalItemQty = 0;
                $totalServedQty = 0;
                $hasServeQty = false;

                foreach ($orderItem->subItems as $subItemKey => $subItem) {
                    foreach ($validatedItems as $key => $item) {
                        foreach ($request->items as $reqItemKey => $reqItem) {
                            if ($item['order_item_subitem_id'] === $reqItem['order_item_subitem_id'] && $subItem['id'] === $item['order_item_subitem_id']) {
                                if ($item['amount'] > 0) {
                                    if ($reqItem['keep_id']) {
                                        $keepItem = KeepItem::find($reqItem['keep_id']);
                                        $keepItem->update([
                                            'qty' => $reqItem['type'] === 'qty' ? $keepItem->qty + $item['amount'] : $keepItem->qty,
                                            'cm' => $reqItem['type'] === 'cm' ? $keepItem->cm + $item['amount'] : $keepItem->cm,
                                            'status' => 'Keep',
                                        ]);

                                        $keepItem->save();
                                        $keepItem->refresh();
            
                                        KeepHistory::create([
                                            'keep_item_id' => $reqItem['keep_id'],
                                            'order_item_id' => $reqItem['order_item_id'],
                                            'qty' => $reqItem['type'] === 'qty' ? round($item['amount'], 2) : 0,
                                            'cm' => $reqItem['type'] === 'cm' ? number_format((float) $item['amount'], 2, '.', '') : '0.00',
                                            'keep_date' => $keepItem->updated_at,
                                            'status' => 'Keep',
                                        ]);
                                    } else {
                                    //     dd($reqItem['type'] === 'cm' ? round($item['amount'], 2) : 0.00,
                                    // number_format((float) '8.258', 2, '.', ''));
                                        // dd($item['expired_from']);
                                        $newKeep = KeepItem::create([
                                            'customer_id' => $request->customer_id,
                                            'order_item_subitem_id' => $item['order_item_subitem_id'],
                                            'qty' => $reqItem['type'] === 'qty' ? $item['amount'] : 0,
                                            'cm' => $reqItem['type'] === 'cm' ? $item['amount'] : 0,
                                            'remark' => $item['remark'] ?: null,
                                            'user_id' => $request->user_id,
                                            'status' => 'Keep',
                                            'expired_from' => $item['expired_from'],
                                            'expired_to' => $item['expired_to'],
                                        ]);

                                        $item = OrderItemSubitem::where('id', $item['order_item_subitem_id'])
                                                                ->with(['productItem:id,inventory_item_id', 'productItem.inventoryItem:id,item_name'])
                                                                ->first();
                                        $name = Customer::where('id', $request->customer_id)->first()->pluck('full_name');
                                        
                                        activity()->useLog('keep-item-from-customer')
                                                    ->performedOn($newKeep)
                                                    ->event('kept')
                                                    ->withProperties([
                                                        'edited_by' => auth()->user()->full_name,
                                                        'image' => auth()->user()->getFirstMediaUrl('user'),
                                                        'item_name' => $item->item_name,
                                                        'customer_name' => $name,
                                                    ])
                                                    ->log("'$item->item_name' is kept in $name's account.");
                            
                                        KeepHistory::create([
                                            'keep_item_id' => $newKeep->id,
                                            'qty' => $reqItem['type'] === 'qty' ? round($item['amount'], 2) : 0.00,
                                            'cm' => $reqItem['type'] === 'cm' ? number_format((float) $item['amount'], 2, '.', '') : '0.00',
                                            'keep_date' => $newKeep->created_at,
                                            'status' => 'Keep',
                                        ]);
                                    }

                                    if ($orderItem->status === 'Pending Serve') {
                                        $subItem->increment('serve_qty', $reqItem['type'] === 'cm' ? 1 : $item['amount']);
                                    }
                                    $subItem->save();
                                    $subItem->refresh();
                                }
        
                                $totalItemQty += $subItem['item_qty'] * $orderItem->item_qty;
                                $totalServedQty += $subItem['serve_qty'];  
                                $hasServeQty = $item['amount'] > 0 || $hasServeQty ? true : false;
                            }
                        }
                    }
                }

                if ($hasServeQty) {
                    $orderItem->update(['status' => $totalServedQty === $totalItemQty ? 'Served' : 'Pending Serve']);
                }
            }
            $currentOrder->refresh();

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
                                    $query->select('id', 'customer_id', 'order_item_subitem_id', 'user_id', 'qty', 'cm', 'remark', 'status', 'expired_to', 'created_at')
                                            ->where('status', 'Keep')
                                            ->with([
                                                'orderItemSubitem.productItem:id,inventory_item_id',
                                                'orderItemSubitem.productItem.inventoryItem:id,item_name',
                                                'waiter:id,full_name'
                                            ]);
                                }
                            ])
                            ->find($request->current_customer_id);
        
        foreach ($customer->keepItems as $key => $keepItem) {
            $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
            unset($keepItem->orderItemSubitem);
            
            $keepItem->image = $keepItem->orderItemSubitem->productItem 
                    ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                    : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');
            
            $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
        }

        return response()->json($customer->keepItems);
        
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
                                        'keepItem.waiter:id,full_name'
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
                                        return $history;
                                    });

        return response()->json($keepHistories);
    }

    /**
     * Add the customer's kept item(s) to current order.
     */
    public function addKeptItemToOrder(Request $request, string $id) 
    {
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
                                                    'orderItemSubitem.productItem:id,product_id',
                                                    'orderItemSubitem.productItem.inventoryItem:id,item_name', 
                                                    'orderItemSubitem.orderItem:id'])
                                ->find($id);

            $newOrderItem = OrderItem::create([
                'order_id' => $request->order_id,
                'user_id' => $request->user_id,
                'type' => 'Keep',
                'product_id' => $keepItem->orderItemSubitem->productItem->product_id,
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
                'product_item_id' => $keepItem->orderItemSubitem->productItem->id,
                'item_qty' => 1,
                'serve_qty' => $request->return_qty,
            ]);

            KeepHistory::create([
                'keep_item_id' => $id,
                'order_item_id' => $newOrderItem->id,
                'qty' => $request->type === 'qty' ? round($request->return_qty, 2) : 0.00,
                'cm' => $request->type === 'cm' ? number_format((float) $keepItem->cm, 2, '.', '') : '0.00',
                'keep_date' => $keepItem->created_at,
                'status' => 'Served',
            ]);

            $order = Order::with(['orderTable.table', 'customer:id,full_name'])->find($request->order_id);

            activity()->useLog('return-kept-item')
                        ->performedOn($newOrderItem)
                        ->event('updated')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'item_name' => $newOrderItem->orderItemSubitem->productItem->inventoryItem->item_name,
                            'customer_name' => $order->customer->full_name
                        ])
                        ->log(":properties.item_name is returned to :properties.customer_name.");

            if ($request->type === 'qty') {
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

    /**
     * Update the current order's selected customer.
     */
    public function updateOrderCustomer(Request $request, string $id) 
    {
        $validatedData = $request->validate(
            ['customer_id' => 'required|integer'], 
            ['required' => 'This field is required.']
        );

        $order = Order::find($id);

        $order->update(['customer_id' => $validatedData['customer_id']]);

        return redirect()->back();
    }

    /**
     * Get the payment details of the order.
     */
    public function getOrderPaymentDetails(string $id) 
    {
        $order = Order::with('payment')->find($id);
        $taxes = Setting::whereIn('name', ['SST', 'Service Tax'])->pluck('value', 'name');

        $data = [
            'payment_details' => $order->payment,
            'taxes' => $taxes
        ];

        return response()->json($data);
    }

    /**
     * Update order's payment status.
     */
    public function updateOrderPayment(Request $request, string $id) 
    {
        $payment = Payment::find($id);
        $order = Order::with([
                            'orderTable.table', 
                            'customer:id,point,total_spending,ranking', 
                            'orderItems' => fn($query) => $query->with('product.commItem.configComms')->where('status', 'Served')
                        ])->findOrFail($request->order_id);

        // Handle sale history for "Normal" order items
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
                'product_id' => $item->product_id,
                'total_price' => $item->amount,
                'qty' => (int) $item->item_qty,
            ]);
        });

        $pointConversion = Setting::where('type', 'point')->first(['value', 'point']);
        $totalPoints = (int) round(($payment->grand_total / $pointConversion->value) * $pointConversion->point);

        $customer = $order->customer;

        // Add the accumulated points earned from the order to the customer
        if ($customer) {
            $oldPointBalance = $customer->point;
            $newPointBalance = $oldPointBalance + $totalPoints;
            $oldTotalSpending = $customer->total_spending;
            $newTotalSpending = $oldTotalSpending + $payment->grand_total;
            $oldRanking = $customer->ranking;

            Bus::chain([
                new UpdateTier($customer->id, $newPointBalance, $newTotalSpending),
                new GiveEntryReward($oldRanking, $customer->id, $oldTotalSpending),
                new GiveBonusPoint($id, $totalPoints, $oldPointBalance, $customer->id, $request->user_id)
            ])->dispatch();
            
            /* START PUTTING INTO UPDATE TIER JOB */
            // Determine the new rank based on the new total spending 
            $newRankingDetails = Ranking::where('min_amount', '<=', $newTotalSpending)
                                ->select('id', 'name')
                                ->orderBy('min_amount', 'desc')
                                ->first();
            
            $newRankingDetails->image = $newRankingDetails->getFirstMediaUrl('ranking');

            // $newRanking = $newRankingDetails->value('id');

            // // Update customer points and total spending
            // $customer->update([
            //     'point' => $newPointBalance,
            //     'total_spending' => $newTotalSpending,
            //     'ranking' => $newRanking
            // ]);
            // $customer->refresh();
            /* STOP PUTTING INTO UPDATE TIER JOB */


            // GIVE ENTRY REWARD JOB

            // $bonusPointRewards = [];
            // // Grant rewards for rank up 
            // if ($newRanking && $newRanking != $oldRanking) { 
            //     $ranks = Ranking::select('id')
            //                     ->with(['rankingRewards' => function ($query) {
            //                         $query->where('status', 'Active')
            //                                 ->select('id', 'ranking_id', 'reward_type', 'bonus_point');
            //                     }])
            //                     ->where([
            //                         ['reward', 'active'],
            //                         ['min_amount', '<=', $newTotalSpending],
            //                         ['min_amount', '>', $oldTotalSpending]
            //                     ]) 
            //                     ->orderBy('min_amount', 'asc') 
            //                     ->get(); 

            //     foreach ($ranks as $rank) { 
            //         $rank->rankingRewards->each(function ($reward) use ($customer, $bonusPointRewards) {
            //             CustomerReward::create([ 
            //                 'customer_id' => $customer->id, 
            //                 'ranking_reward_id' => $reward->id, 
            //                 'status' => 'Active' 
            //             ]);

            //             if ($reward->reward_type === 'Bonus Point') {
            //                 array_push($bonusPointRewards, $reward);
            //             }
            //         }); 
            //     };
            // };

            // END GIVE ENTRY REWARD JOB 


            // START GIVE BONUS POINT JOB 
        //     // Log point history for earned points from completed order
        //     PointHistory::create([ 
        //         'product_id' => null, 
        //         'payment_id' => $id, 
        //         'type' => 'Earned', 
        //         'point_type' => 'Order', 
        //         'qty' => 0, 
        //         'amount' => $totalPoints, 
        //         'old_balance' => $oldPointBalance, 
        //         'new_balance' => $newPointBalance, 
        //         'customer_id' => $customer->id, 
        //         'handled_by' => $request->user_id, 
        //         'redemption_date' => now() 
        //     ]);

        //     // Log point history for earned bonus point entry reward
        //     foreach ($bonusPointRewards as $key => $reward) {
        //         $currentPoint = $customer->point;
        //         $pointAfterBonus = $currentPoint + $reward['bonus_point'];
                
        //         PointHistory::create([ 
        //             'product_id' => null, 
        //             'payment_id' => null, 
        //             'type' => 'Earned', 
        //             'point_type' => 'Entry Reward', 
        //             'qty' => 0, 
        //             'amount' => $reward['bonus_point'], 
        //             'old_balance' => $currentPoint, 
        //             'new_balance' => $pointAfterBonus, 
        //             'customer_id' => $customer->id, 
        //             'handled_by' => $request->user_id, 
        //             'redemption_date' => now() 
        //         ]);
                
        //         // Update customer points and total spending
        //         $customer->update(['point' => $pointAfterBonus]);
        //         $customer->refresh();

        //         $customerReward = CustomerReward::find($reward['id']);
        //         $customerReward->update(['status' => 'Redeemed']);
        //     }
        };
        /* END GIVE BONUS POINT JOB */

        // Record waiter earned commissions
        

        // Update payment status
        $payment->update(['status' => 'Successful']);

        // Update the total amount of the order based on the payment's grandtotal
        $order->update(['total_amount' => $payment->grand_total]);

        $order->orderTable->each(function ($tab) {
            $tab->table->update(['status' => 'Pending Clearance']);
            $tab->update(['status' => 'Pending Clearance']);
        });

        // return response()->json($customer ? $newPointBalance : 'guest');
        return response()->json($customer ? ([
            'newPointBalance' => $newPointBalance,
            'newRanking' => $newRankingDetails,
        ]) : []);
    }

    /**
     * Get the payments made on specified currently occupied table.
     */
    public function getOccupiedTablePayments(string $id) 
    {
        $orderTables = OrderTable::with([
                                        'table', 
                                        'order.voucher:id,reward_type,discount', 
                                        'order.payment.customer', 
                                        'order.orderItems' => fn($query) => $query->whereNotIn('status', ['Cancelled']),
                                        'order.orderItems.product',
                                        'order.orderItems.subItems.productItem.inventoryItem',
                                    ])
                                    ->where([
                                        ['table_id', $id],
                                        ['status', 'Pending Clearance'],
                                    ])
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
        $redeemables = Product::select('id','product_name', 'point')
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
                                            'customer.rank:id,name'
                                        ]) 
                                        ->where('customer_id', $id)
                                        ->orderBy('created_at','desc')
                                        ->get();

        $pointHistories->each(function ($record) {
            $record->image = $record->redeemableItem?->getFirstMediaUrl('product');
        });

        return response()->json($pointHistories);
    }

    /**
     * Redeem item and add to current order.
     */
    public function redeemItemToOrder(Request $request, string $id)
    {
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
                                            'productItems.inventoryItem:id,item_name,stock_qty,item_cat_id,inventory_id,low_stock_qty'
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
    }

    /**
     * Redeem entry reward and add to current order.
     */
    public function redeemEntryRewardToOrder(Request $request, string $id)
    {
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
                                                'rankingReward.product.productItems.inventoryItem:id,item_name,stock_qty,inventory_id,low_stock_qty'
                                            ])
                                            ->findOrFail($validatedData['customer_reward_id']);

            $tierReward = $selectedReward->rankingReward;
            $freeProduct = $tierReward->product;

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
                $order = Order::find($id);
                $order->update(['voucher_id' => $tierReward->id]);
                $selectedReward->update(['status' => 'Redeemed']);
            };

            $summary = match ($tierReward->reward_type) {
                'Discount (Amount)' => "'RM $tierReward->discount Discount' has been applied to this order.",
                'Discount (Percentage)' => "'$tierReward->discount% Discount' has been applied to this order.",
                'Free Item' => "'$freeProduct?->product_name' has been added to this customer's order.",
            };
        }
        
        return response()->json($summary);
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
                                'rewards.rankingReward.product:id,product_name'
                            ])
                            ->find($id);

        return response()->json($customer->rewards);
    }

    /**
     * Remove the voucher applied to the order and reinstate back to customer's reward list.
     */
    public function removeOrderVoucher(string $id)
    {
        $order = Order::select('id', 'customer_id', 'voucher_id')
                            ->with([
                                'customer:id',
                                'customer.rewards:id,customer_id,ranking_reward_id,status',
                            ])
                            ->find($id);

        if (!$order || !$order->customer) return response()->json(['error' => 'Order or customer not found'], 404); 
                            
        $customer = $order->customer;
        $removalTarget = $customer->rewards->where('ranking_reward_id', $order->voucher_id)->first();
        
        if (!$removalTarget) return response()->json(['error' => 'Reward not found'], 404);

        $order->update(['voucher_id' => null]);
        $removalTarget->update(['status' => 'Active']);

        return response()->json();
    }

    public function getTableKeepItem(string $id)
    {
        $orderTables = OrderTable::with([
                                        'table',
                                        'order.payment.customer',
                                        'order.orderItems' => fn($query) => $query->where('status', 'Served')->orWhere('status', 'Pending Serve'),
                                        'order.orderItems.product',
                                        'order.orderItems.subItems.productItem.inventoryItem',
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

        $allCustomers = Customer::select('id', 'full_name', 'email', 'phone')->get();
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

    public function reactivateExpiredItems(Request $request)
    {
        $targetItem = KeepHistory::with('keepItem')->find($request->input('id'))->first();

        KeepHistory::create([
            'keep_item_id' => $targetItem->keepItem->id,
            'qty' => $targetItem->keepItem->qty,
            'cm' => number_format((float) $targetItem->keepItem->cm, 2, '.', ''),
            'keep_date' => Carbon::parse($request->input('expiry_date'))->timezone('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
            'status' => 'Extended'
        ]);
        
        $item = KeepItem::find($targetItem->keepItem->id)->with('orderItemSubitem.productItem.inventoryItem')->update([
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
                    ->log("Category name ':properties.item''s detail is updated.");

        return redirect()->back();
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
                                ->with(['orderItemSubitem:id,product_item_id','productItem:id,inventory_item_id','inventoryItem:id,item_name'])
                                ->first();
        $targetItem->update(['status' => 'Deleted']);

        activity()->useLog('delete-category')
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
            'status' => 'Deleted',
        ]);

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
                                    ->whereNotIn('status', ['Order Completed', 'Order Cancelled'])
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
                                    ->get();

        // if($currentTable->order->customer) {
        //     $currentTable->order->customer->image = $currentTable->order->customer_id ? $currentTable->order->customer->getFirstMediaUrl('customer') : null;
        // }

        $currentTable->each(function ($table) {
            if ($table->order && $table->order->customer_id) {
                $table->order->customer->image = $table->order->customer->getFirstMediaUrl('customer');
            }

            foreach ($table->order->orderItems as $orderItem) {
                $orderItem->image = $orderItem->product->getFirstMediaUrl('product');
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
        
        $targetItem = KeepItem::find($validatedData['id']);
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
            'remark' => $targetItem->remark,
            'status' => 'Edited',
        ]);

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

    public function mergeTable(Request $request) {
        // dd($request->all());
        $request->validate([
            'customer_id' => ['required'],
            'id' => ['required', 'integer'],
            'tables' => ['required', 'array'],
        ]);

        dd($request->all());

        // calculate total pax for all the merged tables
        $totalPax = collect($request['tables'])
                    ->flatMap(fn($table) => $table['order_tables'] ?? [])
                    ->sum(fn($orderTable) => (int)($orderTable['pax'] ?? 0));

        // calculate total sum for all the merged tables
        $totalAmount = collect($request['tables'])
                    ->flatMap(fn($table) => $table['order_tables'] ?? [])
                    ->sum(fn($orderTable) => (float)($orderTable['order']['total_amount'] ?? 0));
        
        //step 1: create new order
        $newOrder = Order::create([
            'order_no' => RunningNumberService::getID('order'),
            'pax' => $totalPax, //combined pax
            'user_id' => auth()->user()->id,
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'status' => 'Order Completed',
        ]);

        //step 2: update listed tables to order merged & create new order table
        foreach($request['tables'] as $tables){
            foreach($tables['order_tables'] as $orderTable){
                $mergingTable = OrderTable::find($orderTable['id']);
                $mergingTable->update(['status' => 'Order Merged']);
                $mergingTable->save();

                
            }

            OrderTable::create([
                'table_id' => $tables['id'],
                'pax' => $totalPax,
                'user_id' => auth()->user()->id,
                'status' => 'All Order Served',
                'order_id' => $newOrder->id,                
            ]);
        }

        //step 3: transfer all the order item to this new order (update order_id)


        //step 4: update all tables' status

    }

    private function getAllCustomers() {
        $users = Customer::all();
        foreach($users as $user){
            $user->image = $user->getFirstMediaUrl('customer');
        }

        return $users;
    }

    public function getAllCustomer(){
        $users = $this->getAllCustomers();

        return response()->json($users);
    }
}
