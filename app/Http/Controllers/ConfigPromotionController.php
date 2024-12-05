<?php

namespace App\Http\Controllers;

use App\Models\ConfigMerchant;
use App\Models\ConfigPromotion;
use App\Models\ItemCategory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ConfigPromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $nowDate = now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
        
        // ConfigPromotion::whereDate('promotion_from', '<=', $nowDate)
        //     ->whereDate('promotion_to', '>', $nowDate) 
        //     ->update(['status' => 'Active']);

        // ConfigPromotion::where(function ($query) use ($nowDate) {
        //         $query->whereDate('promotion_to', '<=', $nowDate)  
        //             ->orWhereDate('promotion_from', '>', $nowDate); 
        //     })
        //     ->update(['status' => 'Inactive']);

        $ActivePromotions = ConfigPromotion::where('status', 'Active')->get();
        $InactivePromotions = ConfigPromotion::where('status', 'Inactive')->get();

        $ActivePromotions->each(function ($active) {
            $active->promotion_image = $active->getFirstMediaUrl('promotion');
        });

        $InactivePromotions->each(function ($inactive) {
            $inactive->promotion_image = $inactive->getFirstMediaUrl('promotion');
        });

        $merchant = ConfigMerchant::first();
        $merchant->merchant_image = $merchant->getFirstMediaUrl('merchant_settings');

        $selectedTab = $request->session()->get('selectedTab');

        return Inertia::render('Configuration/MainConfiguration', [
            'ActivePromotions' => $ActivePromotions,
            'InactivePromotions' => $InactivePromotions,
            'merchant' => $merchant,
            'selectedTab' => $selectedTab ?? 0,
        ]);
    }

    public function getPromotions(){
        $ActivePromotions = ConfigPromotion::where('status', 'Active')->get();
        $InactivePromotions = ConfigPromotion::where('status', 'Inactive')->get();

        $ActivePromotions->each(function ($active) {
            $active->promotion_image = $active->getFirstMediaUrl('promotion');
        });

        $InactivePromotions->each(function ($inactive) {
            $inactive->promotion_image = $inactive->getFirstMediaUrl('promotion');
        });
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
        $todayDate = now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
        // dd($todayDate);
        if ($todayDate >= $request->promotion_from && $todayDate < $request->promotion_to) {
            $status = 'Active';
        } else {
            $status = 'Inactive';
        }

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
        
        // return Redirect::route('configurations.promotions.index');
        return redirect()->back();

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
        $todayDate = date('Y/m/d');
        if (($todayDate >= $request->promotion_from) && ($todayDate <= $request->todayDate)) {
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

        if($request->hasfile('promotion_image')) {
            $editPromotion->clearMediaCollection('promotion');
            $editPromotion->addMedia($request->promotion_image)->toMediaCollection('promotion');
        }

        return redirect()->back();

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
    // public function update(Request $request)
    // {
    //     $updateStock = ItemCategory::where('id', $request->id);
    //     if($updateStock){
    //         $updateStock->update([
    //             'low_stock_qty' => $request->low_stock_qty,
    //         ]);
    //     }


    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    // public function getStock()
    // {
    //     $stock = ItemCategory::query();

    //     $results = $stock->get();

    //     return response()->json($results);
    // }

    public function updateMerchant(Request $request)
    {
        $request->validate([
            'merchant_name' => ['required', 'string'],
            'merchant_contact' => ['required', 'string'],
            'merchant_address' => ['required', 'max:255'],
            'merchant_image' => ['required'],
        ]);

        $config_merchant = ConfigMerchant::find($request->merchant_id);

        $config_merchant->update([
            'merchant_name' => $request->merchant_name,
            'merchant_contact' => $request->merchant_contact,
            'merchant_address' => $request->merchant_address,
        ]);

        if($request->hasFile('merchant_image')){
            $config_merchant->clearMediaCollection('merchant_settings');
            $config_merchant->addMedia($request->merchant_image)->toMediaCollection('merchant_settings');
        }

        return redirect()->back()->with('updated succesfully');
    }

    public function addTax(Request $request)
    {   
        $newTax = Setting::find($request->id);

        $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('settings', 'id')
                    ->whereNull('deleted_at')
                    ->ignore($newTax ? $newTax->id : null),
            ],
            'value' => ['required'],
        ], [
            'name.required' => 'The name field is required.',
            'name.max' => 'The name field must not exceed 255 characters.',
            'name.unique' => 'The name already exists in the tax settings.',
            'value.required' => 'This field is required.',
        ]);


        if ($newTax) {
            // Update the existing tax
            $newTax->update([
                'name' => $request->name,
                'value' => round($request->value, 2),
                'type' => 'tax',
                'value_type' => 'percentage',
            ]);
        } else {
            // Create a new tax
            Setting::create([
                'name' => $request->name,
                'value' => $request->value,
                'type' => 'tax',
                'value_type' => 'percentage',
            ]);
        }
    }

    public function getTax()
    {
        $stock = Setting::query();

        $results = $stock->where('type', 'tax')->get();

        return response()->json($results);
    }

    public function deleteTax (String $id)
    {
        $deletingId = Setting::find($id);
        if($deletingId){
            $deletingId->delete();
        };
    }

    public function pointCalculate (Request $request)
    {
        $isPointExist = Setting::where('name', 'Point')->first();

        if($isPointExist)
        {
            $isPointExist->update([
                'type' => 'point',
                'value_type' => 'price',
                'value' => $request->value,
                'point' => $request->point,
            ]);
        } else {
            Setting::create([
                'name' => 'Point',
                'type' => 'point',
                'value_type' => 'price',
                'value' => $request->value,
                'point' => $request->point,
            ]);
        }
    }

    public function getPoint() {
        $setting = Setting::where('name', 'Point')->first(['point', 'value']);
    
        return response()->json([
            'existingPoint' => (string)$setting->point ?? null,
            'respectiveValue' => $setting->value ?? null,
        ]);
    }
}
