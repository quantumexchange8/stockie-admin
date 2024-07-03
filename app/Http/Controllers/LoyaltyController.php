<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Models\Ranking;
use App\Models\RankingReward;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class LoyaltyController extends Controller
{
   public function index(){
    return Inertia::render('LoyaltyProgramme/LoyaltyProgramme');
    }


public function store(Request $request){


    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'min_amount' => 'required|numeric',
        'reward' => 'required|string',
        'rewards' => 'required|array',

    ]);

 
  // dd($request->all());

    if($request->reward==='inactive'){
        $ranking = Ranking::create([
                        'name'=>$request->name,
                        'min_amount' => $request->min_amount,
                        'reward' => $request->reward,
                        'icon'=>'null',
                    ]);
    }
    else{

        $ranking = Ranking::create([
            'name'=>$request->name,
            'min_amount' => $request->min_amount,
            'reward' => $request->reward,
            'icon'=>'null',
        ]);


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
    