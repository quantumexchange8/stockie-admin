<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConfigPromotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    /**
     * Get the 5 most recent promotions
     */
    public function getMostRecentPromotions()
    {
        $allPromotions = ConfigPromotion::where('status', 'Active')
                                        ->limit(5)
                                        ->orderByDesc('promotion_from')
                                        ->get()
                                        ->map(function ($promotion) {
                                            $promotion->promotion_image = $promotion->getFirstMediaUrl('promotion');
                                            return $promotion;
                                        });

        $response = [
            'promotions' => $allPromotions ?? [],
        ];

        return response()->json($response);
    }

    /**
     * Get all promotions
     */
    public function getAllPromotions(Request $request)
    {
        $allPromotions = ConfigPromotion::where('status', 'Active')
                                        ->orderByDesc('promotion_from')
                                        ->get()
                                        ->map(function ($promotion) {
                                            $promotion->promotion_image = $promotion->getFirstMediaUrl('promotion');
                                            return $promotion;
                                        });

        $response = [
            'promotions' => $allPromotions ?? [],
        ];

        return response()->json($response);
    }
    
    /**
     * Get individual promotion's details
     */
    public function getPromotionDetails(Request $request)
    {
        $promotion = ConfigPromotion::findOrFail($request->id);
                        
        $promotion->promotion_image = $promotion->getFirstMediaUrl('promotion');
                        
        $response = [
            'promotion' => $promotion,
        ];

        return response()->json($response);
    }
}
