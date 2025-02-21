<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderTableRequest;
use App\Models\Category;
use App\Models\ConfigMerchant;
use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\IventoryItem;
use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemSubitem;
use App\Models\OrderTable;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\StockHistory;
use App\Models\Table;
use App\Models\User;
use App\Models\Zone;
use App\Notifications\InventoryOutOfStock;
use App\Notifications\InventoryRunningOutOfStock;
use App\Notifications\OrderAssignedWaiter;
use App\Notifications\OrderCheckInCustomer;
use App\Notifications\OrderPlaced;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    protected $authUser;
    public function __construct()
    {
        $this->authUser = User::find(Auth::id());
        $this->authUser->image = $this->authUser->getFirstMediaUrl('user');
    }
    
    /**
     * Get all the zones and its tables.
     */
    public function getAllTables()
    {
        $zones = Zone::with([
                            'tables:id,table_no,seat,zone_id,status,order_id',
                            'tables.orderTables' => function ($query) {
                                $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled'])
                                    ->select('id', 'table_id', 'pax', 'user_id', 'status', 'order_id', 'created_at');
                            },
                            'tables.orderTables.order:id,pax,customer_id,amount,voucher_id,total_amount,status,created_at',
                            // 'tables.orderTables.order.customer:id,full_name',
                            // 'tables.orderTables.order.customer.rewards' => function ($query) {
                            //     $query->where('status', 'Active')->select('id','customer_id', 'ranking_reward_id', 'status');
                            // },
                            // 'tables.orderTables.order.customer.rewards.rankingReward',
                            'tables.orderTables.order.voucher:id,reward_type,discount'
                        ])->get(['id', 'name']);
    
        if ($zones->isEmpty()) {
            return response()->json([]);
        }
    
        $zones = $zones->map(function ($zone) use ($zones){
            $tablesArray = $zone->tables?->map(function ($table) use ($zones) {
                $table->pending_count = $table->orderTables->sum(function ($orderTable) {
                    return $orderTable->order
                            ->orderItems
                            ->where('status', 'Pending Serve')
                            ->sum(function ($orderItem) {
                                return $orderItem->subItems
                                    ->sum(fn ($subItem) => $subItem->item_qty * $orderItem->item_qty - $subItem->serve_qty);
                            });
                });
    
                $currentOrderTable = $table->orderTables->firstWhere('status', '!=', 'Pending Clearance')
                    ?? $table->orderTables->first();
    
                // if ($currentOrderTable && $currentOrderTable->order && $currentOrderTable->order->customer) {
                //     $currentOrderTable->order->customer->image = $currentOrderTable->order->customer->getFirstMediaUrl('customer');
                // }
                
                if ($currentOrderTable && $currentOrderTable->order) unset($currentOrderTable->order->orderItems);

                $table->order = $currentOrderTable->order ?? null;
    
                // Determine if the table is merged
                $table->is_merged = $zones->some(fn ($z) => 
                    $z->tables->some(fn ($t) => 
                        $t->id !== $table->id 
                        && $t->order_id === $table->order_id 
                        && $t->status !== 'Empty Seat'
                    )
                );

                // Unset the orderTables property to clean up the response
                $table->unsetRelation('orderTables');
    
                return $table;
            });
    
            return [
                'name' => $zone->name,
                'tables' => $tablesArray
            ];
        })->filter(fn ($zone) => $zone['tables'] != null);
    
        return response()->json($zones);
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
     * Check in to table.
     */
    public function checkInTable(Request $request)
    {
        try {
            $validatedData = $request->validate(
                [
                    'tables' => 'required|array',
                    'pax' => 'required|string|max:255',
                ], 
                [
                    'required' => 'This field is required.',
                    'string' => 'This field must be a string.',
                    'max' => 'This field must not exceed 255 characters.',
                    'array' => 'This field must be an array.',
                ]
            );

            $waiter = $this->authUser;
            
            $tableString = $this->getTableName($validatedData['tables']);
            
            $newOrder = Order::create([
                'order_no' => RunningNumberService::getID('order'),
                'pax' => $validatedData['pax'],
                'user_id' => $waiter->id,
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
                    'user_id' => $waiter->id,
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
                            'assigned_by' => $waiter->full_name,
                            'assigner_image' => $waiter->getFirstMediaUrl('user'),
                        ])
                        ->log("Assigned :properties.waiter_name to serve :properties.table_name.");

            Notification::send(User::all(), new OrderAssignedWaiter($tableString, $waiter->id, $waiter->id));

            return response()->json([
                'status' => 'success',
                'title' => "Customer has been checked-in to $tableString.",
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'title' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            return response()->json([
                'title' => 'Error checking in.',
                'errors' => $e
            ]);
        };
    }
    
    /**
     * Check in customer to table.
     */
    public function checkInCustomer(Request $request)
    {
        try {
            $validatedData = $request->validate(
                [
                    'order_id' => 'required|integer',
                    'customer_uuid' => 'required|string',
                    'tables' => 'required|array',
                    'type' => 'nullable|string',
                ], 
                ['required' => 'This field is required.']
            );

            $order = Order::find($validatedData['order_id']);

            $tableString = $this->getTableName($validatedData['tables']);
            
            if ($order && $order->customer_id && $validatedData['type'] === 'new') {
                $customerName = $order->customer->full_name;

                return response()->json([
                    'title' => 'Another customer is already checked-in to this table',
                    'message' => "This table is currently assigned to $customerName. Would you like to update the check-in to a different customer?",
                    // 'order_id' => $validatedData['order_id'],
                    // 'customer_uuid' => $validatedData['customer_uuid'],
                ], 201);
            }

            $customer = Customer::where('uuid', $validatedData['customer_uuid'])->first();

            $order->update(['customer_id' => $customer->id]);

            return response()->json([
                'status' => 'success',
                'message' => $validatedData['type'] === 'update'
                        ? "Check-in customer from $tableString has been updated."
                        : "Customer has been checked-in to $tableString."
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'title' => 'QR code not found...',
                'message' => "We couldn't locate this QR code in our system. Try scanning again or use a different one.",
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            return response()->json([
                'title' => 'QR code not found...',
                'message' => "We couldn't locate this QR code in our system. Try scanning again or use a different one.",
                'errors' => $e
            ]);
        };
    }
    
    // /**
    //  * Check in customer to table.
    //  */
    // public function getOrderSummary(Request $request)
    // {
    //     // Fetch only the main order tables first, selecting only necessary columns
    //     $orderTables = OrderTable::select('id', 'table_id', 'status', 'updated_at', 'order_id')
    //                                 ->with('table:id,table_no,status')
    //                                 ->where('table_id', $request->id)
    //                                 ->orderByDesc('updated_at')
    //                                 ->get();

    //     // Find the first non-pending clearance table
    //     $currentOrderTable = $orderTables->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled'])
    //                                         ->firstWhere('status', '!=', 'Pending Clearance') 
    //                         ?? $orderTables->first();

    //     if (!!$currentOrderTable) {
    //         // Lazy load relationships only for the selected table
    //         $currentOrderTable->load([
    //             'order:id,pax,customer_id,amount,voucher_id,total_amount,status,created_at',
    //             'order.customer:id,full_name',
    //             'order.customer.rewards' => function ($query) {
    //                 $query->where('status', 'Active')->select('id','customer_id', 'ranking_reward_id', 'status');
    //             },
    //             'order.customer.rewards.rankingReward',
    //             'order.voucher:id,reward_type,discount'
    //         ]);

    //         // if ($currentOrderTable->order->orderItems) {
    //         //     foreach ($currentOrderTable->order->orderItems as $orderItem) {
    //         //         $orderItem->product->image = $orderItem->product->getFirstMediaUrl('product');
    //         //         $orderItem->handledBy->image = $orderItem->handledBy->getFirstMediaUrl('user');
    //         //         $orderItem->product->discount_item = $orderItem->product->discountSummary($orderItem->product->discount_id)?->first();
    //         //         unset($orderItem->product->discountItems);
    //         //     }
    //         // }

    //         if ($currentOrderTable->order->customer) {
    //             $currentOrderTable->order->customer->image = $currentOrderTable->order->customer->getFirstMediaUrl('customer');
    //         };

    //         // $currentOrderTable->order->pending_count = $currentOrderTable->order->orderItems()->where('status', 'Pending Serve')->count();

    //         // dd($currentOrderTable->order->orderItems->where('status', 'Pending Serve')->count());
            
    //         $data = [
    //             'currentOrderTable' => $currentOrderTable->table,
    //             'order' => $currentOrderTable->order
    //         ];
    //     } else {
    //         $data = null;
    //     }

    //     return response()->json($data);
    // }

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
     * Get pending serve items query with relationships
     */
    private function getPendingServeItemsQuery($tableId)
    {
        return OrderTable::where('table_id', $tableId)
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
            ->orderByDesc('id');
    }
    
    /**
     * Get all pending serve items of the selected order.
     */
    public function getPendingServeItems(Request $request)
    {
        $currentTable = $this->getPendingServeItemsQuery($request->id)->get();

        $pendingServeItems = collect();

        $currentTable->each(function ($table) use (&$pendingServeItems) {
            if ($table->order) {
                if ($table->order->customer_id) {
                    $table->order->customer->image = $table->order->customer->getFirstMediaUrl('customer');
                }

                foreach ($table->order->orderItems as $orderItem) {
                    $orderItem->image = $orderItem->product->getFirstMediaUrl('product');
                    $pendingServeItems->push($orderItem);
                }
            }
        });
    
        return response()->json($pendingServeItems);
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
     * Serve order item.
     */
    public function serveOrderItem(Request $request)
    {        
        if (isset($id)) {
            $orderItem = OrderItem::with(['subItems', 'product', 'keepHistory'])->find($request->order_item_id);
            $subItems = $orderItem->subItems;
            
            if ($subItems) {
                $totalItemQty = 0;
                $totalServedQty = 0;
                $hasServeQty = false;

                foreach ($subItems as $key => $item) {
                    foreach ($request->items as $key => $updated_item) {
                        if ($item['id'] === $item['serve_qty'] && $item['serve_qty'] !== ($item['item_qty'] * $$orderItem->item_qty)) {
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
        
        $currentTable = $this->getPendingServeItemsQuery($request->table_id)->get();

        $pendingServeItems = collect();

        $currentTable->each(function ($table) use (&$pendingServeItems) {
            if ($table->order) {
                if ($table->order->customer_id) {
                    $table->order->customer->image = $table->order->customer->getFirstMediaUrl('customer');
                }

                foreach ($table->order->orderItems as $orderItem) {
                    $orderItem->image = $orderItem->product->getFirstMediaUrl('product');
                    $pendingServeItems->push($orderItem);
                }
            }
        });
    
        return response()->json($pendingServeItems);
    }

    /**
     * Place order items.
     */
    public function placeOrderItem(Request $request)
    {
        $orderItems = $request->items;
        $validatedOrderItems = [];
        $allItemErrors = [];

        $currentOrder = Order::select('id', 'pax', 'amount', 'customer_id', 'user_id', 'status')
                                    ->with('orderTable:id,table_id,status,order_id')
                                    ->find($request->order_id);

        $addNewOrder = $currentOrder->status === 'Order Completed' && $currentOrder->orderTable->every(fn ($table) => $table->status === 'Pending Clearance');
        $tablesArray = $currentOrder->orderTable->map(fn ($table) => $table->table_id)->toArray();

        $tableString = $this->getTableName($tablesArray);

        $waiter = $this->authUser;
        $waiter->image = $waiter->getFirstMediaUrl('user');

        foreach ($orderItems as $index => $item) {
            $rules = $item['item_type'] === 'normal'
                    ? ['item_qty' => 'required|integer']
                    : ['return_qty' => 'required|integer'];

            $requestMessages = $item['item_type'] === 'normal'
                    ? ['item_qty.required' => 'This field is required.', 'item_qty.integer' => 'This field must be an integer.']
                    : ['return_qty.required' => 'This field is required.', 'return_qty.integer' => 'This field must be an integer.'];

            // Validate order items data
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
                $validatedOrderItems[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) {
            return response()->json([
                'title' => 'Error placing order',
                'errors' => $allItemErrors
            ], 422);
        }

        if (count($validatedOrderItems) > 0) {
            if ($addNewOrder) {
                $newOrder = Order::create([
                    'order_no' => RunningNumberService::getID('order'),
                    'pax' => $currentOrder->pax,
                    'user_id' => $currentOrder->user_id,
                    'customer_id' => $currentOrder->customer_id,
                    'amount' => 0.00,
                    'total_amount' => 0.00,
                    'status' => 'Pending Serve',
                ]);
        
                foreach ($tablesArray as $selectedTable) {
                    $table = Table::find($selectedTable);
                    $table->update([
                        'status' => 'Pending Order',
                        'order_id' => $newOrder->id
                    ]);
            
                    OrderTable::create([
                        'table_id' => $selectedTable,
                        'pax' => $currentOrder->pax,
                        'user_id' => $waiter->id,
                        'status' => 'Pending Order',
                        'order_id' => $newOrder->id
                    ]);
                }
                $newOrder->refresh();
            }

            $temp = 0.00;
            $totalDiscountedAmount = 0.00;

            foreach ($orderItems as $key => $item) {
                $status = $item['action_type'] === 'now' ? 'Served' : 'Pending Serve'; 

                if ($item['item_type'] === 'normal') {
                    $product = Product::select('id', 'discount_id')->find($item['product_id']);
    
                    $originalItemAmount = $item['price'] * $item['item_qty'];
                    $currentProductDiscount = $product->discountSummary($product->discount_id)?->first();
                    $newItemAmount = round($currentProductDiscount ? $currentProductDiscount['price_after'] * $item['item_qty'] : $originalItemAmount, 2);
    
                    $new_order_item = OrderItem::create([
                        'order_id' => $addNewOrder ? $newOrder->id : $request->order_id,
                        'user_id' => $waiter->id,
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
                            
                            $serveQty = $item['action_type'] === 'now' ? $value['qty'] * $item['item_qty'] : 0;
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
                } else if ($item['item_type'] === 'keep') {
                    $keepItem = KeepItem::with(['orderItemSubitem:id,order_item_id,product_item_id', 
                                                            'orderItemSubitem.productItem:id,product_id',
                                                            'orderItemSubitem.productItem.inventoryItem:id,item_name', 
                                                            'orderItemSubitem.orderItem:id'])
                                        ->find($item['id']);
        
                    $newOrderItem = OrderItem::create([
                        'order_id' => $addNewOrder ? $newOrder->id : $request->order_id,
                        'user_id' => $waiter->id,
                        'type' => 'Keep',
                        'product_id' => $keepItem->orderItemSubitem->productItem->product_id,
                        'keep_item_id' => $item['id'],
                        'item_qty' => $item['return_qty'],
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
                        'serve_qty' => $item['return_qty'],
                    ]);
        
                    KeepHistory::create([
                        'keep_item_id' => $item['id'],
                        'order_item_id' => $newOrderItem->id,
                        'qty' => $item['type'] === 'qty' ? round($item['return_qty'], 2) : 0.00,
                        'cm' => $item['type'] === 'cm' ? number_format((float) $keepItem->cm, 2, '.', '') : '0.00',
                        'keep_date' => $keepItem->created_at,
                        'status' => 'Served',
                    ]);
        
                    $currOrder = Order::with(['orderTable.table', 'customer:id,full_name'])->find($addNewOrder ? $newOrder->id : $request->order_id);
        
                    activity()->useLog('return-kept-item')
                                ->performedOn($newOrderItem)
                                ->event('updated')
                                ->withProperties([
                                    'edited_by' => auth()->user()->full_name,
                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                    'item_name' => $keepItem->orderItemSubitem->productItem->inventoryItem->item_name,
                                    'customer_name' => $currOrder->customer->full_name
                                ])
                                ->log(":properties.item_name is returned to :properties.customer_name.");
        
                    if ($item['type'] === 'qty') {
                        $keepItem->update([
                            'qty' => ($keepItem->qty - $item['return_qty']) > 0 ? $keepItem->qty - $item['return_qty'] : 0.00,
                            'status' => ($keepItem->qty - $item['return_qty']) > 0 ? 'Keep' : 'Returned'
                        ]);
                    } else {
                        $keepItem->update([
                            'cm' => 0.00,
                            'status' => 'Returned'
                        ]);
                    }
                }
            }
        
            if ($request->new_voucher_id) {
                $selectedReward = CustomerReward::select('id', 'customer_id', 'ranking_reward_id', 'status')
                                            ->with([
                                                'rankingReward:id,reward_type,discount,free_item,item_qty',
                                                'rankingReward.product:id,product_name',
                                                'rankingReward.product.productItems:id,product_id,inventory_item_id,qty',
                                                'rankingReward.product.productItems.inventoryItem:id,item_name,stock_qty,inventory_id,low_stock_qty,current_kept_amt'
                                            ])
                                            ->findOrFail($request->voucher_id);

                $tierReward = $selectedReward->rankingReward;
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
                    'voucher_id' => in_array($tierReward->reward_type, ['Discount (Amount)', 'Discount (Percentage)']) ? $tierReward->id : null,
                    'total_amount' => $order->amount + $temp,
                    'discount_amount' => $order->discount_amount + $totalDiscountedAmount,
                    'status' => $orderStatus
                ]);
                
                if (in_array($tierReward->reward_type, ['Discount (Amount)', 'Discount (Percentage)'])) {
                    $selectedReward->update(['status' => 'Redeemed']);
                };
                
                // Update all tables associated with this order
                $order->orderTable->each(function ($tab) use ($orderTableStatus) {
                    $tab->table->update(['status' => $orderTableStatus]);
                    $tab->update(['status' => $orderTableStatus]);
                });
            }

            return response()->json([
                'status' => 'success',
                'title' => "Order has been successfully placed."
            ], 201);
        }
    
        return response()->json([
            'status' => 'error',
            'title' => "No items selected to be placed."
        ]);
    }
    
    public function getAllCustomers() 
    {
        $customers = Customer::with([
                                    'rank:id,name',
                                    'keepItems' => function ($query) {
                                        $query->where('status', 'Keep')
                                                ->with([
                                                    'orderItemSubitem.productItem:id,inventory_item_id',
                                                    'orderItemSubitem.productItem.inventoryItem:id,item_name',
                                                    'waiter:id,full_name'
                                                ]);
                                    },
                                    'rewards:id,customer_id,ranking_reward_id,status,updated_at',
                                    'rewards.rankingReward:id,ranking_id,reward_type,min_purchase,discount,min_purchase_amount,bonus_point,free_item,item_qty,updated_at',
                                    'rewards.rankingReward.product:id,product_name'
                                ])
                                ->get()
                                ->each(function ($customer) {
                                    $customer->image = $customer->getFirstMediaUrl('customer');

                                    foreach ($customer->keepItems as $key => $keepItem) {
                                        $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
                                        $keepItem->order_no = $keepItem->orderItemSubitem->orderItem->order['order_no'];
                                        unset($keepItem->orderItemSubitem);
                            
                                        $keepItem->image = $keepItem->orderItemSubitem->productItem 
                                                            ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                                                            : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');
                            
                                        $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
                            
                                    }

                                    return $customer;
                                });

        return response()->json($customers);
    }
    
    // public function getOrderCustomer(Request $request) 
    // {
    //     $customer = Customer::with([
    //                             'rank:id,name',
    //                             'keepItems' => function ($query) {
    //                                 $query->where('status', 'Keep')
    //                                         ->with([
    //                                             'orderItemSubitem.productItem:id,inventory_item_id',
    //                                             'orderItemSubitem.productItem.inventoryItem:id,item_name',
    //                                             'waiter:id,full_name'
    //                                         ]);
    //                             },
    //                             'rewards:id,customer_id,ranking_reward_id,status,updated_at',
    //                             'rewards.rankingReward:id,ranking_id,reward_type,min_purchase,discount,min_purchase_amount,bonus_point,free_item,item_qty,updated_at',
    //                             'rewards.rankingReward.product:id,product_name'
    //                         ])
    //                         ->find($request->id);
        
    //     foreach ($customer->keepItems as $key => $keepItem) {
    //         $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
    //         $keepItem->order_no = $keepItem->orderItemSubitem->orderItem->order['order_no'];
    //         unset($keepItem->orderItemSubitem);

    //         $keepItem->image = $keepItem->orderItemSubitem->productItem 
    //                             ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
    //                             : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

    //         $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');

    //     }

    //     return response()->json($customer);
    // }
    
    public function getTableKeepItems(Request $request) 
    {
        $orderTables = OrderTable::with([
                                        'table',
                                        'order.payment.customer',
                                        'order.orderItems' => fn($query) => $query->where('status', 'Served')->orWhere('status', 'Pending Serve'),
                                        'order.orderItems.product',
                                        'order.orderItems.subItems.productItem.inventoryItem',
                                        'order.orderItems.subItems.keepItems.oldestKeepHistory' => function ($query) {
                                            $query->where('status', 'Keep');
                                        },
                                        'order.orderItems.keepItem.oldestKeepHistory'  => function ($query) {
                                            $query->where('status', 'Keep');
                                        }
                                    ])
                                    ->where('table_id', $request->id)
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

        return response()->json($uniqueOrders);
    }
}
