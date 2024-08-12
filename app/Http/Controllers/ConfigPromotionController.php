<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigPromotionRequest;
use App\Models\ConfigMerchant;
use App\Models\ConfigPromotion;
use App\Models\ItemCategory;
use App\Models\TaxSetting;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ConfigPromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $nowDate = Carbon::now();
        $ActivePromotions = ConfigPromotion::where('status', 'Active')->get();
        $InactivePromotions = ConfigPromotion::where('status', 'Inactive')->get();

        $merchant = ConfigMerchant::first();

        return Inertia::render('Configuration/MainConfiguration', [
            'ActivePromotions' => $ActivePromotions,
            'InactivePromotions' => $InactivePromotions,
            'merchant' => $merchant,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $todayDate = new DateTime();
        if (($todayDate >= $request->promotion_from) && ($todayDate < $request->todayDate)) {
            $status = 'Active';
        } else {
            $status = 'Inactive';
        }
        // dd($data);

        $promotion = ConfigPromotion::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'promotion_from' => $data['promotion_from'],
            'promotion_to' => $data['promotion_to'],
            'image' => $data['image'],
            'status' => $status,
        ]);

        if ($request->hasfile('image'))
        {
            $promotion->addMedia($request->image)->toMediaCollection('promotion');
        }
        
        return Redirect::route('configurations.promotions.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        

        $todayDate = new DateTime();
        if (($todayDate >= $request->promotion_from) && ($todayDate < $request->todayDate)) {
            $status = 'Active';
        } else {
            $status = 'Inactive';
        }
        $editPromotion = ConfigPromotion::find($request->id);

        $editPromotion->update([
            'title' => $request->title,
            'description' => $request->description,
            'promotion_from' => $request->promotion_from,
            'promotion_to' => $request->promotion_to,
            'status' => $status,
        ]);
    }

    public function delete(Request $request)
    {
        
        $editPromotion = ConfigPromotion::find($request->id);
        $editPromotion->delete();

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getStock()
    {
        $stock = ItemCategory::query();

        $results = $stock->get();

        return response()->json($results);
    }

    public function updateMerchant(Request $request)
    {

        $request->validate([
            'merchant_name' => ['required'],
            'merchant_contact' => ['required'],
            'merchant_address' => ['required'],
        ]);

        $config_merchant = ConfigMerchant::find($request->merchant_id);

        $config_merchant->update([
            'merchant_name' => $request->merchant_name,
            'merchant_contact' => $request->merchant_contact,
            'merchant_address' => $request->merchant_address,
        ]);

        return redirect()->back()->with('updated succesfully');
    }

    public function addTax(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'percentage' => ['required'],
        ]);

        $tax = TaxSetting::create([
            'name' => $request->name,
            'percentage' => $request->percentage,
        ]);

        return redirect()->back()->with('updated succesfully');
    }

    public function getTax()
    {

        $stock = TaxSetting::query();

        $results = $stock->get();

        return response()->json($results);
    }
}
