<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\Order;
use App\Models\PointHistory;
use App\Models\Product;
use App\Models\Ranking;
use App\Models\StockHistory;
use App\Models\User;
use App\Notifications\InventoryOutOfStock;
use App\Notifications\InventoryRunningOutOfStock;
use App\Services\RunningNumberService;
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
                                ->get()
                                ->map(function ($customer) {
                                    $customer->image = $customer->getFirstMediaUrl('customer');
                                    $customer->keep_items_count = $customer->keepItems->reduce(function ($total, $item) {
                                        $itemQty = $item->qty > $item->cm ? $item->qty : 1;

                                        return $total + $itemQty;
                                    }, 0);

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
            'full_name' => $validatedData['full_name'],
            'dial_code' => '+60',
            'phone' => $validatedData['phone'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'ranking' => $defaultRank->id,
            'point' => 0,
            'total_spending' => 0.00,
            'first_login' => '1',
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
                                ->get()
                                ->map(function ($customer) {
                                    $customer->image = $customer->getFirstMediaUrl('customer');
                                    $customer->keep_items_count = $customer->keepItems->count();

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
                                ->get()
                                ->map(function ($customer) {
                                    $customer->image = $customer->getFirstMediaUrl('customer');
                                    $customer->keep_items_count = $customer->keepItems->count();

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
        
        $customer = Customer::with('keepItems', function ($query) {
                                $query->with([
                                            'orderItemSubitem:id,product_item_id',
                                            'orderItemSubitem.productItem:id,inventory_item_id',
                                            'orderItemSubitem.productItem.inventoryItem'
                                        ])
                                        ->where('status', 'Keep')
                                        ->whereColumn('qty', '>', 'cm');
                            })
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
                $item->update([
                    'qty' => 0.00,
                    'status' => 'Deleted'
                ]);

                activity()->useLog('delete-kept-item')
                            ->performedOn($item)
                            ->event('deleted')
                            ->withProperties([
                                'edited_by' => auth()->user()->full_name,
                                'image' => auth()->user()->getFirstMediaUrl('user'),
                                'item_name' => $item->orderItemSubitem->productItem->inventoryItem->item_name,
                            ])
                            ->log(":properties.item_name is deleted.");

                KeepHistory::create([
                    'keep_item_id' => $item->id,
                    'qty' => $item->qty,
                    'cm' => number_format((float) $item->cm, 2, '.', ''),
                    'keep_date' => $item->created_at,
                    'remark' => $request->remark,
                    'status' => 'Deleted',
                ]);

                if ($item->qty > $item->cm) {
                    $inventoryItem = $item->orderItemSubitem->productItem->inventoryItem;
                    $deletedKeepQty = $item->qty;
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
                        Notification::send(User::all(), new InventoryOutOfStock($inventoryItem->item_name, $inventoryItem->id));
                    };
        
                    if($newStatus === 'Low in stock'){
                        Notification::send(User::all(), new InventoryRunningOutOfStock($inventoryItem->item_name, $inventoryItem->id));
                    }
                }
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
                    $query->whereBetween('point', array_map('intval', $request['checkedFilters']['points']));
                }
            });

            // Apply filter for 'keepItems'
            if (!empty($request['checkedFilters']['keepItems'])) {
                $queries->withCount(['keepItems' => fn ($query) => $query->where('status', 'Keep')])
                        ->having('keep_items_count', '>',0);
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

        $customers = $queries->get('id')->toArray();

        return response()->json($customers);
    }

    public function returnKeepItem (Request $request, string $id) 
    {
        $selectedItem = KeepItem::findOrFail($id);

        if ($request->type === 'qty') {
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
            'keep_date' => $selectedItem->created_at,
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
                                        'keepItem:id,order_item_subitem_id,customer_id,qty,cm,remark,user_id,status,expired_from,expired_to',
                                        'keepItem.orderItemSubitem:id,order_item_id,product_item_id', 
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
                                            unset($history->keepItem->orderItemSubitem);

                                            $history->keepItem->image = $history->keepItem->orderItemSubitem->productItem 
                                                        ? $history->keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                                                        : $history->keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');
    
                                            $history->keepItem->waiter->image = $history->keepItem->waiter->getFirstMediaUrl('user');
                                        }
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

        PointHistory::create([
            'type' => 'Adjusted',
            'point_type' => 'Adjustment',
            'qty' => 0,
            'amount' => $request->point,
            'old_balance' => $targetCustomer->point,
            'new_balance' => !!$request->addition ? $targetCustomer->point + $request->point : $targetCustomer->point - $request->point,
            'remark' => $request->reason ? $request->reason : '',
            'customer_id' => $request->id,
            'handled_by' => auth()->user()->id,
            'redemption_date' => now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
        ]);

        $targetCustomer->update([
            'point' => !!$request->addition ? $targetCustomer->point + $request->point : $targetCustomer->point - $request->point,
        ]);

        $targetCustomer->save();

        $data = $this->getCustomers();

        return response()->json($data->find( $request->id)->point);
    }

    private function getCustomers(){
        $data = Customer::where(function ($query) {
                            $query->where('status', '!=', 'void')
                                ->orWhereNull('status'); // Handle NULL cases
                        });

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
}
