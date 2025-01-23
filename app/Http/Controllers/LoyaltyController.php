<?php

namespace App\Http\Controllers;

use App\Http\Requests\PointItemRequest;
use App\Http\Requests\PointRequest;
use App\Http\Requests\RankingRequest;
use App\Http\Requests\RankingRewardRequest;
use App\Models\Customer;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Models\Payment;
use App\Models\PointItem;
use App\Models\Product;
use App\Models\SaleHistory;
use App\Models\Ranking;
use App\Models\RankingReward;
use App\Models\Point;
use App\Models\PointHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;

class LoyaltyController extends Controller
{
    public function index(Request $request){
        $tiers = Ranking::with([
                                'rankingRewards' => fn ($query) => $query->where('status', 'Active'), 
                                'rankingRewards.product',
                                'customers'
                            ])
                            ->orderBy('id')
                            ->get()
                            ->map(function ($rank) {
                                $rank['merged_reward_type'] = $rank->rankingRewards
                                                                    ->pluck('reward_type')
                                                                    ->unique()
                                                                    ->implode(', ');

                                $rank['member'] = $rank->customers->count();
                                
                                $rank->icon = $rank->getFirstMediaUrl('ranking');

                                $rank->rankingRewards->each(function ($reward) {
                                    $reward->isFullyRedeemed = $reward->customerReward->contains('status', 'Active');
                                });

                                return $rank;
                            }); 

        $existingIcons = Ranking::all()->flatMap(function ($ranking){
            return $ranking->getMedia('ranking');
        });

        $redeemableItems = Product::select(['id', 'product_name', 'point'])
                                    ->where('is_redeemable', true)
                                    ->with([
                                        'productItems:id,product_id,inventory_item_id,qty',
                                        'productItems.inventoryItem:id,stock_qty',
                                        'pointHistories',
                                        'pointHistories.handledBy:id,name'
                                    ])
                                    ->orderBy('id')
                                    ->get()
                                    ->map(function ($product) {
                                        $product->image = $product->getFirstMediaUrl('product');
                                        $product_items = $product->productItems;
                                        $minStockCount = 0;
        
                                        if (count($product_items) > 0) {
                                            $stockCountArr = [];
        
                                            foreach ($product_items as $key => $value) {
                                                $stockQty = $value->inventoryItem->stock_qty;
                                                $stockCount = (int)bcdiv($stockQty, (int)$value['qty']);
        
                                                array_push($stockCountArr, $stockCount);
                                            }
                                            $minStockCount = min($stockCountArr);
                                        }
                                        $product['stock_left'] = $minStockCount;

                                        return $product;
                                    }); 

        $products = Product::select(['id', 'product_name'])
                            ->with([
                                'productItems:id,product_id,inventory_item_id,qty',
                                'productItems.inventoryItem:id,stock_qty'
                            ])
                            ->where([['availability', 'Available'], ['status', '!=', 'Out of stock']])
                            ->orderBy('id')
                            ->get()
                            ->map(function ($product) {
                                $product_items = $product->productItems;
                                $minStockCount = 0;

                                if (count($product_items) > 0) {
                                    $stockCountArr = [];

                                    foreach ($product_items as $key => $value) {
                                        $stockQty = $value->inventoryItem->stock_qty;
                                        $stockCount = (int)bcdiv($stockQty, (int)$value['qty']);

                                        array_push($stockCountArr, $stockCount);
                                    }
                                    $minStockCount = min($stockCountArr);
                                }

                                unset($product->productItems);

                                return [
                                    'text' => $product->product_name,
                                    'value' => $product->id,
                                    'image' => $product->getFirstMediaUrl('product'),
                                    'stock_left' => $minStockCount

                                ];
                            });

        // $inventoryItems = Iventory::withWhereHas('inventoryItems')
        //                             ->select(['id', 'name'])
        //                             ->orderBy('id')
        //                             ->get()
        //                             ->map(function ($group) {
        //                                 $group_items = $group->inventoryItems->map(function ($item) {
        //                                     return [
        //                                         'text' => $item->item_name,
        //                                         'value' => $item->id,
        //                                     ];
        //                                 });

        //                                 return [
        //                                     'group_name' => $group->name,
        //                                     'items' => $group_items,
        //                                     'group_image' => $group->getFirstMediaUrl('inventory')
        //                                 ];
        //                             });

        // $totalPointsGivenAway = SaleHistory::with('product:id,point')
        //                                     ->orderBy('id')
        //                                     ->get()
        //                                     ->map(function ($sale) {
        //                                         $product = $sale->product;
        //                                         $totalPointsGivenAway = 0;

        //                                         $totalPointsGivenAway += $sale['qty'] * (int)$product['point'];

        //                                         return $totalPointsGivenAway;
        //                                     });

        $totalPointsGivenAway = PointHistory::where('type', 'Earned')->sum('amount');

        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        return Inertia::render('LoyaltyProgramme/LoyaltyProgramme', [
            'message' => $message ?? [],
            'tiers' => $tiers,
            'logos' => $existingIcons,
            'redeemableItems' => $redeemableItems,
            'products' => $products,
            'totalPointsGivenAway' => (int)$totalPointsGivenAway,
        ]);
    }

