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

class LoyaltyController extends Controller
{
    public function index(){
        return Inertia::render('LoyaltyProgramme/LoyaltyProgramme');
    }


    public function store(RankingRequest $request)
    {
        // $validatedData = $request->validate([
        //     'name' => 'required|string|max:255|unique:rankings',
        //     'min_amount' => 'required|string',
        //     'reward' => 'required|string',
        //     'rewards' => 'required|array',
        // ]);
        
        // Get validated product data
        $validatedData = $request->validated();

        // Get product items
        $rewardsData = $request->input('rewards');
        
        $rankingRewardRequest = new RankingRewardRequest();
        $validatedRankingRewards = [];
        $allItemErrors = [];

        foreach ($rewardsData as $index => $reward) {
            $rules = $rankingRewardRequest->rules();
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

        if($request->reward==='inactive'){
            $ranking = Ranking::create([
                'name'=>$request->name,
                'min_amount' => $request->min_amount,
                'reward' => $request->reward,
                'icon' => ''
            ]);
        } else {
            $ranking = Ranking::create([
                'name'=>$request->name,
                'min_amount' => $request->min_amount,
                'reward' => $request->reward,
                'icon' => ''
            ]);

            if ($validatedData['rewards'] && count($validatedData['rewards']) > 0) {
                foreach ($validatedData['rewards'] as $rewardData) {
                    RankingReward::create([
                        'ranking_id' => $ranking->id,
                        'reward_type' => $rewardData['type'],
                        'discount' => $rewardData['amount'],
                        'min_purchase_amount' => $rewardData['min_purchase_amount'],
                        'valid_period_from'=>$rewardData['valid_period_from'],
                        'valid_period_to'=>$rewardData['valid_period_to'],
                        'bonus_point'=>$rewardData['bonus_point'],
                        'min_purchase'=>$rewardData['min_purchase'],
                        'free_item'=>$rewardData['free_item'],
                        'item_qty'=>$rewardData['item_qty'],
                    ]);
                }
            }
        }
    }

    public function showRecord()
    {
        $rankings = Ranking::with('rankingRewards', 'customer')->orderBy('id')->get();

        $groupedRankings = $rankings->groupBy('name');

        $data = $groupedRankings->map(function ($group) {
            return [
                'id'=>$group->first()->id,
                'name' => $group->first()->name,
                'min_amount' => $group->first()->min_amount,
                'icon' => $group->first()->icon,
                'type_all' => $group->flatMap(function ($item) {
                    return $item->rankingRewards->pluck('reward_type')->all();
                })->unique()->implode(', '),
                'member' => $group->first()->customer->count(),
            ];
        })->values(); 

        return response()->json($data);
    }

    public function showTierDetails(string $id)
    {
        return Inertia::render('LoyaltyProgramme/Partial/TierDetail', ['id' => $id]);
    }

    public function showMemberList(Request $request)
    {   
        $id = $request->query('id');
        $customers = Customer::where('ranking', $id)
                                ->with('ranking', 'ranking.rankingRewards')
                                ->get();

        $customerCount = $customers->count();
        foreach ($customers as $customer) {
            $customer->total_spend = 0; //Hard core for now
        }
        $ranking = Ranking::find($id); 

        $rankingRewards = RankingReward::where('ranking_id', $id)->get();
        return response()->json([
            'customers' => $customers,
            'ranking' => $ranking,
            'rankingRewards' => $rankingRewards,
            'count' => $customerCount,
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

    public function updateTierData(Request $request)
    {
        $id = $request->query('id');
        $rankingData = $request->input('ranking');
        $rankingRewardsData = $request->input('rankingRewards');

        // Update Ranking
        $ranking = Ranking::findOrFail($id);
        $ranking->update($rankingData);

        // Update or Create RankingRewards
        foreach ($rankingRewardsData as $rewardData) {
            $rewardId = $rewardData['id']; // Assuming each reward has an 'id' field

            if ($rewardId) {
                // Update existing reward
                $reward = RankingReward::findOrFail($rewardId);
                $reward->update($rewardData);
            } else {
                // Create new reward
                $rewardData['ranking_id'] = $id; // Attach ranking_id to new reward
                RankingReward::create($rewardData);
            }
        }

        return response()->json(['message' => 'Tier data updated successfully']);
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
    public function deleteProductItem(Request $request, string $id)
    {
        
    }
}
    