<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderTableRequest;
use App\Models\IventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemSubitem;
use App\Models\OrderTable;
use App\Models\Product;
use App\Models\Table;
use App\Models\Waiter;
use Illuminate\Http\Request;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
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
                                    return $orderTable->status !== 'Order Completed' && $orderTable->status !== 'Empty Seat';
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

        $waiters = Waiter::orderBy('id')->get();

        $orders = Order::with(['orderTable:id,table_id', 'orderTable.table:id,table_no', 'waiter:id,name'])
                        ->orderByDesc('id')
                        ->get()
                        ->filter(function ($order) {
                            return $order->status === 'Order Completed' || $order->status === 'Order Cancelled';
                        })
                        ->values();

        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        return Inertia::render('Order/Order', [
            'message' => $message ?? [],
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

        $latestOrderId = Order::latest()->first();

        $newOrder = Order::create([
            'order_no' => str_pad((int)$latestOrderId->order_no + 1, 3, "0", STR_PAD_LEFT),
            'pax' => $validatedData['pax'],
            'waiter_id' => $validatedData['waiter_id'],
            'total_amount' => 0.00,
            'status' => 'Pending Serve',
        ]);

        $table = Table::find($validatedData['table_id']);

        $table->update(['status' => $validatedData['status']]);

        OrderTable::create([
            'table_id' => $validatedData['table_id'],
            'reservation' => $request->reservation ? 'reserved' : null,
            'pax' => $validatedData['pax'],
            'waiter_id' => $validatedData['waiter_id'],
            'status' => $validatedData['status'],
            'reservation_date' => $validatedData['reservation_date'],
            'order_id' => $newOrder->id
        ]);
        
        $summary = $request->reservation 
            ? "Reservation has been made to '$request->table_no'."
            : "You've successfully check in customer to '$request->table_no'.";

        $message = [ 
            'severity' => 'success', 
            'summary' => $summary
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    /**
     * Update reserved order table's details.
     */
    public function updateReservation(OrderTableRequest $request, string $id)
    {
        $validatedData = $request->validated();
        
        if (!isset($id)) {
            $severity = 'error';
            $summary = "No reservation id found.";
        }

        if (isset($id)) {
            $reservation = OrderTable::find($id);
            
            if ($reservation) {
                $reservation->update([
                    'table_id'=>$validatedData['table_id'],
                    'reservation' => $request->reservation ? 'reserved' : null,
                    'pax' => $validatedData['pax'],
                    'waiter_id' => $validatedData['waiter_id'],
                    'status' => $validatedData['status'],
                    'reservation_date' => $validatedData['reservation_date'],
                    'order_id' => $validatedData['order_id']
                ]);

                $severity = 'success';
                $summary = "Changes saved";
            } else {
                $severity = 'error';
                $summary = "No reservation found.";
            }
        }

        $message = [ 
            'severity' => $severity, 
            'summary' => $summary
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    /**
     * Delete reserved order table.
     */
    public function deleteReservation(Request $request, string $id)
    {
        $existingReservation = OrderTable::find($id);

        $message = [ 
            'severity' => 'error', 
            'summary' => 'No reservation found.'
        ];

        if ($existingReservation) {
            $existingReservation->delete();

            $message = [ 
                'severity' => 'success', 
                'summary' => 'Selected reservation has been deleted successfully.'
            ];
        }
        
        return Redirect::route('orders')->with(['message' => $message]);
    }

    /**
     * Store new item into order.
     */
    public function storeOrderItem(Request $request)
    {
        $orderItems = $request->items;
        $validatedOrderItems = [];
        $allItemErrors = [];

        foreach ($orderItems as $index => $item) {
            $rules = ['item_qty' => 'nullable|integer'];
            $requestMessages = ['item_qty.integer' => 'This field must be an integer.'];

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

        $severity = 'warn';
        $summary = "No product has been added to $request->table_no order.";

        if (count($validatedOrderItems) > 0) {
            $temp = 0;

            foreach ($orderItems as $key => $item) {
                $new_order_item = OrderItem::create([
                    'order_id' => $request->order_id,
                    'user_id' => $request->user_id,
                    'type' => 'Normal',
                    'product_id' => $item['product_id'],
                    'item_qty' => $item['item_qty'],
                    'amount' => round($item['price'] * $item['item_qty'], 2),
                    // 'point_earned' => $item['point'] * $item['item_qty'], should add the point earned only after served
                    'status' => 'Pending Serve',
                ]);

                if (count($item['product_items']) > 0) {
                    $temp += round($item['price'] * $item['item_qty'], 2);
                    
                    foreach ($item['product_items'] as $key => $value) {
                        OrderItemSubitem::create([
                            'order_item_id' => $new_order_item->id,
                            'product_item_id' => $value['id'],
                            'item_qty' => $value['qty'],
                            'serve_qty' => 0,
                        ]);
                    }
                }
            }

            $severity = 'success';
            $summary = "Product has been added to $request->table_no order.";

            $order = Order::with('orderTable')->find($request->order_id);

            $order->update([
                'amount' => $order->total_amount + $temp,
                'total_amount' => $order->total_amount + $temp,
            ]);

            $table = Table::find($request->table_id);
            $table->update(['status' => $orderItems > 0 ? 'Order Placed' : 'Pending Order']);

            $order->orderTable->update(['status' => $orderItems > 0 ? 'Order Placed' : 'Pending Order']);
        }

        $message = [ 
            'severity' => $severity, 
            'summary' => $summary
        ];

        return redirect()->back()->with(['message' => $message]);
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
                            'orderItems.orderedBy:id,name',
                            'orderItems.subItems'
                        ])
                        ->find($id);

        return response()->json($order);
    }

    /**
     * Cancel the order along with its items.
     */
    public function cancelOrder(string $id)
    {
        $existingOrder = Order::with(['orderItems', 'orderTable'])->find($id);
        $table = Table::find($existingOrder->orderTable['table_id']);

        $message = [ 
            'severity' => 'error', 
            'summary' => 'Error cancelling order.'
        ];

        if ($existingOrder) {
            foreach ($existingOrder->orderItems() as $key => $item) {
                $item->update([
                    'status' => 'Cancelled'
                ]);
            }

            $existingOrder->update([
                'status' => 'Order Cancelled'
            ]);

            $message = [ 
                'severity' => 'success', 
                'summary' => 'Selected order has been cancelled successfully.'
            ];

            $table->update(['status' => 'Empty Seat']);
            $existingOrder->orderTable->update(['status' => 'Order Cancelled']);
        }

        return redirect()->back()->with(['message' => $message]);
    }

    /**
     * Update order item's and its sub items' details.
     * Update order table's status.
     */
    public function updateOrderItem(Request $request, string $id)
    {        
        if (isset($id)) {
            $orderItem = OrderItem::with(['subItems'])->find($id);
            $subItems = $orderItem->subItems;
            
            if ($subItems) {
                $totalItemQty = 0;
                $totalServedQty = 0;
                $hasServeQty = false;

                foreach ($subItems as $key => $item) {
                    foreach ($request->items as $key => $updated_item) {
                        if ($item['id'] === $updated_item['sub_item_id']) {
                            $item->update([
                                'serve_qty' => $item['serve_qty'] + $updated_item['serving_qty'],
                            ]);
                            $item->save();

                            $totalItemQty += $item['item_qty'] * $orderItem->item_qty;
                            $totalServedQty += $item['serve_qty'];
                            $hasServeQty = $updated_item['serving_qty'] > 0 ? true : false;
                        }
                    }
                }

                if ($hasServeQty) {
                    $orderItem->update([
                        'point_earned' => $request->point,
                        'status' => $totalServedQty === $totalItemQty ? 'Served' : 'Pending Serve',
                    ]);

                    $orderItem->save();
                }
            }
            
            $orderItem->refresh();

            $order = Order::with(['orderTable', 'orderItems'])->find($orderItem->order_id);
            $table = Table::find($order->orderTable['table_id']);
            $orderTable = $order->orderTable;
            $allOrderItems = $order->orderItems;
            
            if ($order) {
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
                    if ($uniqueStatuses[0] === 'Served') {
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
                                    return $orderTable->status !== 'Order Completed' && $orderTable->status !== 'Empty Seat';
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
            $order = Order::with('orderTable')->find($id);
            $table = Table::find($order->orderTable['table_id']);

            // dd($order->status === 'Order Served' && $order->orderTable['status'] === 'All Order Served', $order->orderTable['status']);
            if ($request->action_type === 'complete') {
                if ($order->status === 'Order Served') {
                    $order->update(['status' => 'Order Completed']);
                }
    
                if ($order->status === 'Order Completed' && $order->orderTable['status'] === 'All Order Served') {
                    $table->update(['status' => 'Pending Clearance']);
                    $order->orderTable->update(['status' => 'Pending Clearance']);
                }
            } 
            
            if ($request->action_type === 'clear') {
                $table->update(['status' => 'Empty Seat']);
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
        
        if ($dateFilter) {
            $dateFilter = array_map(function ($date) {
                                return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
                            }, $dateFilter);

            // Apply the date filter (single date or date range)
            $query = Order::whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                                    ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                                        $subQuery->whereDate('created_at', '<=', $dateFilter[1]);
                                    });
        } else {
            $query = Order::query();
        }


        $data = $query->with(['orderTable:id,table_id', 'orderTable.table:id,table_no', 'waiter:id,name'])
                        ->orderBy('id', 'desc')
                        ->get()
                        ->filter(function ($order) {
                            return $order->status === 'Order Completed' || $order->status === 'Order Cancelled';
                        })
                        ->values();

        return response()->json($data);
    }
}