    public function storeTier(RankingRequest $request)
    {   
        // Get validated tier data
        $validatedData = $request->validated();

        // Get tier items
        $rewardsData = $request->input('rewards');
        
        $rankingRewardRequest = new RankingRewardRequest();
        $validatedRankingRewards = [];
        $allItemErrors = [];

        if($rewardsData){
            foreach ($rewardsData as $index => $reward) {
                $rules = $rankingRewardRequest->rules();
    
                switch ($reward['reward_type']) {
                    case 'Discount (Amount)':
                    case 'Discount (Percentage)':
                        $rules['discount'] = str_replace('nullable', 'required', $rules['discount']);
    
                        if ($reward['min_purchase'] === 'active') {
                            $rules['min_purchase_amount'] = str_replace('nullable', 'required', $rules['min_purchase_amount']);
                        }
    
                        break;
                    case 'Bonus Point':
                        $rules['bonus_point'] = str_replace('nullable', 'required', $rules['bonus_point']);
                        
                        break;
                    case 'Free Item':
                        $rules['free_item'] = str_replace('nullable', 'required', $rules['free_item']);
                        
                        break;
                }
    
                // DO NOT REMOVE code that has until confirmed is not needed by requirements
                // if ($reward['reward_type'] !== 'Bonus Point') {
                //     $rules['valid_period_from'] = str_replace('nullable', 'required', $rules['valid_period_from']);
                //     $rules['valid_period_to'] = str_replace('nullable', 'required', $rules['valid_period_to']);
                // }
    
                $requestMessages = $rankingRewardRequest->messages();
    
                // Validate ranking rewards data
                $rankingRewardsValidator = Validator::make(
                    $reward,
                    $rules,
                    $requestMessages,
                    $rankingRewardRequest->attributes()
                );
                
                if ($rankingRewardsValidator->fails()) {
                    // Collect the errors for each reward and add to the array with reward index
                    foreach ($rankingRewardsValidator->errors()->messages() as $field => $messages) {
                        $allItemErrors["items.$index.$field"] = $messages;
                    }
                } else {
                    // Collect the validated reward and manually add the 'id' field back
                    $validatedReward = $rankingRewardsValidator->validated();
                    if (isset($reward['id'])) {
                        $validatedReward['id'] = $reward['id'];
                    }
                    $validatedRankingRewards[] = $validatedReward;
                }
            }
        }

        // If there are any reward validation errors, return them
        if (!empty($allItemErrors)) {
            return redirect()->back()->withErrors($allItemErrors);
        }

        $ranking = Ranking::create([
            'name'=>$validatedData['name'],
            'min_amount' => $validatedData['min_amount'],
            'reward' => $validatedData['reward'],
            'icon' => ''
        ]);

        if($request->hasFile('icon')) {
            $ranking->addMedia($validatedData['icon'])->toMediaCollection('ranking');
        }

        activity()->useLog('create-tier')
                    ->performedOn($ranking)
                    ->event('added')
                    ->withProperties([
                        'created_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'tier_name' => $ranking->name,
                    ])
                    ->log("Tier '$ranking->name' is added.");

        if($validatedData['reward'] === 'active' && count($validatedRankingRewards) > 0) {
            foreach ($validatedRankingRewards as $value) {
                RankingReward::create([
                    'ranking_id' => $ranking->id,
                    'reward_type' => $value['reward_type'],
                    'discount' => $value['discount'],
                    'min_purchase_amount' => $value['min_purchase_amount'],
                    'min_purchase' => $value['min_purchase'],
                    'bonus_point' => $value['bonus_point'],
                    'free_item' => $value['free_item'],
                    'item_qty' => $value['item_qty'],
                    // 'valid_period_from' => $value['valid_period_from'], // DO NOT REMOVE code that has until confirmed is not needed by requirements
                    // 'valid_period_to' => $value['valid_period_to'], // DO NOT REMOVE code that has until confirmed is not needed by requirements
                    'status' => 'Active',
                ]);
            }
        }

        $message = [ 
            'severity' => 'success', 
            'summary' => 'New tier has been added successfully.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    // public function showRecord()
    // {
    //     $data = Ranking::with([
    //                             'rankingRewards', 
    //                             'rankingRewards.inventoryItem',
    //                             'customers'
    //                         ])
    //                         ->orderBy('id')
    //                         ->get()
    //                         ->map(function ($rank) {
    //                             $rank['merged_reward_type'] = $rank->rankingRewards
    //                                                                 ->pluck('reward_type')
    //                                                                 ->unique()
    //                                                                 ->implode(', ');

    //                             $rank['member'] = $rank->customers->count();
                                
    //                             return $rank;
    //                         }); 

    //     return response()->json($data);
    // }

    public function showTierDetails(string $id)
    {
        $tier = Ranking::with([
                            'rankingRewards' => fn ($query) => $query->where('status', 'Active'), 
                            'customers'
                        ])->find($id); 

        $tier->icon = $tier->getFirstMediaUrl('ranking');

        $reward = $tier->rankingRewards->map(function($reward) {
            return array_merge(
                $reward->toArray(),
                [ 'item_name' => $reward->product?->product_name ]
            );
        });
        
        $existingIcons = Ranking::all()->flatMap(function ($ranking){
            return $ranking->getMedia('ranking');
        });

        // $inventoryItems = Iventory::withWhereHas('inventoryItems')
        //                             ->select(['id', 'name'])
        //                             ->orderBy('id')
        //                             ->get()
        //                             ->map(function ($group) {
        //                                 $group_items = $group->inventoryItems->map(function ($item) {
        //                                     return [
        //                                         'text' => $item->item_name,
        //                                         'value' => $item->id,
        //                                     ];
        //                                 });

        //                                 return [
        //                                     'group_name' => $group->name,
        //                                     'items' => $group_items
        //                                 ];
        //                             });

        $products = Product::select(['id', 'product_name'])
                            ->where([['availability', 'Available'], ['status', '!=', 'Out of stock']])
                            ->orderBy('id')
                            ->get()
                            ->map(function ($product) {
                                return [
                                    'text' => $product->product_name,
                                    'value' => $product->id,
                                    'image' => $product->getFirstMediaUrl('product')
                                ];
                            });

        $monthlySpent = Payment::with('customer')
                                ->whereHas('customer', function ($query) use ($id) {
                                    $query->where('ranking', $id);
                                })
                                ->where('status', 'Successful')
                                ->whereMonth('receipt_end_date', now()->month)
                                ->whereYear('receipt_end_date', now()->year)
                                ->get()
                                ->groupBy('customer_id')
                                ->map(function ($payments) {
                                    return [
                                        'full_name' => $payments->first()->customer->full_name,
                                        'spent' => $payments->sum('grand_total'),
                                    ];
                                })
                                ->sortBy('full_name')
                                ->values();
        $names = $monthlySpent->pluck('full_name')->toArray();
        $spendings = $monthlySpent->pluck('spent')->toArray();

        $customers = Customer::where('ranking', $id)
                            ->select('full_name', 'created_at', 'total_spending')
                            ->get();

        $customers->each(function($customer){
            $customer->joined_on = Carbon::parse($customer->created_at)->format('d/m/Y');
            $customer->image = $customer->getFirstMediaUrl('customer');
        });

        return Inertia::render('LoyaltyProgramme/Partial/TierDetail', [
            'id' => $id,
            'tier' => $tier,
            'reward' => $reward,
            'customers' => $customers,
            // 'inventoryItems' => $inventoryItems,
            'logos' => $existingIcons,
            'products' => $products,
            'names' => $names,
            'spendings' => $spendings,
        ]);
    }

    public function filterMemberSpending (Request $request)
    {  
        // $id = $request->input('id');

        $monthlySpent = Payment::with('customer')
                                ->whereHas('customer', function ($query) use ($request) {
                                    $query->where('ranking', $request->input('id'));
                                })
                                ->when($request->input('selected') === 'This month', function ($query) {
                                            $query->whereMonth('created_at', now()->month)
                                                ->whereYear('created_at', now()->year);
                                        })
                                ->when($request->input('selected') === 'This year', function ($query) {
                                            $query->whereYear('created_at', now()->year);
                                        })
                                ->get()
                                ->groupBy('customer_id')
                                ->map(function ($payments) {
                                    return [
                                        'full_name' => $payments->first()->customer->full_name,
                                        'spent' => $payments->sum('grand_total'),
                                    ];
                                })
                                ->sortByDesc('full_name')
                                ->values();
        $names = $monthlySpent->pluck('full_name')->toArray();
        $spendings = $monthlySpent->pluck('spent')->toArray();

        return response()->json([
            'names' => $names,
            'spendings' => $spendings,
        ]);
    }

    // public function showTierData(Request $request)
    // {   
    //     $id = $request->query('id');
    //     $ranking = Ranking::with('rankingRewards')
    //                         ->find($id); 
    //     // $rankingRewards = RankingReward::where('ranking_id', $id)->get();
    //     return response()->json([
    //         'ranking' => $ranking,
    //         'rankingRewards' => $ranking->rankingRewards,
    //     ]);
    // }

    public function updateTier(RankingRequest $request, string $id)
    {        
        // Get validated tier data
        $validatedData = $request->validated();

        // Get tier items
        $rewardsData = $request->input('rewards') ?? [];
        
        $rankingRewardRequest = new RankingRewardRequest();
        $validatedRankingRewards = [];
        $allItemErrors = [];

        foreach ($rewardsData as $index => $reward) {
            $rules = $rankingRewardRequest->rules();

            switch ($reward['reward_type']) {
                case 'Discount (Amount)':
                case 'Discount (Percentage)':
                    $rules['discount'] ??= str_replace('nullable', 'required', $rules['discount']);

                    if ($reward['min_purchase'] === 'active') {
                        $rules['min_purchase_amount'] = str_replace('nullable', 'required', $rules['min_purchase_amount']);
                    }

                    break;
                case 'Bonus Point':
                    $rules['bonus_point'] = str_replace('nullable', 'required', $rules['bonus_point']);
                    
                    break;
                case 'Free Item':
                    $rules['free_item'] = str_replace('nullable', 'required', $rules['free_item']);
                    
                    break;
            }

            // DO NOT REMOVE code that has until confirmed is not needed by requirements
            // if ($reward['reward_type'] !== 'Bonus Point') {
            //     $rules['valid_period_from'] = str_replace('nullable', 'required', $rules['valid_period_from']);
            //     $rules['valid_period_to'] = str_replace('nullable', 'required', $rules['valid_period_to']);
            // }

            $requestMessages = $rankingRewardRequest->messages();

            // Validate ranking rewards data
            $rankingRewardsValidator = Validator::make(
                $reward,
                $rules,
                $requestMessages,
                $rankingRewardRequest->attributes()
            );
            
            if ($rankingRewardsValidator->fails()) {
                // Collect the errors for each reward and add to the array with reward index
                foreach ($rankingRewardsValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated reward and manually add the 'id' field back
                $validatedReward = $rankingRewardsValidator->validated();
                if (isset($reward['id'])) {
                    $validatedReward['id'] = $reward['id'];
                }
                $validatedRankingRewards[] = $validatedReward;
            }
        }

        // If there are any reward validation errors, return them
        if (!empty($allItemErrors)) redirect()->back()->withErrors($allItemErrors);

        if (isset($id)) {
            $existingRanking = Ranking::find($id);

            $existingRanking->update([
                'name'=>$validatedData['name'],
                'min_amount' => $validatedData['min_amount'],
                'reward' => $validatedData['reward'],
                'icon' => ''
            ]);

            activity()->useLog('edit-tier-detail')
                        ->performedOn($existingRanking)
                        ->event('updated')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'tier_name' => $existingRanking->name,
                        ])
                        ->log("Detail for tier '$existingRanking->name' is updated.");

            if($request->hasFile('icon')) {                
                $existingRanking->clearMediaCollection('ranking');
                $existingRanking->addMedia($validatedData['icon'])->toMediaCollection('ranking');
            }
        }

        if($validatedData['reward'] === 'active' && count($validatedRankingRewards) > 0) {
            foreach ($validatedRankingRewards as $value) {
                if (isset($value['id'])) {
                    $existingRankingReward = RankingReward::where('id', $value['id'])->first();

                    $existingRankingReward->update([
                        'ranking_id' => $request->id,
                        'reward_type' => $value['reward_type'],
                        'discount' => $value['discount'],
                        'min_purchase_amount' => $value['min_purchase_amount'],
                        'min_purchase' => $value['min_purchase'],
                        'bonus_point' => $value['bonus_point'],
                        'free_item' => $value['free_item'],
                        'item_qty' => $value['item_qty'],
                        // 'valid_period_from' => $value['valid_period_from'], // DO NOT REMOVE code that has until confirmed is not needed by requirements
                        // 'valid_period_to' => $value['valid_period_to'], // DO NOT REMOVE code that has until confirmed is not needed by requirements
                        'status' => $value['status'],

                    ]);

                    activity()->useLog('edit-reward-types')
                                ->performedOn($existingRankingReward)
                                ->event('updated')
                                ->withProperties([
                                    'edited_by' => auth()->user()->full_name,
                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                    'tier_name' => $existingRanking->name,
                                ])
                                ->log("Reward type for tier '$existingRanking->name' is updated.");

                } else {
                    RankingReward::create([
                        'ranking_id' => $request->id,
                        'reward_type' => $value['reward_type'],
                        'discount' => $value['discount'],
                        'min_purchase_amount' => $value['min_purchase_amount'],
                        'min_purchase' => $value['min_purchase'],
                        'bonus_point' => $value['bonus_point'],
                        'free_item' => $value['free_item'],
                        'item_qty' => $value['item_qty'],
                        // 'valid_period_from' => $value['valid_period_from'], // DO NOT REMOVE code that has until confirmed is not needed by requirements
                        // 'valid_period_to' => $value['valid_period_to'], // DO NOT REMOVE code that has until confirmed is not needed by requirements
                        'status' => 'Active',
                    ]);
                }
            }
        }

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Changes saved.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    /**
     * Get all inventory items along with its items.
     */
    public function getAllInventoryWithItems()
    {
        return Iventory::withWhereHas('inventoryItems')
                        ->select(['id', 'name'])
                        ->orderBy('id')
                        ->get()
                        ->map(function ($group) {
                            $group_items = $group->inventoryItems->map(function ($item) {
                                return [
                                    'text' => $item->item_name,
                                    'value' => $item->id,
                                ];
                            });

                            return [
                                'group_name' => $group->name,
                                'items' => $group_items
                            ];
                        });
    }
     
    /**
     * Delete tier and its rewards as well as updating all its members' tier status.
     */
    public function deleteTier(Request $request, string $id)
    {
        $existingRanking = Ranking::with('rankingRewards')->find($id);

        $message = [ 
            'severity' => 'error', 
            'summary' => 'Error deleting redeemable item.'
        ];

        if ($existingRanking) {
            activity()->useLog('delete-tier')
                        ->performedOn($existingRanking)
                        ->event('deleted')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'tier_name' => $existingRanking->name,
                        ])
                        ->log("Tier '$existingRanking->name' and its ranking rewards are deleted.");

            // Soft delete all related ranking rewards in bulk
            $existingRanking->rankingRewards()->delete();
    
            // Soft delete the ranking
            $existingRanking->delete();

            $message = [ 
                'severity' => 'success', 
                'summary' => 'Selected tier has been deleted successfully.'
            ];
        }

        
        return Redirect::route('loyalty-programme')->with(['message' => $message]);
    }

