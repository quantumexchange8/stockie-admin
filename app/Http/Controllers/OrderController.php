<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderTableRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\IventoryItem;
use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemSubitem;
use App\Models\OrderTable;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\SaleHistory;
use App\Models\StockHistory;
use App\Models\Table;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $zones = Zone::with(['tables', 'tables.orderTables', 'tables.orderTables.order'])
                        ->select('id', 'name')
                        ->get()
                        ->map(function ($zone) {
                            $zone->tables->map(function ($table) {
                                // Find the first order table with a status that is not 'Order Completed'
                                $orderTable = $table->orderTables->first(function ($orderTable) {
                                    return $orderTable->status !== 'Order Completed' && $orderTable->status !== 'Empty Seat' && $orderTable->status !== 'Order Cancelled';
                                });

                                // Filter order tables with the 'reserved' status
                                $reservations = $table->orderTables->filter(function ($orderTable) {
                                    return $orderTable->reservation === 'reserved';
                                })->sortBy(function ($reservation) {
                                    return $reservation->reservation_date;
                                })->map(function ($reservation) {
                                    // Separate reservation date and time
                                    $reservation['reservation_at'] = Carbon::parse($reservation->reservation_date)->format('d/m/Y');
                                    $reservation['reservation_time'] = Carbon::parse($reservation->reservation_date)->format('H:i');
                                    return $reservation;
                                });
    
                                // Assign the found order table as an object to the table's 'order_table' property
                                $table['order_table'] = $orderTable ?? null;

                                // Assign the filtered reservations to the 'reservations' property
                                $table['reservations'] = $reservations->isNotEmpty() ? $reservations->values() : null;
    
                                // Optionally remove the orderTables relationship if you don't need it in the response
                                unset($table->orderTables);
    
                                return $table;
                            });

                            return [
                                'text' => $zone->name,
                                'value' => $zone->id,
                                'tables' => $zone->tables
                            ];
                        });

        // $waiters = Waiter::orderBy('id')->get();
        $waiters = User::where('role', 'waiter')->orderBy('id')->get();

        $orders = Order::with([
                            'orderItems:id,order_id,product_id,item_qty,amount,point_earned,status', 
                            'orderItems.product:id,product_name', 
                            'orderTable:id,table_id,order_id', 
                            'orderTable.table:id,table_no', 
                            'waiter:id,name',
                            'customer:id,point'
                        ])
                        ->orderByDesc('id')
                        ->get()
                        ->filter(function ($order) {
                            return $order->status === 'Order Completed' || $order->status === 'Order Cancelled';
                        })
                        ->values();
        
        // Get the flashed messages from the session
        // $message = $request->session()->get('message');

        return Inertia::render('Order/Order', [
            // 'message' => $message ?? [],
            'zones' => $zones,
            'waiters' => $waiters,
            'orders' => $orders,
        ]);
    }

    /**
     * Store new order table.
     */
    public function storeOrderTable(OrderTableRequest $request)
    {
        $validatedData = $request->validated();

        if (!$request->reservation) {
            $latestOrderId = Order::latest()->first();
    
            $newOrder = Order::create([
                'order_no' => $latestOrderId ? str_pad((int)$latestOrderId->order_no + 1, 3, "0", STR_PAD_LEFT) : '000',
                'pax' => $validatedData['pax'],
                'user_id' => $validatedData['assigned_waiter'],
                'amount' => 0.00,
                'total_amount' => 0.00,
                'status' => 'Pending Serve',
            ]);
    
            $table = Table::find($validatedData['table_id']);
    
            $table->update([
                'status' => $validatedData['status'],
                'order_id' => $newOrder->id
            ]);
        }

        OrderTable::create([
            'table_id' => $validatedData['table_id'],
            'reservation' => $request->reservation ? 'reserved' : null,
            'pax' => $validatedData['pax'],
            'user_id' => $request->user_id,
            'status' => $validatedData['status'],
            'reservation_date' => $validatedData['reservation_date'],
            'order_id' => $request->reservation ? null : $newOrder->id
        ]);

        // $message = [ 
        //     'severity' => 'success', 
        //     'summary' => $request->reservation 
        //                     ? "Reservation has been made to '$request->table_no'."
        //                     : "You've successfully check in customer to '$request->table_no'."
        // ];

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
    public function storeOrderItem(Request $request)
    {
        $orderItems = $request->items;
        $validatedOrderItems = [];
        $allItemErrors = [];
        $serveNow = $request->action_type === 'now' ? true : false;

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
            $temp = 0;

            foreach ($orderItems as $key => $item) {
                $status = $serveNow ? 'Served' : 'Pending Serve'; 
                $new_order_item = OrderItem::create([
                    'order_id' => $request->order_id,
                    'user_id' => $request->user_id,
                    'type' => 'Normal',
                    'product_id' => $item['product_id'],
                    'item_qty' => $item['item_qty'],
                    'amount' => round($item['price'] * $item['item_qty'], 2),
                    'point_earned' => $item['point'] * $item['item_qty'],
                    'status' => $status,
                ]);

                if (count($item['product_items']) > 0) {
                    $temp += round($item['price'] * $item['item_qty'], 2);
                    
                    foreach ($item['product_items'] as $key => $value) {
                        $productItem = ProductItem::with(['inventoryItem:id,item_name,stock_qty,item_cat_id', 'inventoryItem.itemCategory:id,low_stock_qty'])->find($value['id']);
                        $inventoryItem = $productItem->inventoryItem;
    
                        // Deduct stock
                        $stockToBeSold = $value['qty'] * $item['item_qty'];
                        $oldStockQty = $inventoryItem->stock_qty;
                        $newStockQty = $oldStockQty - $stockToBeSold;

                        $newStatus = match(true) {
                            $newStockQty === 0 => 'Out of stock',
                            $newStockQty <= $inventoryItem->itemCategory['low_stock_qty'] => 'Low in stock',
                            default => 'In stock'
                        };
    
                        $inventoryItem->update([
                            'stock_qty' => $newStockQty,
                            'status' => $newStatus
                        ]);
                        $inventoryItem->refresh();
    
                        StockHistory::create([
                            'inventory_id' => $inventoryItem->id,
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
                    }
                }
            }

            $order = Order::with('orderTable')->find($request->order_id);
            $table = Table::find($request->table_id);
            // $tableStatus = $orderItems > 0 ? 'Order Placed' : 'Pending Order';

            // $order->update([
            //     'amount' => $order->amount + $temp,
            //     'total_amount' => $order->total_amount + $temp,
            //     'status' => $serveNow ? 'Served' : 'Pending Serve'
            // ]);
            // $table->update(['status' => $tableStatus]);
            // $order->orderTable->update(['status' => $tableStatus]);

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
                    'total_amount' => $order->total_amount + $temp,
                    'status' => $orderStatus
                ]);
                $table->update(['status' => $orderTableStatus]);
                $order->orderTable->update(['status' => $orderTableStatus]);
            }
        }

        return redirect()->back();
    }

    /**
     * Get all products.
     */
    public function getAllProducts()
    {
        $products = Product::with(['productItems', 'category:id,name'])
                            ->orderBy('id')
                            ->get()
                            ->map(function ($product) {
                                $product_items = $product->productItems;
                                $minStockCount = 0;

                                if (count($product_items) > 0) {
                                    $stockCountArr = [];

                                    foreach ($product_items as $key => $value) {
                                        $inventory_item = IventoryItem::select('stock_qty')->find($value['inventory_item_id']);

                                        $stockQty = $inventory_item->stock_qty;
                                        
                                        $stockCount = (int)round($stockQty / (int)$value['qty']);
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
    public function getOrderWithItems(string $id)
    {
        $order = Order::with([
                            'orderItems', 
                            'orderItems.product', 
                            'orderItems.product.productItems',
                            'orderItems.product.productItems.inventoryItem',
                            'orderItems.handledBy:id,name',
                            'orderItems.subItems',
                            'orderItems.subItems.keepItems',
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
                            'waiter:id,name',
                            'customer:id,full_name,email,phone,point'
                        ])
                        ->find($id);

        return response()->json($order);
    }

    /**
     * Cancel the order along with its items.
     */
    public function cancelOrder(string $id)
    {
        $existingOrder = Order::with([
                                    'orderItems', 
                                    'orderItems.subItems', 
                                    'orderItems.subItems.productItem', 
                                    'orderItems.subItems.productItem.inventoryItem', 
                                    'orderItems.subItems.productItem.inventoryItem.itemCategory', 
                                    'orderTable',
                                    'orderTable.table',
                                ])
                                ->find($id);

        $table = $existingOrder->orderTable->table;

        // $message = [ 
        //     'severity' => 'error', 
        //     'summary' => 'Error cancelling order.'
        // ];

        if ($existingOrder) {
            foreach ($existingOrder->orderItems as $item) {
                if ($item['status'] === 'Pending Serve') {
                    foreach ($item->subItems as $subItem) {
                        $inventoryItem = $subItem->productItem->inventoryItem;
                        $itemCategory = $inventoryItem->itemCategory;
                        
                        $qtySold = $subItem['serve_qty'];
                        $restoredQty = $item['item_qty'] * $subItem['item_qty'] - $qtySold;
                        $oldStockQty = $inventoryItem->stock_qty;
                        $newStockQty = $oldStockQty + $restoredQty;
    
                        // Update inventory with restored stock
                        $newStatus = match(true) {
                            $newStockQty === 0 => 'Out of stock',
                            $newStockQty <= $itemCategory['low_stock_qty'] => 'Low in stock',
                            default => 'In stock'
                        };

                        $inventoryItem->update([
                            'stock_qty' => $newStockQty,
                            'status' => $newStatus
                        ]);
                        $inventoryItem->refresh();
    
                        if ($restoredQty > 0) {
                            StockHistory::create([
                                'inventory_id' => $inventoryItem->id,
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

            // $message = [ 
            //     'severity' => 'success', 
            //     'summary' => 'Selected order has been cancelled successfully.'
            // ];

            $table->update([
                'status' => 'Empty Seat',
                'order_id' => null
            ]);
            $existingOrder->orderTable->update(['status' => 'Order Cancelled']);
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
                    'cm' => $keepHistory->cm,
                    'keep_date' => $keepItem->created_at,
                    'status' => 'Served',
                ]);
            }

            $order = Order::with(['orderTable', 'orderItems'])->find($orderItem->order_id);
            
            if ($order) {
                $table = Table::find($order->orderTable['table_id']);
                $orderTable = $order->orderTable;
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
                
                $order->update(['status' => $orderStatus]);
                $table->update(['status' => $orderTableStatus]);
                $orderTable->update(['status' => $orderTableStatus]);
            }
        }

        return redirect()->back();
    }
    
    /**
     * Get all zones along with its tables and order.
     */
    public function getAllZones()
    {
        $zones = Zone::with(['tables', 'tables.orderTables', 'tables.orderTables.order'])
                        ->select('id', 'name')
                        ->get()
                        ->map(function ($zone) {
                            $zone->tables->map(function ($table) {
                                // Find the first order table with a status that is not 'Order Completed'
                                $orderTable = $table->orderTables->first(function ($orderTable) {
                                    return $orderTable->status !== 'Order Completed' && $orderTable->status !== 'Empty Seat' && $orderTable->status !== 'Order Cancelled';
                                });

                                // Filter order tables with the 'reserved' status
                                $reservations = $table->orderTables->filter(function ($orderTable) {
                                    return $orderTable->reservation === 'reserved';
                                })->sortBy(function ($reservation) {
                                    return $reservation->reservation_date;
                                })->map(function ($reservation) {
                                    // Separate reservation date and time
                                    $reservation['reservation_at'] = Carbon::parse($reservation->reservation_date)->format('d/m/Y');
                                    $reservation['reservation_time'] = Carbon::parse($reservation->reservation_date)->format('H:i');
                                    return $reservation;
                                });
    
                                // Assign the found order table as an object to the table's 'order_table' property
                                $table['order_table'] = $orderTable ?? null;

                                // Assign the filtered reservations to the 'reservations' property
                                $table['reservations'] = $reservations->isNotEmpty() ? $reservations->values() : null;
    
                                // Optionally remove the orderTables relationship if you don't need it in the response
                                unset($table->orderTables);
    
                                return $table;
                            });

                            return [
                                'text' => $zone->name,
                                'value' => $zone->id,
                                'tables' => $zone->tables
                            ];
                        });
                            
        return response()->json($zones);
    }

    /**
     * Update order status to completed once all order items are served.
     */
    public function updateOrderStatus(Request $request, string $id)
    {
        if (isset($id)) {
            $order = Order::with([
                                'orderTable', 
                                'orderItems' => function ($query) {
                                    $query->where('status', 'Served');
                                }
                            ])->find($id);
            $table = Table::find($order->orderTable['table_id']);
            $customer = Customer::find($request->customer_id);
            $taxes = Setting::whereIn('name', ['SST', 'Service Tax'])->pluck('percentage', 'name');
            $totalTaxPercentage = ($taxes['SST'] ?? 0) + ($taxes['Service Tax'] ?? 0);

            if ($request->action_type === 'complete') {
                if ($order->status === 'Order Served') {
                    $order->update(['status' => 'Order Completed']);
                }
    
                if ($order->status === 'Order Completed' && $order->orderTable['status'] === 'All Order Served') {
                    if (count($order->orderItems) > 0) {
                        foreach ($order->orderItems as $item) {
                            if ($item['type'] === 'Normal') {
                                SaleHistory::create([
                                    'product_id' => $item['product_id'],
                                    'total_price' => $item['amount'],
                                    'qty' => (int) $item['item_qty']
                                ]);
                            }
                        }
                    }

                    if ($customer) {
                        $accumulatedPoints = array_reduce($order->orderItems->toArray(), fn($totalPoint, $item) => $totalPoint + $item['point_earned'], 0);

                        // Add the accumulated points earned from the order to the customer
                        $customer->update(['point' => $customer['point'] + $accumulatedPoints]);
                    }
                    
                    $order->update(['total_amount' => round($order->total_amount * (1 + $totalTaxPercentage / 100), 2)]);
                    $table->update(['status' => 'Pending Clearance']);
                    $order->orderTable->update(['status' => 'Pending Clearance']);
                }
            } 
            
            if ($request->action_type === 'clear') {
                $table->update([
                    'status' => 'Empty Seat',
                    'order_id' => null
                ]);
                $order->orderTable->update(['status' => 'Order Completed']);
            }
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
            $query->whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                                    ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                                        $subQuery->whereDate('created_at', '<=', $dateFilter[1]);
                                    });
        }

        $data = $query->with([
                            'orderItems:id,order_id,product_id,item_qty,amount,point_earned,status', 
                            'orderItems.product:id,product_name', 
                            'orderTable:id,table_id,order_id', 
                            'orderTable.table:id,table_no', 
                            'waiter:id,name',
                            'customer:id,point'
                        ])
                        ->orderBy('id', 'desc')
                        ->get()
                        ->filter(function ($order) {
                            return $order->status === 'Order Completed' || $order->status === 'Order Cancelled';
                        })
                        ->values();

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
            $order = Order::with(['orderItems', 'orderItems.product', 'orderTable', 'orderTable.table'])->find($id);
            $orderItems = $order->orderItems;
            $orderTable = $order->orderTable;
            $table = $orderTable->table;
            
            if ($orderItems) {
                $cancelledAmount = 0;

                foreach ($orderItems as $key => $item) {
                    foreach ($request->items as $key => $updated_item) {
                        if ($item['id'] === $updated_item['order_item_id']) {
                            // Restore stock for each product item
                            $subItems = OrderItemSubitem::where('order_item_id', $item['id'])->get();
                                                
                            foreach ($subItems as $subItem) {
                                $productItem = ProductItem::with(['inventoryItem', 'inventoryItem.itemCategory'])->find($subItem->product_item_id);
                                $inventoryItem = $productItem->inventoryItem;
                                
                                // $qtySold = $subItem['serve_qty'];
                                $restoredQty = $updated_item['remove_qty'] * $subItem['item_qty'];
                                $oldStockQty = $inventoryItem->stock_qty;
                                $newStockQty = $oldStockQty + $restoredQty;

                                // Update inventory with restored stock
                                $newStatus = match(true) {
                                    $newStockQty === 0 => 'Out of stock',
                                    $newStockQty <= $inventoryItem->itemCategory['low_stock_qty'] => 'Low in stock',
                                    default => 'In stock'
                                };
            
                                $inventoryItem->update([
                                    'stock_qty' => $newStockQty,
                                    'status' => $newStatus
                                ]);
                                $inventoryItem->refresh();

                                StockHistory::create([
                                    'inventory_id' => $inventoryItem->id,
                                    'inventory_item' => $inventoryItem->item_name,
                                    'old_stock' => $oldStockQty,
                                    'in' => $restoredQty,
                                    'out' => 0,
                                    'current_stock' => $inventoryItem->stock_qty,
                                ]);
                            }

                            $balanceQty = $item['item_qty'] - $updated_item['remove_qty'];
                            $cancelledPoints = $updated_item['remove_qty'] * $item->product['point'];
                            
                            if ((int)$balanceQty > 0) {
                                $cancelledAmount += ($item['amount'] / $item['item_qty']) * $updated_item['remove_qty'];
                                
                                $item->update([
                                    'item_qty' => $balanceQty,
                                    'amount' => ($item['amount'] / $item['item_qty']) * $balanceQty,
                                    'point_earned' => $item['point_earned'] > 0 ? $item['point_earned'] - $cancelledPoints : 0,
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
                $table->update(['status' => $orderTableStatus]);
                $orderTable->update(['status' => $orderTableStatus]);
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
            'expired_from' => 'nullable|date_format:Y-m-d',
            'expired_to' => 'nullable|date_format:Y-m-d',
        ];
        $requestMessages = [
            'order_item_subitem_id.required' => 'This field is required.',
            'order_item_subitem_id.integer' => 'This field must be an integer.',
            'amount.required' => 'This field is required.',
            'amount.decimal' => 'This field must be a decimal number.',
            'remark.string' => 'This field must be an string.',
            'expired_from.date_format' => 'This field must be in a date format: Y-m-d.',
            'expired_to.date_format' => 'This field must be in a date format: Y-m-d.',
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
            return redirect()->back()->withErrors($allItemErrors)->withInput();
        }

        if (count($validatedItems) > 0) {
            $order = Order::with(['orderTable', 'orderItems', 'orderItems.subItems', 'orderItems.product'])->find($request->order_id);
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
                                            'qty' => $reqItem['type'] === 'qty' ? round($item['amount'], 2) : 0.00,
                                            'cm' => $reqItem['type'] === 'cm' ? round($item['amount'], 2) : 0.00,
                                            'keep_date' => $keepItem->updated_at,
                                            'status' => 'Keep',
                                        ]);
                                    } else {
                                        $newKeep = KeepItem::create([
                                            'customer_id' => $request->customer_id,
                                            'order_item_subitem_id' => $item['order_item_subitem_id'],
                                            'qty' => $reqItem['type'] === 'qty' ? $item['amount'] : 0,
                                            'cm' => $reqItem['type'] === 'cm' ? $item['amount'] : 0,
                                            'remark' => $item['remark'] ?: '',
                                            'user_id' => $request->user_id,
                                            'status' => 'Keep',
                                            'expired_from' => $item['expired_from'],
                                            'expired_to' => $item['expired_to'],
                                        ]);
            
                                        KeepHistory::create([
                                            'keep_item_id' => $newKeep->id,
                                            'qty' => $reqItem['type'] === 'qty' ? round($item['amount'], 2) : 0.00,
                                            'cm' => $reqItem['type'] === 'cm' ? round($item['amount'], 2) : 0.00,
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
            $order->refresh();

            if ($order) {
                $table = Table::find($order->orderTable['table_id']);
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
                $table->update(['status' => $orderTableStatus]);
                $order->orderTable->update(['status' => $orderTableStatus]);
            }
        }

        return redirect()->back();
    }

    /**
     * Get the order's customer.
     */
    public function getCustomerDetails(string $id) 
    {
        $customer = Customer::with([
                                'keepItems' => function ($query) {
                                    $query->where('status', 'Keep')
                                            ->with([
                                                'orderItemSubitem.productItem:id,inventory_item_id',
                                                'orderItemSubitem.productItem.inventoryItem:id,item_name',
                                                'waiters:id,name'
                                            ]);
                                }
                            ])
                            ->find($id);

        
        foreach ($customer->keepItems as $key => $keepItem) {
            $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
            unset($keepItem->orderItemSubitem);
        }

        return response()->json($customer);
    }

    /**
     * Get the customer's keep histories.
     */
    public function getCustomerKeepHistories(string $id) 
    {
        $keepHistories = KeepHistory::with([
                                        'keepItem', 
                                        'keepItem.orderItemSubitem', 
                                        'keepItem.orderItemSubitem.productItem:id,inventory_item_id', 
                                        'keepItem.orderItemSubitem.productItem.inventoryItem:id,item_name', 
                                        'keepItem.waiters:id,name'
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
            $keepItem = KeepItem::with(['orderItemSubitem:id,order_item_id,product_item_id', 'orderItemSubitem.productItem:id,product_id', 'orderItemSubitem.orderItem:id'])->find($id);

            $newOrderItem = OrderItem::create([
                'order_id' => $request->order_id,
                'user_id' => $request->user_id,
                'type' => 'Keep',
                'product_id' => $keepItem->orderItemSubitem->productItem->product_id,
                'keep_item_id' => $id,
                'item_qty' => $request->return_qty,
                'amount' => 0,
                'point_earned' => 0,
                'status' => 'Pending Serve',
            ]);
            
            OrderItemSubitem::create([
                'order_item_id' => $newOrderItem->id,
                'product_item_id' => $keepItem->orderItemSubitem->productItem->id,
                'item_qty' => 1,
                'serve_qty' => 0,
            ]);

            KeepHistory::create([
                'keep_item_id' => $id,
                'order_item_id' => $newOrderItem->id,
                'qty' => $request->type === 'qty' ? round($request->return_qty, 2) : 0.00,
                'cm' => $request->type === 'cm' ? round($keepItem->cm, 2) : 0.00,
                'keep_date' => $keepItem->created_at,
                'status' => 'Returned',
            ]);

            $order = Order::with(['orderTable', 'orderTable.table'])->find($request->order_id);

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
            $order->orderTable->update(['status' => 'Order Placed']);
            $order->orderTable->table->update(['status' => 'Order Placed']);
        }

        return redirect()->back();
    }

}
