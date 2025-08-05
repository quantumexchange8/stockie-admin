<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\OrderTableRequest;
use App\Models\Category;
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
use App\Models\PointHistory;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Ranking;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\ShiftTransaction;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Log;
use Random\Randomizer;

class OrderController extends Controller
{
    protected $authUser;
    public function __construct()
    {
        $this->authUser = User::with('waiterPosition:id,name')->find(Auth::id());
        $this->authUser->image = $this->authUser->getFirstMediaUrl('user');
    }

    private function getReservedTablesId()
    {
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
     * Get all the zones and its tables.
     */
    public function getAllTables()
    {
        // Load reserved tables IDs once (assuming this doesn't change during request)
        $reservedTablesId = $this->getReservedTablesId();

        // Eager load all necessary relationships with optimized queries
        $zones = Zone::with([
            'tables:id,table_no,seat,zone_id,status,order_id,is_locked',
            'tables.orderTables' => function ($query) {
                $query->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided', 'Order Merged'])
                    ->select('id', 'table_id', 'pax', 'user_id', 'status', 'order_id', 'created_at')
                    ->with(['order:id,pax,customer_id,amount,voucher_id,total_amount,status,created_at',
                        'order.orderTable' => function ($q) {
                            $q->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided', 'Order Merged'])
                                ->select('id', 'order_id', 'table_id')
                                ->with('table:id,table_no');
                        },
                        'order.voucher:id,reward_type,discount']);
            }
        ])->get(['id', 'name']);

        if ($zones->isEmpty()) {
            return response()->json([]);
        }

        // Preload all pending order items in a single query
        $orderIds = collect();
        $zones->each(function ($zone) use (&$orderIds) {
            $zone->tables->each(function ($table) use (&$orderIds) {
                $orderIds = $orderIds->merge($table->orderTables->pluck('order_id'));
            });
        });
        
        $pendingItems = DB::table('order_items')
            ->select('order_id', DB::raw('SUM(item_qty * (SELECT COALESCE(SUM(item_qty), 0) FROM order_item_subitems WHERE order_item_subitems.order_item_id = order_items.id AND order_item_subitems.serve_qty = 0)) as pending_count'))
            ->whereIn('order_id', $orderIds->unique()->filter())
            ->where('status', 'Pending Serve')
            ->groupBy('order_id')
            ->get()
            ->keyBy('order_id');

        // Process zones and tables
        $zones = $zones->map(function ($zone) use ($zones, $reservedTablesId, $pendingItems) {
            $tablesArray = $zone->tables?->map(function ($table) use ($zones, $reservedTablesId, $pendingItems) {
                // Calculate pending count from preloaded data
                $table->pending_count = $table->orderTables->sum(function ($orderTable) use ($pendingItems) {
                    return $pendingItems[$orderTable->order_id]->pending_count ?? 0;
                });

                $currentOrderTable = $table->orderTables
                    ->whereNotIn('status', ['Order Completed', 'Empty Seat', 'Order Cancelled', 'Order Voided'])
                    ->firstWhere('status', '!=', 'Pending Clearance') 
                    ?? $table->orderTables->first();

                if ($currentOrderTable && $currentOrderTable->order) {
                    unset($currentOrderTable->order->orderItems);
                }

                $table->order = $currentOrderTable->order ?? null;

                // Determine if the table is merged
                $table->is_merged = $zones->some(fn ($z) => 
                    $z->tables->some(fn ($t) => 
                        $t->id != $table->id &&
                        ($table->order_id ? $t->order_id == $table->order_id : false) &&
                        $t->status !== 'Empty Seat'
                    )
                );

                $table->is_reserved = in_array($table->id, $reservedTablesId);

                // Use preloaded reservations
                $table->reservation = Reservation::whereJsonContains('table_no', ['id' => $table->id])
                                                ->whereIn('status', ['Pending', 'Checked in'])
                                                ->first();

                $table->order_table_names = $table->table_no;

                // Find the first non-pending clearance table
                if ($table->orderTables) {
                    $orderTable = $table->orderTables->firstWhere('status', '!=', 'Pending Clearance') ?? $table->orderTables->first();
                    $table->order_table_names = $orderTable?->order_id
                        ? collect($orderTable->order->orderTable)
                            ->map(fn($ot) => $ot['table']['table_no'] ?? null)
                            ->filter()
                            ->sort()
                            ->implode(', ')
                        : $table->table_no;
                }

                // Clean up
                $table->unsetRelation('orderTables');

                return $table;
            });

            return [
                'name' => $zone->name,
                'tables' => $tablesArray
            ];
        })->filter(fn ($zone) => $zone['tables'] != null);
        
        return response()->json([
            'zones' => $zones,
            'hasOpenedShift' => ShiftTransaction::hasOpenedShift(),
        ]);
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

            Notification::send(User::where('position', 'admin')->get(), new OrderCheckInCustomer($tableString, $waiter->full_name, $waiter->id));

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

            Notification::send(User::where('position', 'admin')->get(), new OrderAssignedWaiter($tableString, $waiter->id, $waiter->id));

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
            ], 422);
        };
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

            $order = Order::with([
                                'customer:id',
                                'customer.rewards:id,customer_id,ranking_reward_id,status',
                            ])
                            ->find($validatedData['order_id']);

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

            if ($order->customer && $customer->id !== $order->customer_id && $order->voucher) {
                $this->removeOrderVoucher($order);
            }

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
            ], 422);
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
                                'productItems.inventoryItem', 
                                'category:id,name', 
                                'commItem' => fn($query) => 
                                    // $query->where('product.commItem.configComms')->where('status', 'Served')
                                    $query->whereHas('configComms')
                                        ->where('status', 'Active')
                                        ->with('configComms'),
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
                                        $inventory_item = $value->inventoryItem;
                                        $stockQty = $inventory_item->stock_qty;
                                        
                                        $stockCount = (int)bcdiv($stockQty, (int)$value['qty']);
                                        array_push($stockCountArr, $stockCount);
                                    }
                                    $minStockCount = min($stockCountArr);
                                }
                                $product['stock_left'] = $minStockCount; 
                                $product['category_name'] = $product->category->name;

                                $configCommItem = $product->commItem;
        
                                if ($configCommItem) {
                                    $commissionType = $configCommItem->configComms->comm_type;
                                    $commissionRate = $configCommItem->configComms->rate;
                                    $commissionAmount = $commissionType === 'Fixed amount per sold product' 
                                            ? $commissionRate * 1
                                            : $product->price * 1 * ($commissionRate / 100);
                                }
                                
                                $product['commission_amount'] = $configCommItem ? round($commissionAmount, 2) : 0.00;
                                
                                unset($product->commItem);
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
            ->whereNotIn('status', ['Order Completed', 'Order Cancelled', 'Order Voided'])
            ->whereHas('order.orderItems', function ($query) {
                $query->where('status', 'Pending Serve');
            })
            ->with([
                'order:id,order_no,created_at,customer_id',
                'order.orderItems' => function ($query) {
                    $query->where('status', 'Pending Serve')
                        ->select('id', 'amount_before_discount', 'amount', 'product_id', 'item_qty', 'order_id', 'user_id', 'status');
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
     * Get all pending serve items thats has been ordered by the user.
     */
    public function getAllPendingServeItems()
    {
        $tables = Table::whereHas('orderTables', function ($query) {
                                $query->whereNotIn('status', ['Order Completed', 'Order Cancelled', 'Order Voided'])
                                        ->whereHas('order.orderItems', function ($subQuery) {
                                            $subQuery->where([
                                                ['status', 'Pending Serve'],
                                                ['user_id', $this->authUser->id]
                                            ]);
                                        });
                            })
                            ->where([
                                ['status', '!=', 'Empty Seat'],
                                ['state', 'active']
                            ])
                            ->with([
                                'orderTables:id,table_id,order_id,status',
                                'orderTables' => function ($query) {
                                    $query->whereNotIn('status', ['Order Completed', 'Order Cancelled', 'Order Voided']);
                                },
                                'orderTables.order:id,order_no,created_at,customer_id',
                                'orderTables.order.orderItems' => function ($query) {
                                    $query->where([
                                            ['status', 'Pending Serve'],
                                            ['user_id', $this->authUser->id]
                                        ])
                                        ->select('id', 'amount_before_discount', 'amount', 'product_id', 'item_qty', 'order_id', 'user_id', 'status');
                                },
                                'orderTables.order.orderItems.product:bucket,id,price,product_name,point',
                                'orderTables.order.orderItems.subItems:id,item_qty,product_item_id,serve_qty,order_item_id',
                                'orderTables.order.orderItems.subItems.productItem:id,product_id,inventory_item_id',
                                'orderTables.order.orderItems.subItems.productItem.inventoryItem:id,item_name',
                                'orderTables.order.customer:id,full_name'
                            ])
                            ->select('id', 'table_no', 'status', 'state', 'order_id')
                            ->orderByDesc('id')
                            ->get();

        $tables->each(function ($table) {
            $table->orderTables->each(function ($orderTable) {
                if ($orderTable->order) {
                    if ($orderTable->order->customer_id) {
                        $orderTable->order->customer->image = $orderTable->order->customer->getFirstMediaUrl('customer');
                    }

                    foreach ($orderTable->order->orderItems as $orderItem) {
                        $orderItem->image = $orderItem->product->getFirstMediaUrl('product');
                    }
                }
            });
        });

    
        return response()->json($tables);
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
        $orderItem = OrderItem::with(['subItems', 'product', 'keepHistory'])->find($request->order_item_id);
        $subItems = $orderItem->subItems;
        
        if ($subItems) {
            $totalItemQty = 0;
            $totalServedQty = 0;
            $hasServeQty = false;

            foreach ($subItems as $key => $item) {
                foreach ($request->items as $key => $updated_item) {
                    if ($item['id'] === $updated_item['sub_item_id']) {
                        $maxServeQty = $orderItem->item_qty * $item->item_qty;
                        $newServeQty = $item->serve_qty + $updated_item['serving_qty'];

                        if ($newServeQty > $maxServeQty) {
                            return response()->json([
                                'status' => 'error',
                                'title' => "Serving quantity exceeds the maximum allowed servable quantities."
                            ], 422);
                        }

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
            $keepItem = KeepItem::with('orderItemSubitem.productItem.inventoryItem')->find($orderItem->keep_item_id);
            $inventoryItem = $keepItem->orderItemSubitem->productItem->inventoryItem;
            $keepHistory = $orderItem->keepHistory;

            $keepItem->update(['status' => 'Served']);

            KeepHistory::create([
                'keep_item_id' => $orderItem->keep_item_id,
                'order_item_id' => $orderItem->id,
                'qty' => $keepHistory->qty,
                'cm' => number_format((float) $keepHistory->cm, 2, '.', ''),
                'keep_date' => $keepItem->created_at,
                'kept_balance' => $inventoryItem->current_kept_amt,
                'user_id' => $this->authUser->id,
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

        $currentOrder = Order::select('id', 'pax', 'amount', 'customer_id', 'user_id', 'status', 'voucher_id')
                                ->with(['orderTable:id,table_id,status,order_id', 'customer.rewards'])
                                ->find($request->order_id);

        $addNewOrder = in_array($currentOrder->status, ['Order Completed', 'Order Cancelled', 'Order Voided']) && $currentOrder->orderTable->every(fn ($table) => in_array($table->status, ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided']));
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
                    $product = Product::with('productItems')->select('id', 'price', 'discount_id')->find($item['product_id']);
    
                    $originalItemAmount = $product->price * $item['item_qty'];
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
    
                    Notification::send(User::where('position', 'admin')->get(), new OrderPlaced($tableString, $waiter->full_name, $waiter->id));
    
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
    
                    if (count($product->productItems) > 0) {
                        $temp += $newItemAmount;
                        
                        foreach ($product->productItems as $key => $value) {
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
                                Notification::send(User::where('position', 'admin')->get(), new InventoryOutOfStock($inventoryItem->item_name, $inventoryItem->id));
                            };
    
                            if($newStatus === 'Low in stock'){
                                Notification::send(User::where('position', 'admin')->get(), new InventoryRunningOutOfStock($inventoryItem->item_name, $inventoryItem->id));
                            }
                        }
                    }
                } else if ($item['item_type'] === 'keep') {
                    $keepItem = KeepItem::with(['orderItemSubitem:id,order_item_id,product_item_id', 
                                                            'orderItemSubitem.productItem:id,product_id,inventory_item_id',
                                                            'orderItemSubitem.productItem.inventoryItem', 
                                                            'orderItemSubitem.orderItem:id'])
                                        ->find($item['id']);

                    $productItem = $keepItem->orderItemSubitem->productItem;
                    $inventoryItem = $productItem->inventoryItem;

                    $newOrderItem = OrderItem::create([
                        'order_id' => $addNewOrder ? $newOrder->id : $request->order_id,
                        'user_id' => $waiter->id,
                        'type' => 'Keep',
                        'product_id' => $productItem->product_id,
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
                        'product_item_id' => $productItem->id,
                        'item_qty' => 1,
                        'serve_qty' => $item['return_qty'],
                    ]);

                    $currOrder = Order::with(['orderTable.table', 'customer:id,full_name'])->find($addNewOrder ? $newOrder->id : $request->order_id);
                    $orderTableNames = implode(", ", $currOrder->orderTable->map(fn ($order_table) => $order_table['table']['table_no'])->toArray());
        
                    KeepHistory::create([
                        'keep_item_id' => $item['id'],
                        'order_item_id' => $newOrderItem->id,
                        'qty' => $item['type'] === 'qty' ? round($item['return_qty'], 2) : 0.00,
                        'cm' => $item['type'] === 'cm' ? number_format((float) $keepItem->cm, 2, '.', '') : '0.00',
                        'keep_date' => $keepItem->created_at,
                        'kept_balance' => $item['type'] === 'qty' ? $inventoryItem->current_kept_amt - $item['return_qty'] : $inventoryItem->current_kept_amt,
                        'user_id' => $this->authUser->id,
                        'kept_from_table' => $keepItem->kept_from_table,
                        'redeemed_to_table' => $orderTableNames,
                        'status' => 'Served',
                    ]);
                
                    activity()->useLog('return-kept-item')
                                ->performedOn($newOrderItem)
                                ->event('updated')
                                ->withProperties([
                                    'edited_by' => auth()->user()->full_name,
                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                    'item_name' => $inventoryItem->item_name,
                                    'customer_name' => $currOrder->customer->full_name
                                ])
                                ->log(":properties.item_name is returned to :properties.customer_name.");
        
                    if ($item['type'] === 'qty') {
                        $inventoryItem->decrement('total_kept', $item['return_qty']);
                        $inventoryItem->decrement('current_kept_amt', $item['return_qty']);

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

            $order = Order::with(['orderTable.table', 'orderItems', 'voucher'])->find($addNewOrder ? $newOrder->id : $request->order_id);
            $oldVoucherId = $request->old_voucher_id ?: null;
        
            // if ($request->new_voucher_id !== $oldVoucherId) {
            //     if ($request->new_voucher_id) {
            //         if ($oldVoucherId) {
            //             $oldSelectedReward = CustomerReward::select('id', 'customer_id', 'ranking_reward_id', 'status')
            //                                         ->with([
            //                                             'rankingReward:id,reward_type,discount,free_item,item_qty',
            //                                             'rankingReward.product:id,product_name',
            //                                             'rankingReward.product.productItems:id,product_id,inventory_item_id,qty',
            //                                             'rankingReward.product.productItems.inventoryItem:id,item_name,stock_qty,inventory_id,low_stock_qty,current_kept_amt'
            //                                         ])
            //                                         ->find($oldVoucherId);
                                                
            //             if ($oldSelectedReward) {
            //                 $oldSelectedReward->update(['status' => 'Active']);
            //             }
            //         }
    
            //         $selectedReward = CustomerReward::select('id', 'customer_id', 'ranking_reward_id', 'status')
            //                                     ->with([
            //                                         'rankingReward:id,reward_type,discount,free_item,item_qty',
            //                                         'rankingReward.product:id,product_name',
            //                                         'rankingReward.product.productItems:id,product_id,inventory_item_id,qty',
            //                                         'rankingReward.product.productItems.inventoryItem:id,item_name,stock_qty,inventory_id,low_stock_qty,current_kept_amt'
            //                                     ])
            //                                     ->find($request->new_voucher_id);
    
            //         if ($selectedReward && $selectedReward->rankingReward && in_array($selectedReward->rankingReward->reward_type, ['Discount (Amount)', 'Discount (Percentage)'])) {
            //             $selectedReward->update(['status' => 'Redeemed']);
            //             $order->update(['voucher_id' => $selectedReward->id]);
            //             Log::info("updated order: $order");
            //         }
                
            //     } else {
            //         $this->removeOrderVoucher($currentOrder);
            //     }
            // }

            if ($request->new_voucher_id) {
                $selectedReward = CustomerReward::select('id', 'customer_id', 'ranking_reward_id', 'status')
                                            ->with([
                                                'rankingReward:id,reward_type,discount,free_item,item_qty',
                                                'rankingReward.product:id,product_name',
                                                'rankingReward.product.productItems:id,product_id,inventory_item_id,qty',
                                                'rankingReward.product.productItems.inventoryItem:id,item_name,stock_qty,inventory_id,low_stock_qty,current_kept_amt'
                                            ])
                                            ->find($request->new_voucher_id);

                $voucher = [
                    'id' => $selectedReward->rankingReward->id,
                    'customer_reward_id' => $selectedReward->id,
                ];
                $this->updateOrderVoucher($order, $voucher);

            } else {
                $this->updateOrderVoucher($order, null);
            }

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

            $order->refresh();

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

            $earnedCommission = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                                            ->join('products', 'order_items.product_id', '=', 'products.id')
                                            ->join('config_employee_comm_items as comm_items', 'products.id', '=', 'comm_items.item')
                                            ->join('config_employee_comms as comms', 'comm_items.comm_id', '=', 'comms.id')
                                            ->where([
                                                ['order_items.type', 'Normal'],
                                                ['order_items.user_id', $this->authUser->id],
                                                ['orders.id', $order->id]
                                            ])
                                            ->whereColumn('comm_items.created_at', '<=', 'order_items.created_at')
                                            ->whereNull('comm_items.deleted_at')
                                            ->whereNull('comms.deleted_at')
                                            ->selectRaw("
                                                SUM(
                                                    CASE
                                                        WHEN comms.comm_type = 'Fixed amount per sold product'
                                                        THEN comms.rate * order_items.item_qty
                                                        ELSE products.price * order_items.item_qty * (comms.rate / 100)
                                                    END
                                                ) as total_commission
                                            ")
                                            ->value('total_commission');

            return response()->json([
                'status' => 'success',
                'title' => "Order has been successfully placed.",
                'pendingServeItems' => $pendingServeItems,
                'order' => $order,
                'earned_commission' => round($earnedCommission, 2)
            ], 201);
        }
    
        return response()->json([
            'status' => 'error',
            'title' => "No items selected to be placed."
        ], 422);
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
                                ->where(function ($query) {
                                    $query->where('status', '!=', 'void')
                                          ->orWhereNull('status'); // Handle NULL cases
                                })
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

        // $customers = Customer::where(function ($query) {
        //                             $query->where('status', '!=', 'void')
        //                                   ->orWhereNull('status'); // Handle NULL cases
        //                         })
        //                         ->get(['id', 'uuid', 'name', 'full_name', 'email', 'phone'])
        //                         ->each(function ($customer) {
        //                             $customer->image = $customer->getFirstMediaUrl('customer');

        //                             return $customer;
        //                         });

        return response()->json($customers);
    }
    
    public function getCustomerDetails(Request $request) 
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
                                },
                                'rewards:id,customer_id,ranking_reward_id,status,updated_at',
                                'rewards.rankingReward:id,ranking_id,reward_type,min_purchase,discount,min_purchase_amount,bonus_point,free_item,item_qty,updated_at',
                                'rewards.rankingReward.product:id,product_name'
                            ])
                            ->find($request->id);

        $customer->image = $customer->getFirstMediaUrl('customer');
        $customer->rank['image'] = $customer->rank->getFirstMediaUrl('ranking');
        
        $customer->keep_items_count = $customer->keepItems->reduce(function ($total, $item) {
            $itemQty = $item->qty > $item->cm ? $item->qty : 1;

            return $total + $itemQty;
        }, 0);

        unset($customer->keepItems);

        return response()->json($customer);
    }
    
    public function getCustomerKeepItems(Request $request) 
    {
        $customer = Customer::with([
                                'keepItems' => function ($query) {
                                    $query->where('status', 'Keep')
                                            ->with([
                                                'orderItemSubitem.productItem:id,inventory_item_id',
                                                'orderItemSubitem.productItem.inventoryItem:id,item_name',
                                                'waiter:id,full_name'
                                            ]);
                                },
                            ])
                            ->find($request->id);
        
        foreach ($customer->keepItems as $key => $keepItem) {
            $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
            $keepItem->order_no = $keepItem->orderItemSubitem->orderItem->order['order_no'];
            unset($keepItem->orderItemSubitem);

            $keepItem->image = $keepItem->orderItemSubitem->productItem 
                                ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                                : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

            $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');

        }


        return response()->json($customer->keepItems);
    }
    
    public function getTableKeepItems(Request $request) 
    {
        $orderTables = OrderTable::with([
                                        'table',
                                        'order.orderItems' => fn($query) => $query->where('status', 'Served')->orWhere('status', 'Pending Serve'),
                                        'order.orderItems.product.category',
                                        'order.orderItems.subItems.productItem.inventoryItem',
                                        'order.orderItems.subItems.keepItems.oldestKeepHistory' => function ($query) {
                                            $query->where('status', 'Keep');
                                        },
                                        'order.orderItems.keepItem.oldestKeepHistory'  => function ($query) {
                                            $query->where('status', 'Keep');
                                        },
                                        'order.orderItems.keepItem.keepHistories'  => function ($query) {
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
                                            // return response()->json($item);

                                            $item->sub_items =  $item->subItems->map(function ($subItem) use ($item) {
                                                $isKeepType = !($item['type'] === 'Normal' || $item['type'] === 'Redemption' || $item['type'] === 'Reward');
                                                $totalKept = $isKeepType
                                                    ?   $item->keepItem?->keepHistories?->reduce(fn ($total, $history) => (
                                                            $total + ((int)$history->qty ?? 0) + (((float)$history->cm ?? 0) > 0 ? 1 : 0)

                                                        ), 0) ?? 0
                                                    :   $subItem->keepItems?->reduce(fn ($total, $keepItem) => (
                                                            $total + ((int)$keepItem?->oldestKeepHistory?->qty ?? 0) + (((float)$keepItem?->oldestKeepHistory?->cm ?? 0) > 0 ? 1 : 0)

                                                        ), 0) ?? 0;

                                                $subItem['total_kept'] = $totalKept;

                                                return $subItem;
                                            });

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

    public function getOrderDetails(Request $request) 
    {
        $order = Order::with(['orderTable.table', 'orderItems.product.commItem.configComms', 'voucher'])
                        ->find($request->order_id);

        $order->orderItems->each(function ($item) {
            $item['image'] = $item->product->getFirstMediaUrl('product');

            $configCommItem = $item->product->commItem;

            if ($configCommItem) {
                $commissionType = $configCommItem->configComms->comm_type;
                $commissionRate = $configCommItem->configComms->rate;
                $commissionAmount = $commissionType === 'Fixed amount per sold product' 
                        ? $commissionRate * $item->item_qty
                        : $item->product->price * $item->item_qty * ($commissionRate / 100);
            }
            
            $item['commission_amount'] = $configCommItem ? round($commissionAmount, 2) : 0.00;
        });

        $currentTable = $this->getPendingServeItemsQuery($request->table_id)->get();

        $pendingServeItems = collect();

        $currentTable->each(function ($table) use (&$pendingServeItems) {
            if ($table->order) {
                if ($table->order->customer_id) {
                    $table->order->customer->image = $table->order->customer->getFirstMediaUrl('customer');
                }

                foreach ($table->order->orderItems as $orderItem) {
                    $orderItem['image'] = $orderItem->product->getFirstMediaUrl('product');
                    $pendingServeItems->push($orderItem);

                    $configCommItem = $orderItem->product->commItem;

                    if ($configCommItem) {
                        $commissionType = $configCommItem->configComms->comm_type;
                        $commissionRate = $configCommItem->configComms->rate;
                        $commissionAmount = $commissionType === 'Fixed amount per sold product' 
                                ? $commissionRate * 1
                                : $orderItem->product->price * 1 * ($commissionRate / 100);
                    }
                    
                    $orderItem['commission_amount'] = $configCommItem ? $commissionAmount : 0.00;
                    unset($orderItem->product->commItem);
                }
            }
        });

        return response()->json([
            'order' => $order,
            'pendingServeItems' => $pendingServeItems,
        ]);
    }

    /**
     * Add the items to keep.
     */
    public function addItemToKeep(Request $request)
    {
        try {
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
                return response()->json(['errors' => $allItemErrors], 422);
            }

            if (count($validatedItems) > 0) {
                $order = Order::with(['orderTable.table', 'orderItems.subItems', 'orderItems.product'])->find($request->order_id);
                $currentOrder = Order::with(['orderTable.table', 'orderItems.subItems', 'orderItems.product'])->find($request->current_order_id);

                $orderTableNames = $request->order_id != $request->current_order_id
                        ? implode(", ", $order->orderTable->map(fn ($order_table) => $order_table['table']['table_no'])->toArray())
                        : implode(", ", $currentOrder->orderTable->map(fn ($order_table) => $order_table['table']['table_no'])->toArray());

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
                                        //         'user_id' => $this->authUser->id,
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
                                            'user_id' => $this->authUser->id,
                                            'kept_from_table' => $orderTableNames,
                                            'status' => 'Keep',
                                            'expired_from' => $item['expired_from'],
                                            'expired_to' => $item['expired_to'],
                                        ]);

                                        $associatedSubItem = OrderItemSubitem::where('id', $item['order_item_subitem_id'])
                                                                ->with(['productItem:id,inventory_item_id', 'productItem.inventoryItem:id,item_name'])
                                                                ->first();

                                        $inventoryItemName = $associatedSubItem->productItem->inventoryItem->item_name;

                                        $name = Customer::where('id', $request->customer_id)->first()->full_name;

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
                                            'user_id' => $this->authUser->id,
                                            'kept_from_table' => $orderTableNames,
                                            'status' => 'Keep',
                                        ]);

                                        $tempOrderItem->increment('total_kept', $item['amount']);
                                        $tempOrderItem->increment('current_kept_amt', $item['amount']);

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

            $orderTables = OrderTable::with([
                                        'table',
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
                                    ->where('table_id', $request->table_id)
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

            foreach ($orderTables as $orderTable) {
                if ($orderTable->order) {
                    foreach ($orderTable->order->orderItems as $order_item) {
                        if ($order_item->product) {
                            $order_item->product->image = $order_item->product->getFirstMediaUrl('product');
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

            // return response()->json();

            // $tableKeepHistories = KeepHistory::with([
            //                             'keepItem.orderItemSubitem.orderItem.order.orderTable', 
            //                             'keepItem.orderItemSubitem.productItem:id,inventory_item_id', 
            //                             'keepItem.orderItemSubitem.productItem.inventoryItem:id,item_name', 
            //                             'keepItem.waiter:id,full_name',
            //                             'orderItem.order.orderTable:id,order_id,table_id'
            //                         ])
            //                         ->orderByDesc('id')
            //                         ->get()
            //                         ->filter(fn ($history) => 
            //                                 $history->keepItem->orderItemSubitem->orderItem->order->orderTable->table_id === $request->table_id
            //                                 && in_array($history->keepItem->orderItemSubitem->orderItem->order->orderTable->status, ['Pending Order', 'Order Placed', 'All Order Served', 'Pending Clearance'])
            //                                 && $history->status === 'Keep'
            //                         )
            //                         ->map(function ($history) {
            //                             // Assign item_name and unset unnecessary relationship data
            //                             if ($history->keepItem && $history->keepItem->orderItemSubitem) {
            //                                 $history->keepItem->item_name = $history->keepItem->orderItemSubitem->productItem->inventoryItem->item_name;
            //                                 unset($history->keepItem->orderItemSubitem); // Clean up the response

            //                                 $history->keepItem->image = $history->keepItem->orderItemSubitem->productItem 
            //                                                     ? $history->keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
            //                                                     : $history->keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');
                                
            //                                 $history->keepItem->waiter->image = $history->keepItem->waiter->getFirstMediaUrl('user');
            //                             }
            //                             return $history;
            //                         });

            return response()->json([
                'customer_keep_list' => $customer?->keepItems,
                'table_keep_list' => $uniqueOrders,
                // 'table_keep_histories' => $tableKeepHistories,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'title' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            Log::error('Error fetching order data: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'title' => 'Error keeping item.',
                'errors' => $e
            ], 422);
        };
    }

    public function getTablePaymentHistories(Request $request) 
    {
        $orderTables = OrderTable::with([
                                        'table', 
                                        'order.voucher:id,reward_type,discount', 
                                        'order.payment.customer', 
                                        'order.payment.paymentMethods', 
                                        // 'order.orderItems' => fn($query) => $query->whereNotIn('status', ['Cancelled']),
                                        'order.orderItems.product.commItem.configComms',
                                        'order.orderItems.subItems.productItem.inventoryItem',
                                    ])
                                    ->where([
                                        ['table_id', $request->id],
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

                        $configCommItem = $orderItem->product->commItem;

                        if ($configCommItem) {
                            $commissionType = $configCommItem->configComms->comm_type;
                            $commissionRate = $configCommItem->configComms->rate;
                            $commissionAmount = $commissionType === 'Fixed amount per sold product' 
                                    ? $commissionRate * $orderItem->item_qty
                                    : $orderItem->product->price * $orderItem->item_qty * ($commissionRate / 100);
                        }
                        
                        $orderItem['commission_amount'] = $configCommItem ? round($commissionAmount, 2) : 0.00;
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
        
        return response()->json($uniqueOrders);
    }

    public function getRedeemableItems() 
    {
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
        
        return response()->json($redeemables);
    }

    public function getCustomerPointHistories(Request $request)
    {
        $pointHistories = PointHistory::with([
                                            'payment:id,order_id,point_earned',
                                            'payment.order:id,order_no',
                                            'redeemableItem:id,product_name',
                                            'customer:id,ranking',
                                            'customer.rank:id,name'
                                        ]) 
                                        ->where('customer_id', $request->id)
                                        ->orderBy('created_at','desc')
                                        ->get();

        $pointHistories->each(function ($record) {
            $record->image = $record->redeemableItem?->getFirstMediaUrl('product');
        });

        return response()->json($pointHistories);
    }
    
    public function createCustomerFromOrder(Request $request)
    {
        try {
            $existingCustomer = Customer::where('name', $request->full_name)->first(['id', 'full_name', 'phone']);

            if ($existingCustomer) {
                $existingCustomer->image = $existingCustomer->getFirstMediaUrl('customer');

                return response()->json([
                    'status' => 'info',
                    'title' => "Existing Account Found",
                    'message' => "We found an account associated with this email address, do you want to check-in this customer account instead?",
                    'existing_customer' => $existingCustomer
                ]);
            }

            $validatedData = $request->validate(
                [
                    'full_name' => 'required|string|max:255|unique:customers,full_name',
                    'phone' => 'nullable|integer|unique:customers,phone',
                    'email' => 'nullable|email|unique:customers,email',
                    'order_id' => 'required',
                ], 
                [
                    'full_name.unique' => 'Username has already been taken.',
                    'phone.unique' => 'Phone number has already been taken.',
                    'phone.integer' => 'This field must be in numbers only.',
                    'email.unique' => 'Email has already been taken.',
                    'email.email' => 'Invalid email.',
                    'required' => 'This field is required.',
                    'email' => 'Invalid email',
                ]
            );

            $defaultRank = Ranking::where('name', 'Member')->first(['id', 'name']);
        
            $newCustomer = Customer::create([
                'uuid' => RunningNumberService::getID('customer'),
                'name' => $validatedData['full_name'],
                'full_name' => $validatedData['full_name'],
                'dial_code' => '+60',
                'phone' => $validatedData['phone'],
                'email' => $validatedData['email'],
                'password' => Hash::make('Test1234.'), // new Randomizer()->getBytes(8)
                'ranking' => $defaultRank->id,
                'point' => 0,
                'total_spending' => 0.00,
                'first_login' => '0',
                'status' => 'verified',
            ]);

            $order = Order::find($validatedData['order_id']);
            $order->update(['customer_id' => $newCustomer->id]);

            // need to send email to customer to show their new account details and password

            return response()->json([
                'status' => 'succcess',
                'title' => "Customer's account has been successfully created and checked-in to current table.",
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'title' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            return response()->json([
                'title' => 'Error creating customer.',
                'errors' => $e
            ], 422);
        };
    }
    
    public function redeemItemToOrder(Request $request)
    {
        try {
            $validatedData = $request->validate(
                [
                    'table_id' => 'required|integer',
                    'order_id' => 'required|integer',
                    'redeem_qty' => 'required|integer',
                    'selected_item_id' => 'required|integer',
                ], 
                ['required' => 'This field is required.']
            );

            $currentOrder = Order::select('id', 'pax', 'amount', 'customer_id', 'user_id', 'status')
                                    ->with([
                                        'orderTable:id,table_id,status,order_id',
                                        'customer:id,full_name,point'
                                    ])
                                    ->find($validatedData['order_id']);

            $addNewOrder = in_array($currentOrder->status, ['Order Completed', 'Order Cancelled', 'Order Voided']) && $currentOrder->orderTable->every(fn ($table) => in_array($table->status, ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided']));
            $tablesArray = $currentOrder->orderTable->map(fn ($table) => $table->table_id)->toArray();
    
            $tableString = $this->getTableName($tablesArray);
            $pointSpent = 0;
    
            $waiter = $this->authUser;
            $waiter->image = $waiter->getFirstMediaUrl('user');

            $customer = $currentOrder->customer;
    
            if ($validatedData) {
                if ($addNewOrder) {
                    $newOrder = Order::create([
                        'order_no' => RunningNumberService::getID('order'),
                        'pax' => $currentOrder->pax,
                        'user_id' => $waiter->id,
                        'customer_id' => $customer->id,
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
    
                $redeemableItem = Product::with([
                                                'productItems:id,product_id,inventory_item_id,qty',
                                                'productItems.inventoryItem:id,item_name,stock_qty,inventory_id,low_stock_qty,current_kept_amt'
                                            ])
                                            ->find($validatedData['selected_item_id']);
    
                $newOrderItem = OrderItem::create([
                    'order_id' => $addNewOrder ? $newOrder->id : $validatedData['order_id'],
                    'user_id' => $waiter->id,
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
                    'handled_by' => $waiter->id,
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
    
                $order = Order::with(['orderTable.table', 'voucher'])->find($addNewOrder ? $newOrder->id : $validatedData['order_id']);
                
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
    
                $earnedCommission = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                                                ->join('products', 'order_items.product_id', '=', 'products.id')
                                                ->join('config_employee_comm_items as comm_items', 'products.id', '=', 'comm_items.item')
                                                ->join('config_employee_comms as comms', 'comm_items.comm_id', '=', 'comms.id')
                                                ->where([
                                                    ['order_items.type', 'Normal'],
                                                    ['order_items.user_id', $waiter->id],
                                                    ['orders.id', $order->id]
                                                ])
                                                ->whereColumn('comm_items.created_at', '<=', 'order_items.created_at')
                                                ->whereNull('comm_items.deleted_at')
                                                ->whereNull('comms.deleted_at')
                                                ->selectRaw("
                                                    SUM(
                                                        CASE
                                                            WHEN comms.comm_type = 'Fixed amount per sold product'
                                                            THEN comms.rate * order_items.item_qty
                                                            ELSE products.price * order_items.item_qty * (comms.rate / 100)
                                                        END
                                                    ) as total_commission
                                                ")
                                                ->value('total_commission');
    
                return response()->json([
                    'status' => 'success',
                    'title' => "Product has been successfully redeemed and added to customer's order detail.",
                    'customerPoint' => $customer->point,
                    'pendingServeItems' => $pendingServeItems,
                    'order' => $order,
                    'earned_commission' => round($earnedCommission, 2)
                ], 201);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'title' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            Log::info($e);
            return response()->json([
                'title' => 'Error redeeming item.',
                'errors' => $e
            ], 422);
        };
    }

    public function getTableKeepHistories(Request $request) 
    {
        // Fetch order tables with necessary relationships
        $orderTables = OrderTable::with([
                                    'table',
                                    'order.orderItems' => fn($query) => $query->whereIn('status', ['Served', 'Pending Serve']),
                                    'order.orderItems.subItems.productItem.inventoryItem',
                                    'order.orderItems.subItems.keepItems.keepHistories' => fn($query) => $query->where('status', 'Keep'),
                                    'order.orderItems.subItems.keepItems.waiter'
                                ])
                                ->where('table_id', $request->id)
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
                                        'waiter' => $keepItem->waiter
                                    ]
                                ]);
                            })
                        )
                    )
                )->values(),
                'customer' => $order->customer ? [
                    'id' => $order->customer->id,
                    'name' => $order->customer->full_name,
                    'image' => $order->customer->getFirstMediaUrl('customer')
                ] : null,
            ];
        })->values();
    
        return response()->json($uniqueOrders);
    }

    /**
     * Redeem entry reward and add to current order.
     */
    public function redeemEntryRewardToOrder(Request $request)
    {
        try {
            $validatedData = $request->validate(
                [
                    'table_id' => 'required|integer',
                    'order_id' => 'required|integer',
                    'customer_reward_id' => 'required|integer',
                ], 
                [
                    'required' => 'This field is required.',
                    'integer' => 'This field must be an integer.',
                ]
            );

            $currentOrder = Order::select('id', 'pax', 'amount', 'customer_id', 'user_id', 'status')
                                    ->with([
                                        'orderTable:id,table_id,status,order_id',
                                        'customer:id,full_name,point'
                                    ])
                                    ->find($validatedData['order_id']);
                                    
            $addNewOrder = in_array($currentOrder->status, ['Order Completed', 'Order Cancelled', 'Order Voided']) && $currentOrder->orderTable->every(fn ($table) => in_array($table->status, ['Pending Clearance', 'Order Completed', 'Order Cancelled', 'Order Voided']));
            $tablesArray = $currentOrder->orderTable->map(fn ($table) => $table->table_id)->toArray();

            $tableString = $this->getTableName($tablesArray);

            $waiter = $this->authUser;
            $waiter->image = $waiter->getFirstMediaUrl('user');

            $customer = $currentOrder->customer;

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

                if ($tierReward->reward_type === 'Free Item') {
                    if ($addNewOrder) {
                        $newOrder = Order::create([
                            'order_no' => RunningNumberService::getID('order'),
                            'pax' => $currentOrder->pax,
                            'user_id' => $waiter->id,
                            'customer_id' => $customer->id,
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

                    $newOrderItem = OrderItem::create([
                        'order_id' => $addNewOrder ? $newOrder->id : $validatedData['order_id'],
                        'user_id' => $waiter->id,
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

                    $order = Order::with(['orderTable.table', 'voucher'])->find($addNewOrder ? $newOrder->id : $validatedData['order_id']);
                    
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

                    $data = [
                        'status' => 'success',
                        'title' => "Reward has been successfully used and added to the order of the customer.",
                        'pendingServeItems' => $this->getUpdatedPendingServeItems($request->table_id),
                        'order' => $order,
                        'earned_commission' => round($this->getUpdatedEarnedCommission($waiter->id, $order->id), 2)
                    ];
                };

                if (in_array($tierReward->reward_type, ['Discount (Amount)', 'Discount (Percentage)'])) {
                    $order = Order::find($validatedData['order_id']);
                    $order->update(['voucher_id' => $tierReward->id]);
                    $selectedReward->update(['status' => 'Redeemed']);

                    $data = [
                        'status' => 'success',
                        'title' => "Reward has been successfully used and added to the order of the customer.",
                    ];
                };

                return response()->json($data, 201);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'title' => 'The given data was invalid.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception  $e) {
            Log::info($e);
            return response()->json([
                'title' => 'Error redeeming reward.',
                'errors' => $e
            ], 422);
        };
    }

    public function removeEntryRewardFromOrder(Request $request)
    {
        $order = Order::select('id', 'customer_id', 'voucher_id')
                            ->with([
                                'customer:id',
                                'customer.rewards:id,customer_id,ranking_reward_id,status',
                            ])
                            ->find($request->id);

        if (!$order || !$order->customer) {
            return response()->json([
                'status' => 'error',
                'title' => 'Order or customer not found.',
            ], 404);
        }
                            
        $customer = $order->customer;
        $removalTarget = $customer->rewards->where('ranking_reward_id', $order->voucher_id)->first();
        
        if (!$removalTarget) {
            return response()->json([
                'status' => 'error',
                'title' => 'Reward not found.',
            ], 404);
        }

        $order->update(['voucher_id' => null]);
        $removalTarget->update(['status' => 'Active']);

        return response()->json([
            'status' => 'success',
            'title' => 'Reward vouhcer removed.',
        ], 201);
    }

    private function getUpdatedPendingServeItems(string $tableId) 
    {
        $currentTable = $this->getPendingServeItemsQuery($tableId)->get();
    
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

        return $pendingServeItems;
    }

    private function getUpdatedEarnedCommission(string $waiterId, string $orderId) 
    {
        return $earnedCommission = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('config_employee_comm_items as comm_items', 'products.id', '=', 'comm_items.item')
                ->join('config_employee_comms as comms', 'comm_items.comm_id', '=', 'comms.id')
                ->where([
                    ['order_items.type', 'Normal'],
                    ['order_items.user_id', $waiterId],
                    ['orders.id', $orderId]
                ])
                ->whereColumn('comm_items.created_at', '<=', 'order_items.created_at')
                ->whereNull('comm_items.deleted_at')
                ->whereNull('comms.deleted_at')
                ->selectRaw("
                    SUM(
                        CASE
                            WHEN comms.comm_type = 'Fixed amount per sold product'
                            THEN comms.rate * order_items.item_qty
                            ELSE products.price * order_items.item_qty * (comms.rate / 100)
                        END
                    ) as total_commission
                ")
                ->value('total_commission');
    }
    
    /**
     * Check in guest to table based on reservation.
     */
    public function checkInReservedTable(Request $request)
    {
        try {
            // Validate form request
            $validatedData = $request->validate(
                [
                    'assigned_waiter' => 'required|integer',
                    'pax' => 'required|string',
                    'reservation_id' => 'required|integer',
                ], 
                ['required' => 'This field is required.']
            );

            $reservation = Reservation::find($validatedData['reservation_id']);
            $selectedTables = array_column($reservation->table_no, 'id');
            
            $waiter = $this->authUser;
            $waiter->image = $waiter->getFirstMediaUrl('user');

            // Create new order
            $newOrder = Order::create([
                'order_no' => RunningNumberService::getID('order'),
                'pax' => $validatedData['pax'],
                'user_id' => $validatedData['assigned_waiter'],
                'customer_id' => $reservation->customer_id ?? null,
                'amount' => 0.00,
                'total_amount' => 0.00,
                'status' => 'Pending Serve',
            ]);
            
            // Update selected tables and create order table records in a loop
            foreach ($selectedTables as $tableId) {
                $table = Table::find($tableId);
        
                // Update table status and related order
                $table->update([
                    'status' => 'Pending Order',
                    'order_id' => $newOrder->id
                ]);

                // Create new order table
                OrderTable::create([
                    'table_id' => $tableId,
                    'pax' => $validatedData['pax'],
                    'user_id' => $waiter->id,
                    'status' => 'Pending Order',
                    'order_id' => $newOrder->id
                ]);
            }

            //Check in activity log and notification
            $assignedWaiter = User::select('full_name', 'id')->find($validatedData['assigned_waiter']);
            $assignedWaiter->image = $assignedWaiter->getFirstMediaUrl('user');

            $orderTables = implode(', ', array_map(function ($table) {
                return Table::where('id', $table)->pluck('table_no')->first();
            }, $selectedTables));

            activity()->useLog('Order')
                    ->performedOn($newOrder)
                    ->event('check in')
                    ->withProperties([
                        'waiter_name' => $assignedWaiter->full_name,
                        'table_name' => $orderTables, 
                        'waiter_image' => $assignedWaiter->image
                    ])
                    ->log("New customer check-in by :properties.waiter_name.");
            
            Notification::send(User::where('position', 'admin')->get(), new OrderCheckInCustomer($orderTables, $assignedWaiter->full_name, $assignedWaiter->id));

            //Assigned activity log and notification
            activity()->useLog('Order')
                        ->performedOn($newOrder)
                        ->event('assign to serve')
                        ->withProperties([
                            'waiter_name' => $assignedWaiter->full_name,
                            'waiter_image' => $assignedWaiter->image,
                            'table_name' => $orderTables, 
                            'assigned_by' => $waiter->full_name,
                            'assigner_image' => $waiter->image,
                        ])
                        ->log("Assigned :properties.waiter_name to serve :properties.table_name.");

            Notification::send(User::where('position', 'admin')->get(), new OrderAssignedWaiter($orderTables, $waiter->id, $assignedWaiter->id));

            // Update reservation details
            $reservation->update([
                'status' => 'Checked in',
                'action_date' => now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                'order_id' => $newOrder->id,
                'handled_by' => $waiter->id,
            ]);

            $tableString = $this->getTableName($selectedTables);

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
            ], 422);
        };
    }
    
    /**
     * Check customer out of table.
     */
    public function checkOutCustomer(Request $request)
    {
        try {
            $validatedData = $request->validate(
                [
                    'order_id' => 'required|integer',
                    'tables' => 'required|array',
                ], 
                ['required' => 'This field is required.']
            );

            $order = Order::with([
                                'customer:id',
                                'customer.rewards:id,customer_id,ranking_reward_id,status',
                            ])
                            ->find($validatedData['order_id']);

            $tableString = $this->getTableName($validatedData['tables']);

            if ($order->customer_id && $order->voucher) {
                $this->removeOrderVoucher($order);
            }

            $order->update(['customer_id' => null]);
            
            return response()->json([
                'status' => 'success',
                'message' => "The checked-in customer from $tableString has been checked-out."
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
            ], 422);
        };
    }

    public function getAutoUnlockDuration() {
        $setting = Setting::where('name', 'Table Auto Unlock')
                            ->first(['name', 'value_type', 'value']);
    
        return response()->json($setting);
    }
    
    public function handleTableLock(Request $request)
    {
        $action = $request->action;

        if (in_array($action, ['lock', 'unlock'])) {
            Table::whereIn('id', $request->table_id_array)->update(['is_locked' => $action === 'lock']);

            return response()->json([
                'status' => 'success',
                'message' => $action === 'unlock' ? 'The table you were viewing has been unlocked' : 'The table you are currently viewing will be locked'
            ], 201);
        }

        return response()->json([
            'title' => 'Error handling table lock state.',
        ], 422);
    }
    
    public function getExpiringPointHistories(Request $request)
    {
        $customer = Customer::find($request->id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'title' => 'The customer id does not exist'
            ], 422);
        }

        $expiringNotificationTimer = Setting::where([
                                                ['name', 'Point Expiration Notification'],
                                                ['type', 'expiration']
                                            ])
                                            ->first(['id', 'value']);

        $expiringPointHistories = PointHistory::where('customer_id', $request->id)
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

        return response()->json([
            'status' => 'success',
            'expiring_point_histories' => $expiringPointHistories,
        ], 200);
    }
}
