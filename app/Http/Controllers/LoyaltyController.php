<?php

namespace App\Http\Controllers;
use App\Http\Requests\RankingRequest;
use App\Http\Requests\RankingRewardRequest;
use App\Models\Iventory;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Models\Ranking;
use App\Models\RankingReward;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redirect;

class LoyaltyController extends Controller
{
    public function index(){
        return Inertia::render('LoyaltyProgramme/LoyaltyProgramme');
    }


    public function store(RankingRequest $request)
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

        return redirect()->back()->with('Created Successfully');
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

    public function showTierDetails(string $id)
    {
        return Inertia::render('LoyaltyProgramme/Partial/TierDetail', ['id' => $id]);
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

            if (isset($reward['id'])) {}

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

        return redirect()->back()->with('Updated Successfully');
        // return response()->json(['message' => 'Tier data updated successfully']);
    }

    /**
     * Get all inventory items along with its items.
     */
    public function getAllInventoryWithItems()
    {
        $data = Iventory::withWhereHas('inventoryItems')
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
        
        return response()->json($data);
    }
     
    /**
     * Delete tier and its rewards as well as updating all its members' tier status.
     */
    public function deleteTier(Request $request, string $id)
    {
        $existingRanking = Ranking::with('rankingRewards')->find($id);

        if ($existingRanking) {
            // Soft delete all related ranking rewards in bulk
            $existingRanking->rankingRewards()->delete();
    
            // Soft delete the ranking
            $existingRanking->delete();
        }

        return redirect()->back();
    }
}
    