    /**
     * Store point and its items.
     */
    public function storePoint(PointRequest $request)
    {   
        $validatedData = $request->validated();
        $items = $request->input('items');
        
        $pointItemRequest = new PointItemRequest();
        $validatedItems = [];
        $allItemErrors = [];

        foreach ($items as $index => $item) {
            // Validate point items data
            $pointItemsValidator = Validator::make(
                $item,
                $pointItemRequest->rules(),
                $pointItemRequest->messages(),
                $pointItemRequest->attributes()
            );
            
            if ($pointItemsValidator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($pointItemsValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $pointItemsValidator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedItems[] = $validatedItem;
            }
        }

        
        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) {
            // dd($request);
            return redirect()->back()->withErrors($allItemErrors)->withInput();
        } else {
            $point = Point::create([
                'name'=>$validatedData['name'],
                'point' => (int) $validatedData['point'],
            ]);

            if (isset($validatedData['image']) && $validatedData['image'] instanceof \Illuminate\Http\UploadedFile) {
                $point->addMedia($validatedData['image'])->toMediaCollection('point');
            }
    
            if(count($validatedItems) > 0) {
                foreach ($validatedItems as $value) {
                    PointItem::create([
                        'point_id' => $point->id,
                        'inventory_item_id' => $value['inventory_item_id'],
                        'item_qty' => $value['item_qty'],
                    ]);
                }
            }
    
            $message = [ 
                'severity' => 'success', 
                'summary' => 'New redeemable item has been added successfully.'
            ];
    
            return redirect()->back()->with(['message' => $message]);
        }
    }

