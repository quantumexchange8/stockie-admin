<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\IventoryItem;
use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemSubitem;
use App\Models\OrderTable;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PointHistory;
use App\Models\Product;
use App\Models\Ranking;
use App\Models\Setting;
use App\Models\StockHistory;
use App\Models\User;
use App\Notifications\InventoryOutOfStock;
use App\Notifications\InventoryRunningOutOfStock;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::select('id', 'full_name', 'email', 'phone', 'ranking', 'point', 'status', 'created_at')
                                ->with([
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
                                ->where(function ($query) {
                                    $query->where('status', '!=', 'void')
                                          ->orWhereNull('status'); // Handle NULL cases
                                })
                                ->orderBy('full_name')
                                ->get()
                                ->map(function ($customer) {
                                    $customer->image = $customer->getFirstMediaUrl('customer');
                                    $customer->keep_items_count = $customer->keepItems->reduce(function ($total, $item) {
                                        $itemQty = $item->qty > $item->cm ? $item->qty : 1;

                                        return $total + $itemQty;
                                    }, 0);

                                    // if ($customer->rank) {
                                    //     $customer->rank->image = $customer->rank->getFirstMediaUrl('ranking');
                                    // }

                                    // foreach ($customer->keepItems as $key => $keepItem) {
                                    //     $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
                                    //     $keepItem->order_no = $keepItem->orderItemSubitem->orderItem->order['order_no'];
                                    //     unset($keepItem->orderItemSubitem);

                                    //     $keepItem->image = $keepItem->orderItemSubitem->productItem 
                                    //                 ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                                    //                 : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

                                    //     $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
                                    // }

                                    return $customer;
                                });
                    
        $message = $request->session()->get('message');

        return Inertia::render("Customer/Customer", [
            'customers' => $customers,
            'rankingArr' => Ranking::get(['id', 'name'])->toArray(),
            'message'=> $message ?? [],
        ]);
    }

    public function store(CustomerRequest $request)
    {
        $validatedData = $request->validated();

        $defaultRank = Ranking::where('name', 'Member')->first(['id', 'name']);

        Customer::create([
            'uuid' => RunningNumberService::getID('customer'),
            'name' => $validatedData['full_name'],
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
    
        $customers = Customer::select('id', 'full_name', 'email', 'phone', 'ranking', 'point', 'status', 'created_at')
                                ->with([
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
                                ->where(function ($query) {
                                    $query->where('status', '!=', 'void')
                                          ->orWhereNull('status'); // Handle NULL cases
                                })
                                ->orderBy('full_name')
                                ->get()
                                ->map(function ($customer) {
                                    $customer->image = $customer->getFirstMediaUrl('customer');
                                    $customer->keep_items_count = $customer->keepItems->reduce(function ($total, $item) {
                                        $itemQty = $item->qty > $item->cm ? $item->qty : 1;

                                        return $total + $itemQty;
                                    }, 0);

                                    // if ($customer->rank) {
                                    //     $customer->rank->image = $customer->rank->getFirstMediaUrl('ranking');
                                    // }

                                    // foreach ($customer->keepItems as $key => $keepItem) {
                                    //     $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
                                    //     $keepItem->order_no = $keepItem->orderItemSubitem->orderItem->order['order_no'];
                                    //     unset($keepItem->orderItemSubitem);

                                    //     $keepItem->image = $keepItem->orderItemSubitem->productItem 
                                    //                 ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                                    //                 : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

                                    //     $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
                                    // }

                                    return $customer;
                                });

        return response()->json($customers);
    }

    public function update(CustomerRequest $request, string $id)
    {
        $validatedData = $request->validated();

        $customer = Customer::where('id', $id)->first();

        $customer->update([
            'full_name' => $validatedData['full_name'],
            'phone' => $validatedData['phone'],
            'email' => $validatedData['email'],
        ]);
    
        if ($validatedData['password'] != '') {
            $customer->update([
                'password' => Hash::make($validatedData['password']),
            ]);
        }

        $customers = Customer::select('id', 'full_name', 'email', 'phone', 'ranking', 'point', 'created_at')
                                ->with([
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
                                ->where(function ($query) {
                                    $query->where('status', '!=', 'void')
                                          ->orWhereNull('status'); // Handle NULL cases
                                })
                                ->orderBy('full_name')
                                ->get()
                                ->map(function ($customer) {
                                    $customer->image = $customer->getFirstMediaUrl('customer');
                                    $customer->keep_items_count = $customer->keepItems->reduce(function ($total, $item) {
                                        $itemQty = $item->qty > $item->cm ? $item->qty : 1;

                                        return $total + $itemQty;
                                    }, 0);

                                    // if ($customer->rank) {
                                    //     $customer->rank->image = $customer->rank->getFirstMediaUrl('ranking');
                                    // }

                                    // foreach ($customer->keepItems as $key => $keepItem) {
                                    //     $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
                                    //     $keepItem->order_no = $keepItem->orderItemSubitem->orderItem->order['order_no'];
                                    //     unset($keepItem->orderItemSubitem);

                                    //     $keepItem->image = $keepItem->orderItemSubitem->productItem 
                                    //                 ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                                    //                 : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');

                                    //     $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
                                    // }

                                    return $customer;
                                });

        return response()->json($customers);
    }

    public function deleteCustomer(Request $request, string $id)
    {
        $validatedData = $request->validate(
            [
                'password' => 'required|string|max:255',
            ], 
            [
                'required' => 'This field is required',
                // 'verification_code.required' => 'This field is required',
                // 'verification_code.integer' => 'This field must be an string.',
                // 'verification_code.exists'=> 'Invalid verification code.',
            ]
        );
        
        $customer = Customer::with(['keepItems' => function ($query) {
                                $query->with([
                                            'orderItemSubitem:id,product_item_id',
                                            'orderItemSubitem.productItem:id,inventory_item_id',
                                            'orderItemSubitem.productItem.inventoryItem'
                                        ])
                                        ->where('status', 'Keep')
                                        ->whereColumn('qty', '>', 'cm');
                            }])
                            ->find($id);

        $user = Auth::user();

        if ($customer && (Hash::check($validatedData['password'], $user->password))) {
            activity()->useLog('delete-customer')
                        ->performedOn($customer)
                        ->event('deleted')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'item_name' => $customer->full_name,
                        ])
                        ->log("Customer '$customer->full_name' is deleted.");

            $customer->update([
                'remark' => $request->remark,
                'status' => 'void',
            ]);

            foreach ($customer->keepItems as $key => $item) {
                $inventoryItem = $item->orderItemSubitem->productItem->inventoryItem;
                $deletedKeepQty = $item->qty;
                $isQtyType = $deletedKeepQty > $item->cm;
                $newKeptBalance = $isQtyType ? $inventoryItem->current_kept_amt - $deletedKeepQty : $inventoryItem->current_kept_amt;

                activity()->useLog('delete-kept-item')
                            ->performedOn($item)
                            ->event('deleted')
                            ->withProperties([
                                'edited_by' => auth()->user()->full_name,
                                'image' => auth()->user()->getFirstMediaUrl(collectionName: 'user'),
                                'item_name' => $inventoryItem->item_name,
                            ])
                            ->log(":properties.item_name is deleted.");

                KeepHistory::create([
                    'keep_item_id' => $item->id,
                    'qty' => $deletedKeepQty,
                    'cm' => number_format((float) $item->cm, 2, '.', ''),
                    'keep_date' => $item->expired_from,
                    'kept_balance' => $newKeptBalance,
                    'user_id' => auth()->user()->id,
                    'kept_from_table' => $item->kept_from_table,
                    'remark' => $request->remark,
                    'status' => 'Deleted',
                ]);

                if ($isQtyType) {
                    $oldStock = $inventoryItem->stock_qty;
                    $newStock = $oldStock + $deletedKeepQty;
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

                $item->update([
                    'qty' => 0.00,
                    'cm' => 0.00,
                    'status' => 'Deleted'
                ]);
            }

            $message = [
                'severity' => 'success',
                'summary' => "Selected customer has been deleted successfully.",
            ];

            return Redirect::route('customer')->with(['message'=> $message]);
        }

        return redirect()->back()->withErrors([
            'password' => 'The passcode is not valid.'
        ]);
    }

    public function getFilteredCustomers(Request $request)
    {
        $queries = Customer::query();
    
        if (isset($request['checkedFilters'])) {
            $queries->with('rank')->where(function (Builder $query) use ($request) {
                // Apply filter for 'tier'
                if (isset($request['checkedFilters']['tier']) && count($request['checkedFilters']['tier']) > 0) {
                    $query->whereHas('rank', function ($rankQuery) use ($request) {
                        $rankQuery->whereIn('id', $request['checkedFilters']['tier']);
                    });
                }
    
                // Apply filter for 'points'
                if (isset($request['checkedFilters']['points']) && count($request['checkedFilters']['points']) === 2) {
                    $query->whereBetween('point', array_map(fn ($value) => round($value, 2), $request['checkedFilters']['points']));
                }
            });

            // Apply filter for 'keepItems'
            if (!empty($request['checkedFilters']['keepItems'])) {
                $queries->withCount(['keepItems' => fn ($query) => $query->where('status', 'Keep')])
                        ->having('keep_items_count', '>', 0);
            }
        }
        // dd($queries->toSql());
        // $results = $queries->with('rank')->withCount('keepItems')->get();

        // $customers = $results->map(function ($query) {

        //         $activeKeepItems = $query->keepItems
        //         ->where('qty', '>', 0)
        //         ->sortByDesc('created_at')
        //         ->map(function ($keepItem) {
        //             $itemName = $keepItem->orderItemSubitem->productItem->product->product_name ?? 'N/A';
        //                 return [
        //                 'id' => $keepItem->id,
        //                 'item' => $itemName,
        //                 'qty' => $keepItem->qty,
        //                 'created_at' => Carbon::parse($keepItem->created_at)->format('d/m/Y, h:i A'),
        //                 'expired_from' => Carbon::parse($keepItem->expired_from)->format('d/m/Y'),
        //                 'expired_to' => Carbon::parse($keepItem->expired_to)->format('d/m/Y'),
        //                 'waiter_name' => $keepItem->waiter->full_name ?? 'N/A',
        //             ];
        //         })->toArray(); 

        //     return [
        //         "id" => $query->id,
        //         "tier" => $query->ranking->name ?? 'N/A',
        //         "name" => $query->full_name,
        //         "email" => $query->email,
        //         "phone" => $query->phone,
        //         "points" => $query->point,
        //         "keep_items" => $activeKeepItems,
        //         "keep" => $query->keep_items_count,
        //         "created_at" => Carbon::parse($query->created_at)->format("d/m/Y"),
        //     ];
        // });

        // dd($queries->toSql(), $queries->getBindings(), $customers);

        $customers = $queries->with([
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
                            ->where(function ($query) {
                                $query->where('status', '!=', 'void')
                                        ->orWhereNull('status'); // Handle NULL cases
                            })
                            ->orderBy('full_name')
                            ->get('id')
                            ->toArray();

        return response()->json($customers);
    }

    public function returnKeepItem (Request $request, string $id) 
    {
        $selectedItem = KeepItem::with(['orderItemSubitem:id,order_item_id,product_item_id', 
                                                        'orderItemSubitem.productItem:id,product_id,inventory_item_id',
                                                        'orderItemSubitem.productItem.inventoryItem', 
                                                        'orderItemSubitem.orderItem:id'])
                                    ->find($id);

        $inventoryItem = $selectedItem->orderItemSubitem->productItem->inventoryItem;

        if ($request->type === 'qty') {
            $inventoryItem->decrement('total_kept', $request->return_qty);
            $inventoryItem->decrement('current_kept_amt', $request->return_qty);

            $selectedItem->update([
                'qty' => ($selectedItem->qty - $request->return_qty) > 0 ? $selectedItem->qty - $request->return_qty : 0.00,
                'status' => ($selectedItem->qty - $request->return_qty) > 0 ? 'Keep' : 'Returned'
            ]);
        } else {
            $selectedItem->update([
                'cm' => 0.00,
                'status' => 'Returned'
            ]);
        }

        KeepHistory::create([
            'keep_item_id' => $id,
            'qty' => $request->type === 'qty' ? round($request->return_qty, 2) : 0.00,
            'cm' => $request->type === 'cm' ? round($selectedItem->cm, 2) : 0.00,
            'keep_date' => $selectedItem->expired_from,
            'kept_balance' => $request->type === 'qty' ? $inventoryItem->current_kept_amt - $request->return_qty : $inventoryItem->current_kept_amt,
            'user_id' => auth()->user()->id,
            'kept_from_table' => $selectedItem->kept_from_table,
            'status' => 'Returned',
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

    public function getKeepHistories (string $id) 
    {
        $keepHistories = KeepHistory::with([
                                        'keepItem.orderItemSubitem.productItem.inventoryItem.inventory.media',
                                        'keepItem.orderItemSubitem.productItem.product.media',
                                        'keepItem.waiter.media',
                                        'waiter.media',
                                    ])
                                    ->with([
                                        'keepItem:id,order_item_subitem_id,customer_id,qty,cm,remark,user_id,status,expired_from,expired_to',
                                        'keepItem.orderItemSubitem:id,order_item_id,product_item_id', 
                                        'keepItem.orderItemSubitem.productItem:id,inventory_item_id,product_id', 
                                        'keepItem.orderItemSubitem.productItem.product:id', 
                                        'keepItem.orderItemSubitem.productItem.inventoryItem:id,item_name,inventory_id', 
                                        'keepItem.orderItemSubitem.productItem.inventoryItem.inventory:id', 
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
                                            unset($history->keepItem->orderItemSubitem);

                                            $history->keepItem->image = $history->keepItem->orderItemSubitem->productItem 
                                                        ? $history->keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                                                        : $history->keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');
    
                                                $history->keepItem->waiter->image = $history->keepItem->waiter->getFirstMediaUrl('user');
                                            }
                                            $history->waiter->image = $history->waiter->getFirstMediaUrl('user');
                                        return $history;
                                    });

        return response()->json($keepHistories);
    }

    public function getRedeemableItems () {
        $redeemables = Product::select('id','product_name', 'point', 'availability')->where('is_redeemable', true)->get();
        $redeemables->each(function($redeemable){
            $redeemable->image = $redeemable->getFirstMediaUrl('product');
        });
        
        return response()->json($redeemables);
    }

    public function getCustomerPointHistories(string $id)
    {
        // $orders = Order::with('pointHistories') 
        //     ->where('customer_id', $id)
        //     ->orderBy('created_at','desc')
        //     ->get();

        // $earned = $orders->map(function ($order) {
        //     $positivePoints = $order->orderItems->sum('point_earned');
        //     $pointNames = $order->orderItems->where('type', 'Redemption')
        //         ->map(function ($item) {
        //             return [
        //                 "id" => $item->point->id,
        //                 "name" => $item->point->name ?? 'N/A',
        //                 "image" => $item->point->getFirstMediaUrl('point'),
        //                 "qty" => $item->item_qty,
        //                 "redeemed" => ($item->point_redeemed * $item->item_qty),
        //             ];
        //         })->unique('name'); 

        //     $subject = $positivePoints > 0 ? $order->order_no : null;
        //     return [
        //         'created_at' => Carbon::parse($order->created_at)->format('d/m/Y, h:i A'),
        //         'subject' => $subject,
        //         'earned' => $positivePoints,
        //         'used' => $pointNames,
        //     ];
        // });

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

    public function tierRewards(string $id)
    {
        // $customerTier = Customer::where('id', $id)->pluck('ranking')->first();
        // $tierName = Ranking::where('id', $customerTier)->pluck('name')->first();
        // $rankingRewards = RankingReward::where('ranking_id', $customerTier)->get();
    
        // $response = [];
    
        // foreach ($rankingRewards as $reward) {
        //     $formattedDate = Carbon::parse($reward->valid_period_to)->format('d/m/Y');
        //     if (Carbon::parse($reward->valid_period_to)->isPast()) {
        //         $rewardData = [
        //             'reward_type' => $reward->reward_type,
        //             'status' => 'expired',
        //             'valid_period_to' => $formattedDate,
        //             'reward' => null,
        //         ];
        //     } else {
        //         switch ($reward->reward_type) {
        //             case 'Discount (Amount)':
        //                 $calculatedReward = $reward->discount;
        //                 break;
        //             case 'Discount (Percentage)':
        //                 $calculatedReward = $reward->discount * 100;
        //                 break;
        //             case 'Bonus Point':
        //                 $calculatedReward = $reward->bonus_point;
        //                 break;
        //             case 'Free Item':
        //                 $rewardNo = $reward->free_item;
        //                 $calculatedReward = IventoryItem::where('id', $rewardNo)->pluck('item_name')->first();
        //                 break;
        //             default:
        //                 $calculatedReward = null;
        //         }
        //         $formattedDate = Carbon::parse($reward->valid_period_to)->format('d/m/Y');
        //         $rewardData = [
        //             'reward_type' => $reward->reward_type,
        //             'status' => $reward->min_purchase,
        //             'valid_period_to' => $formattedDate,
        //             'reward' => $calculatedReward,
        //         ];
        //     }
    
        //     $response[] = $rewardData;
        // }
     
        // $result = [
        //     'tier_name' => $tierName,
        //     'rewards' => $response,
        // ];
    
        $customer = Customer::select('id')
                            ->with([
                                'rewards:id,customer_id,ranking_reward_id,status,updated_at',
                                'rewards.rankingReward:id,ranking_id,reward_type,min_purchase,discount,min_purchase_amount,bonus_point,free_item,item_qty,updated_at',
                                'rewards.rankingReward.product:id,product_name',
                                'rewards.rankingReward.ranking:id,name',
                            ])
                            ->find($id);

        return response()->json($customer->rewards);
    }
    
    public function adjustPoint(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'point' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
            'addition' => ['required', 'boolean'],
        ], [
            'required' => 'This field is required.',
            'integer' => 'Invalid input. Please try again.',
            'string' => 'Invalid input. Please try again.', 
            'boolean' => 'Invalid input. Please try again.',
            'max' => 'This field only accepts input of maximum 255 characters.',
        ]);

        $targetCustomer = Customer::find($request->id);

        $newPointBalance = !!$request->addition ? $targetCustomer->point + $request->point : $targetCustomer->point - $request->point;

        $pointExpirationDays = Setting::where([
                                            ['name', 'Point Expiration'],
                                            ['type', 'expiration']
                                        ])
                                        ->first(['id', 'value']);

        $pointExpiredDate = now()->addDays((int)$pointExpirationDays->value);

        if (!!!$request->addition) {
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
                                            ->where('customer_id', $targetCustomer->id)
                                            ->orderBy('expired_at', 'asc') // earliest expiry first
                                            ->get();

            $remainingPoints = $request->point;

            foreach ($usableHistories as $history) {
                if ($remainingPoints <= 0) break;

                $deductAmount = min($remainingPoints, $history->expire_balance);
                $history->expire_balance -= $deductAmount;
                $history->save();

                $remainingPoints -= $deductAmount;
            }

            $afterReimbursePoint = $request->point;

        } else {
            $afterReimbursePoint = $targetCustomer->point < 0 
                    ? $targetCustomer->point + $request->point 
                    : $request->point;
        }

        PointHistory::create([
            'type' => 'Adjusted',
            'point_type' => 'Adjustment',
            'qty' => 0,
            'amount' => $afterReimbursePoint <= 0 ? 0 : $afterReimbursePoint,
            'old_balance' => $targetCustomer->point,
            'new_balance' => $newPointBalance,
            'expire_balance' => !!$request->addition ? $afterReimbursePoint : 0,
            'expired_at' => !!$request->addition ? $pointExpiredDate : null,
            'remark' => $request->reason ? $request->reason : '',
            'customer_id' => $request->id,
            'handled_by' => auth()->user()->id,
            'redemption_date' => now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
        ]);

        $targetCustomer->update([
            'point' => $newPointBalance,
        ]);

        $targetCustomer->save();

        $data = $this->getCustomers();

        return response()->json($data->find( $request->id)->point);
    }

    private function getCustomers(){
        $data = Customer::where(function ($query) {
                            $query->where('status', '!=', 'void')
                                ->orWhereNull('status'); // Handle NULL cases
                        })
                        ->orderBy('full_name');

        return $data;
    }

    // used by order customer listing
    public function getAllCustomers(){
        $customerList = $this->getCustomers()
                        ->with('billDiscountUsages')
                        ->get(['id', 'full_name', 'phone', 'ranking'])
                        ->map(function ($customer) {
                            $customer->image = $customer->getFirstMediaUrl('customer');

                            return $customer;
                        });

        return response()->json($customerList);
    }

    public function getCurrentOrdersCount(string $id){
        $currentOrdersCount = Order::query()
                                    ->join('order_tables', 'orders.id', '=', 'order_tables.order_id')
                                    ->whereNotIn('order_tables.status', ['Order Completed', 'Order Cancelled', 'Order Voided', 'Pending Clearance'])
                                    ->where('orders.customer_id', $id)
                                    ->get()
                                    ->count();

        return response()->json($currentOrdersCount);
    }

    public function importKeepItems(Request $request){
        return DB::transaction(function () use ($request) {
            // Create customer from imported excel
            $customerList = collect($request->keep_item_list)->map(fn ($item) => $item['NAME'])->toArray();
            $defaultRank = Ranking::where('name', 'Member')->first(['id', 'name']);

            foreach ($customerList as $key => $value) {
                $existingCustomer = Customer::where('name', $value)->first();

                if (!$existingCustomer) {
                    Customer::create([
                        'uuid' => RunningNumberService::getID('customer'),
                        'name' => $value,
                        'full_name' => $value,
                        'dial_code' => '+60',
                        'password' => Hash::make('Test1234.'),
                        'ranking' => $defaultRank->id,
                        'point' => 0,
                        'total_spending' => 0.00,
                        'first_login' => '0',
                        'status' => 'verified',
                    ]);
                }
            }

            // Check in table
            $keepItemList = collect($request->keep_item_list);

            // Filter out empty rows (where Item2 might be null or empty)
            $filteredItems = $keepItemList->filter(fn ($item) => isset($item['Item2']) && !empty($item['Item2']));
        
            // Group by item name and sum quantities
            $itemTotals = $filteredItems->groupBy('Item2')->map(fn ($group) => $group->sum('QTY'));
        
            // Sort the items alphabetically
            $sortedItems = $itemTotals->sortKeys();

            // CHeck in customer to table
            $order = Order::create([
                'order_no' => RunningNumberService::getID('order'),
                'pax' => 2,
                'user_id' => 1,
                'amount' => 0.00,
                'total_amount' => 0.00,
                'status' => 'Order Completed',
            ]);

            OrderTable::create([
                'table_id' => 1,
                'pax' => 2,
                'user_id' => 1,
                'status' => 'Order Completed',
                'order_id' => $order->id
            ]);


            // Place order items
            if (count($sortedItems) > 0) {
                foreach ($sortedItems as $itemName => $itemQty) {
                    $product = Product::whereHas('productItems.inventoryItem', function($query) use ($itemName) {
                                            $query->where('item_name', ucwords($itemName));
                                        })
                                        ->where('bucket', 'single')
                                        ->with('productItems')
                                        ->select('id')
                                        ->first();

                    // dd($itemName, $itemQty, $product);

                    $new_order_item = OrderItem::create([
                        'order_id' => $order->id,
                        'user_id' => 1,
                        'type' => 'Normal',
                        'product_id' => $product->id,
                        'item_qty' => $itemQty,
                        'amount_before_discount' => 0.00,
                        'discount_id' => null,
                        'discount_amount' => 0.00,
                        'amount' => 0.00,
                        'status' => 'Served',
                    ]);

                    if (count($product->productItems) > 0) {
                        foreach ($product->productItems as $key => $value) {
                            OrderItemSubitem::create([
                                'order_item_id' => $new_order_item->id,
                                'product_item_id' => $value['id'],
                                'item_qty' => $value['qty'],
                                'serve_qty' => $itemQty * $value['qty'],
                            ]);
                        }
                    }
                }
            }

            // Keep item
            if (count($keepItemList) > 0) {
                $orderItems = $order->orderItems;
                // dd($keepItemList);

                // Loop through keep item listing
                foreach ($keepItemList as $keepItemKey => $keepItem) {
                    $keepItemName = ucwords($keepItem['Item2']);

                    // Loop through order items
                    foreach ($orderItems as $orderItemKey => $orderItem) {
                        // Loop through order item's subitems
                        foreach ($orderItem->subItems as $subItemKey => $subItem) {
                            $associatedInventoryName = $subItem->productItem->inventoryItem->item_name;

                            if ($keepItemName === $associatedInventoryName) {
                                // dd($associatedInventoryName, $subItem, $keepItem);
                                $customer = Customer::where('name', $keepItem['NAME'])->first('id');

                                // if (!$customer) dd($keepItem, $customer);
                                $newKeep = KeepItem::create([
                                    'customer_id' => $customer->id,
                                    'order_item_subitem_id' => $subItem['id'],
                                    'qty' => $keepItem['QTY'],
                                    'cm' => 0,
                                    'remark' => null,
                                    'user_id' => 1,
                                    'kept_from_table' => $keepItem['ROOM'],
                                    'status' => 'Keep',
                                    'expired_from' => Carbon::parse($keepItem['Date2'])->format('Y-m-d H:i:s'),
                                    'expired_to' => Carbon::parse($keepItem['Date2'])->addDays(90)->format('Y-m-d H:i:s')
                                ]);

                                $inventoryItem = IventoryItem::where('item_name', $keepItemName)->first();

                                KeepHistory::create([
                                    'keep_item_id' => $newKeep->id,
                                    'qty' => $keepItem['QTY'],
                                    'cm' => '0.00',
                                    'keep_date' => $keepItem['Date2'],
                                    'user_id' => auth()->user()->id,
                                    'kept_balance' => $inventoryItem->current_kept_amt + $keepItem['QTY'],
                                    'kept_from_table' => $keepItem['ROOM'],
                                    'status' => 'Keep',
                                ]);
                                
                                $inventoryItem->update([
                                    'total_kept' => $inventoryItem->total_kept + $keepItem['QTY'],
                                    'current_kept_amt' => $inventoryItem->current_kept_amt + $keepItem['QTY']
                                ]);
                            }
                        }
                    }
                }
            }

            // Payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'table_id' => $order->orderTable->pluck('table.id'),
                'receipt_no' => RunningNumberService::getID('payment'),
                'receipt_start_date' => $order->created_at,
                'receipt_end_date' => now('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                'total_amount' => 0.00,
                'rounding' => 0.00,
                'sst_amount' => 0.00,
                'service_tax_amount' => 0.00,
                'discount_id' => null,
                'discount_amount' => 0.00,
                'bill_discounts' => null,
                'bill_discount_total' => 0.00,
                'grand_total' => 0.00,
                'amount_paid' => 0.00,
                'change' => 0.00,
                'point_earned' => 0,
                'pax' => $order->pax,
                'status' => 'Successful',
                'customer_id' => null,
                'handled_by' => 1,
            ]);

            PaymentDetail::create([
                'payment_id' => $payment->id,
                'payment_method' => 'Card',
                'amount' => 0.00,
            ]);

            return redirect()->back();
        });
    }
    
    protected function fetchExpiringPointHistories(string $id)
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

        return $expiringPointHistories;
    }
    
    public function getExpiringPointHistories(string $id)
    {
        $data = $this->fetchExpiringPointHistories($id);

        return response()->json($data);
    }
    
    public function getCustomerDetails(string $id)
    {
        $expiringPointHistories = $this->fetchExpiringPointHistories($id);

        $newCustomerData = Customer::with([
                                    'media',
                                    'rank.media',    // eager load media relation for rank
                                    'keepItems.orderItemSubitem.productItem.inventoryItem.inventory.media',
                                    'keepItems.orderItemSubitem.productItem.product.media',
                                    'keepItems.waiter.media',
                                ])
                                ->with([
                                    'rank:id,name',
                                    'keepItems' => function ($query) {
                                        $query->where('status', 'Keep')
                                                ->with([
                                                    'orderItemSubitem.productItem:id,inventory_item_id,product_id',
                                                    'orderItemSubitem.productItem.inventoryItem:id,item_name,inventory_id',
                                                    'orderItemSubitem.productItem.inventoryItem.inventory:id',
                                                    'orderItemSubitem.productItem.product:id',
                                                    'orderItemSubitem.orderItem.order:id,order_no',
                                                    'waiter:id,full_name'
                                                ]);
                                    }
                                ])
                                ->select('id', 'full_name', 'email', 'phone', 'ranking', 'point', 'status', 'created_at')
                                ->find($id);
        
        
        $newCustomerData['image'] = $newCustomerData->getFirstMediaUrl('customer');
        $newCustomerData['keep_items_count'] = $newCustomerData['keepItems']->reduce(function ($total, $item) {
            $itemQty = $item['qty'] > $item['cm'] ? $item['qty'] : 1;

            return $total + $itemQty;
        }, 0);

        if ($newCustomerData['rank']) {
            $newCustomerData['rank']['image'] = $newCustomerData['rank']->getFirstMediaUrl('ranking');
        }

        foreach ($newCustomerData['keepItems'] as $key => $keepItem) {
            $keepItem['item_name'] = $keepItem['orderItemSubitem']['productItem']['inventoryItem']['item_name'];
            $keepItem['order_no'] = $keepItem['orderItemSubitem']['orderItem']['order']['order_no'];
            unset($keepItem['orderItemSubitem']);

            $keepItem['image'] = $keepItem['orderItemSubitem']['productItem'] 
                        ? $keepItem['orderItemSubitem']['productItem']['product']->getFirstMediaUrl('product') 
                        : $keepItem['orderItemSubitem']['productItem']['inventoryItem']['inventory']->getFirstMediaUrl('inventory');

            $keepItem['waiter']['image'] = $keepItem['waiter']->getFirstMediaUrl('user');
        }

        return response()->json([
            'expiringPointHistories' => $expiringPointHistories,
            'newCustomerData' => $newCustomerData,
        ]);
    }
}
