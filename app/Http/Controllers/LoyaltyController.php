<?php

namespace App\Http\Controllers;

use App\Http\Requests\PointItemRequest;
use App\Http\Requests\PointRequest;
use App\Http\Requests\RankingRequest;
use App\Http\Requests\RankingRewardRequest;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Models\PointItem;
use App\Models\SaleHistory;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Models\Ranking;
use App\Models\RankingReward;
use App\Models\Customer;
use App\Models\Point;
use App\Models\PointHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;

class LoyaltyController extends Controller
{
    public function index(Request $request){
        $tiers = Ranking::with([
                                'rankingRewards', 
                                'rankingRewards.inventoryItem',
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
                                
                                return $rank;
                            }); 

        $redeemableItems = Point::with([
                                    'pointItems',
                                    'pointItems.inventoryItem:id,stock_qty',
                                    'pointHistories',
                                    'pointHistories.user:id,name'
                                ])
                                ->orderBy('id')
                                ->get()
                                ->map(function ($point) {
                                    $pointItems = $point->pointItems;
                                    $minStockCount = 0;

                                    if (count($pointItems) > 0) {
                                        $stockCountArr = [];

                                        foreach ($pointItems as $key => $value) {
                                            $inventory_item = IventoryItem::select('stock_qty')
                                                                                ->find($value['inventory_item_id']);

                                            $stockQty = $inventory_item->stock_qty;
                                            
                                            $stockCount = (int)round($stockQty / $value['item_qty']);
                                            array_push($stockCountArr, $stockCount);
                                        }
                                        $minStockCount = min($stockCountArr);
                                    }
                                    $point['stock_left'] = $minStockCount; 

                                    return $point;
                                }); 

        $inventoryItems = Iventory::withWhereHas('inventoryItems')
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

        $totalPointsGivenAway = SaleHistory::with('product:id,point')
                                            ->orderBy('id')
                                            ->get()
                                            ->map(function ($sale) {
                                                $product = $sale->product;
                                                $totalPointsGivenAway = 0;

                                                $totalPointsGivenAway += $sale['qty'] * (int)$product['point'];

                                                return $totalPointsGivenAway;
                                            });

        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        // Clear the flashed messages from the session
        // $request->session()->forget('message');
        // $request->session()->save();

        return Inertia::render('LoyaltyProgramme/LoyaltyProgramme', [
            'message' => $message ?? [],
            'tiers' => $tiers,
            'redeemableItems' => $redeemableItems,
            'inventoryItems' => $inventoryItems,
            'totalPointsGivenAway' => $totalPointsGivenAway[0] ?? 0,
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

            if ($reward['reward_type'] !== 'Bonus Point') {
                $rules['valid_period_from'] = str_replace('nullable', 'required', $rules['valid_period_from']);
                $rules['valid_period_to'] = str_replace('nullable', 'required', $rules['valid_period_to']);
            }

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
        if (!empty($allItemErrors)) {
            return redirect()->back()->withErrors($allItemErrors)->withInput();
        }

        $ranking = Ranking::create([
            'name'=>$validatedData['name'],
            'min_amount' => $validatedData['min_amount'],
            'reward' => $validatedData['reward'],
            'icon' => ''
        ]);

        // dd($validatedRankingRewards);

        if($validatedData['reward'] === 'active' && count($validatedRankingRewards) > 0) {
            foreach ($validatedRankingRewards as $value) {
                RankingReward::create([
                    'ranking_id' => $ranking->id,
                    'reward_type' => $value['reward_type'],
                    'discount' => $value['discount'],
                    'min_purchase_amount' => $value['min_purchase_amount'],
                    'valid_period_from'=>$value['valid_period_from'],
                    'valid_period_to'=>$value['valid_period_to'],
                    'bonus_point'=>$value['bonus_point'],
                    'min_purchase'=>$value['min_purchase'],
                    'free_item'=>$value['free_item'],
                    'item_qty'=>$value['item_qty'],
                ]);
            }
        }

        $message = [ 
            'severity' => 'success', 
            'summary' => 'New tier has been added successfully.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    public function showRecord()
    {
        $data = Ranking::with([
                                'rankingRewards', 
                                'rankingRewards.inventoryItem',
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
                                
                                return $rank;
                            }); 

        return response()->json($data);
    }

    public function showTierDetails(Request $request, string $id)
    {
        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        // Clear the flashed messages from the session
        // $request->session()->forget('message');
        // $request->session()->save();

        return Inertia::render('LoyaltyProgramme/Partial/TierDetail', [
            'id' => $id,
            'message' => $message ?? []
        ]);
    }

    public function showMemberList(Request $request)
    {   
        $id = $request->query('id');
        // $customers = Customer::where('ranking', $id)
        //                         ->with('ranking', 'ranking.rankingRewards')
        //                         ->get(); 

        // foreach ($customers as $customer) {
        //     $customer->total_spend = 0; //Hard core for now
        // }
        
        $ranking = Ranking::with(['rankingRewards', 'customers'])->find($id); 

        return response()->json([
            'customers' => $ranking->customers,
            'ranking' => $ranking,
            'rankingRewards' => $ranking->rankingRewards,
        ]);
    }

    public function showTierData(Request $request)
    {   
        $id = $request->query('id');
        $ranking = Ranking::with('rankingRewards')
                            ->find($id); 
        // $rankingRewards = RankingReward::where('ranking_id', $id)->get();
        return response()->json([
            'ranking' => $ranking,
            'rankingRewards' => $ranking->rankingRewards,
        ]);
    }

    public function updateTier(RankingRequest $request, string $id)
    {        
        // Get validated tier data
        $validatedData = $request->validated();

        // Get tier items
        $rewardsData = $request->input('rewards');
        
        $rankingRewardRequest = new RankingRewardRequest();
        $validatedRankingRewards = [];
        $allItemErrors = [];

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

            if ($reward['reward_type'] !== 'Bonus Point') {
                $rules['valid_period_from'] = str_replace('nullable', 'required', $rules['valid_period_from']);
                $rules['valid_period_to'] = str_replace('nullable', 'required', $rules['valid_period_to']);
            }

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
        if (!empty($allItemErrors)) {
            return redirect()->back()->withErrors($allItemErrors)->withInput();
        }

        if (isset($id)) {
            $existingRanking = Ranking::find($id);

            $existingRanking->update([
                'name'=>$validatedData['name'],
                'min_amount' => $validatedData['min_amount'],
                'reward' => $validatedData['reward'],
                'icon' => ''
            ]);
        }

        if($validatedData['reward'] === 'active' && count($validatedRankingRewards) > 0) {
            foreach ($validatedRankingRewards as $value) {
                if (isset($value['id'])) {
                    $existingRankingReward = RankingReward::find($value['id']);

                    $existingRankingReward->update([
                        'ranking_id' => $request->id,
                        'reward_type' => $value['reward_type'],
                        'discount' => $value['discount'],
                        'min_purchase_amount' => $value['min_purchase_amount'],
                        'valid_period_from'=>$value['valid_period_from'],
                        'valid_period_to'=>$value['valid_period_to'],
                        'bonus_point'=>$value['bonus_point'],
                        'min_purchase'=>$value['min_purchase'],
                        'free_item'=>$value['free_item'],
                        'item_qty'=>$value['item_qty'],
                    ]);
                } else {
                    RankingReward::create([
                        'ranking_id' => $request->id,
                        'reward_type' => $value['reward_type'],
                        'discount' => $value['discount'],
                        'min_purchase_amount' => $value['min_purchase_amount'],
                        'valid_period_from'=>$value['valid_period_from'],
                        'valid_period_to'=>$value['valid_period_to'],
                        'bonus_point'=>$value['bonus_point'],
                        'min_purchase'=>$value['min_purchase'],
                        'free_item'=>$value['free_item'],
                        'item_qty'=>$value['item_qty'],
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

        // Delete point items
        if (count($request->itemsDeletedBasket) > 0 && !is_null($existingPoint)) {
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
                                            ->with(['point:id,name', 'user:id,name'])
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
                                            ->with(['point:id,name,point', 'user:id,name'])
                                            ->orderBy('created_at', 'desc')
                                            ->get();

        $redeemableItem = Point::with(['pointItems', 'pointItems.inventoryItem'])->find($id);

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

    public function getPointHistories(Request $request, string $id = null)
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
            $query->where('point_id', $id);
        }

        $data = $query->with(['point:id,name', 'user:id,name'])
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json($data);
    }
}
    