    /**
     * Update point details and its items.
     */
    public function updatePoint(PointRequest $request, string $id)
    {        
        $validatedData = $request->validated();
        $items = $request->input('items');
        
        $pointItemRequest = new PointItemRequest();
        $validatedItems = [];
        $allItemErrors = [];

        foreach ($items as $index => $item) {
            $pointItemsValidator = Validator::make(
                $item,
                $pointItemRequest->rules(),
                $pointItemRequest->messages(),
                $pointItemRequest->attributes()
            );
            
            if ($pointItemsValidator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($pointItemsValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $pointItemsValidator->validated();
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
        
        $existingPoint = isset($id) ? Point::with('pointItems')->find($id) : null;

        // dd($request->all());
        // Delete point items
        if ($request->itemsDeletedBasket && count($request->itemsDeletedBasket) > 0 && $existingPoint) {
            $existingPoint->pointItems()
                            ->whereIn('id', $request->itemsDeletedBasket)
                            ->delete();
        }

        // Update point details
        if (!is_null($existingPoint)) {
            $existingPoint->update([
                'name'=>$validatedData['name'],
                'point' => $validatedData['point'],
            ]);

            if($validatedData->hasFile('image'))
            {
                $existingPoint->clearMediaCollection('point');
                $existingPoint->addMedia($validatedData->image)->toMediaCollection('point');
            }
        }

        // Update point item(s) details
        if(count($validatedItems) > 0) {
            foreach ($validatedItems as $value) {
                if (isset($value['id'])) {
                    $existingPointItem = PointItem::find($value['id']);

                    $existingPointItem->update([
                        'point_id' => $request->id,
                        'inventory_item_id' => $value['inventory_item_id'],
                        'item_qty' => $value['item_qty'],
                    ]);
                }
            }
        }

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Changes saved.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }
    
    /**
     * Delete point and its items.
     * Or if deleted from edit form, will be deleting only the item.
     */
    public function deletePoint(Request $request, string $id)
    {
        $existingPoint = Point::with('pointItems')->find($id);

        $message = [
            'severity' => 'error',
            'summary' => 'Error deleting redeemable item.',
        ];
        
        if ($existingPoint) {
            // Soft delete all related items in bulk
            $existingPoint->pointItems()->delete();
        
            // Soft delete the point
            $existingPoint->delete();
        
            $message = [
                'severity' => 'success',
                'summary' => 'Selected redeemable item has been deleted successfully.',
            ];
        }
        
        return Redirect::route('loyalty-programme')->with(['message' => $message]);
    }
    
    /**
     * Show Recent Redemptions page.
     */
    public function showRecentRedemptions(Request $request)
    {
        $dateFilter = [
            now()->subDays(30)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
        ];

        // Apply the date filter (single date or date range)
        $redemptionHistories = PointHistory::whereDate('created_at', '>=', $dateFilter[0])
                                            ->whereDate('created_at', '<=', $dateFilter[1])
                                            ->with(['redeemableItem:id,product_name', 'handledBy:id,name'])
                                            ->where('type', 'Used')
                                            ->orderBy('created_at', 'desc')
                                            ->get();

        return Inertia::render('LoyaltyProgramme/Partial/RecentRedemptionHistory', [
            'redemptionHistories' => $redemptionHistories,
            'defaultDateFilter' => $dateFilter
        ]);
    }

    public function showPointDetails(Request $request, string $id)
    {   
        $dateFilter = [
            now()->subDays(30)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
        ];

        // Apply the date filter (single date or date range)
        $redemptionHistories = PointHistory::where('point_id', $id)
                                            ->whereDate('created_at', '>=', $dateFilter[0])
                                            ->whereDate('created_at', '<=', $dateFilter[1])
                                            ->with(['point:id,name,point', 'handledBy:id,name'])
                                            ->orderBy('created_at', 'desc')
                                            ->get();

        $redeemableItem = Point::with(['pointItems', 'pointItems.inventoryItem'])->find($id);
        $redeemableItem->image = $redeemableItem->getFirstMediaUrl('point');

        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        // Clear the flashed messages from the session
        // $request->session()->forget('message');
        // $request->session()->save();
        
        return Inertia::render('LoyaltyProgramme/Partial/PointDetail', [
            'redemptionHistories' => $redemptionHistories,
            'defaultDateFilter' => $dateFilter,
            'redeemableItem' => $redeemableItem,
            'inventoryItems' => $this->getAllInventoryWithItems(),
            'message' => $message ?? []
        ]);
    }

    public function getRecentRedemptionHistories(Request $request, string $id = null)
    {
        $dateFilter = $request->input('dateFilter');
        
        $dateFilter = array_map(function ($date) {
                            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
                        }, $dateFilter);

        // Apply the date filter (single date or date range)
        $query = PointHistory::whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                                ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                                    $subQuery->whereDate('created_at', '<=', $dateFilter[1]);
                                });

        if ($id !== null) {
            $query->where('product_id', $id);
        }

        $data = $query->with(['redeemableItem:id,product_name', 'handledBy:id,name'])
                        ->where('type', 'Used')
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json($data);
    }
}
    