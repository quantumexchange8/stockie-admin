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
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Log;
use Validator;

class ConfigPromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $nowDate = now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
        
        ConfigPromotion::whereDate('promotion_from', '<=', $nowDate)
            ->whereDate('promotion_to', '>', $nowDate) 
            ->update(['status' => 'Active']);

        ConfigPromotion::where(function ($query) use ($nowDate) {
                $query->whereDate('promotion_to', '<=', $nowDate)  
                    ->orWhereDate('promotion_from', '>', $nowDate); 
            })
            ->update(['status' => 'Inactive']);

        $ActivePromotions = ConfigPromotion::where('status', 'Active')->get();
        $InactivePromotions = ConfigPromotion::where('status', 'Inactive')->get();

        $ActivePromotions->each(function ($active) {
            $active->promotion_image = $active->getFirstMediaUrl('promotion');
        });

        $InactivePromotions->each(function ($inactive) {
            $inactive->promotion_image = $inactive->getFirstMediaUrl('promotion');
        });

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
        // dd($data);
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
        // dd($promotion);

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

        if($request->hasfile('image')) {
            $editPromotion->clearMediaCollection('promotion');
            $editPromotion->addMedia($request->image)->toMediaCollection('promotion');
        }
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
    public function update(Request $request)
    {
        $updateStock = ItemCategory::where('id', $request->id);
        if($updateStock){
            $updateStock->update([
                'low_stock_qty' => $request->low_stock_qty,
            ]);
        }


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
            'merchant_name' => ['required', 'string'],
            'merchant_contact' => ['required', 'numeric'],
            'merchant_address' => ['required', 'max:255'],
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
        // dd($request->all());
        $newTax = TaxSetting::find($request->id);

        $rules = [
            'name' => ['required',
                        'max:255',
                        Rule::unique('tax_settings', 'name')->whereNull('deleted_at')->ignore($newTax ? $newTax->id : null)
                    ],
            'percentage' => ['required'],
        ];

        $requestMessages = [
            'name.required' => 'The name field is required.',
            'name.max' => 'The name field must not exceed 255 characters.',
            'name.unique' => 'The name already exists in the tax settings.',
            'percentage.required' => 'This field is required.',
        ];

        // if (!$newTax) {
        //     $rules['name'][] = Rule::unique('tax_settings', 'name')->whereNull('deleted_at');
        // }

        $taxValidator = Validator::make(
            $request->all(),
            $rules,
            $requestMessages
        );

        if ($taxValidator->fails()) {
            Log::info($taxValidator->errors());
            return redirect()
                    ->back()
                    ->withErrors($taxValidator)
                    ->withInput();
        }

        if ($newTax) {
            // Update the existing tax
            $newTax->update([
                'name' => $request->name,
                'percentage' => $request->percentage,
            ]);
        } else {
            // Create a new tax
            TaxSetting::create([
                'name' => $request->name,
                'percentage' => $request->percentage,
            ]);
        }
    }

    public function getTax()
    {

        $stock = TaxSetting::query();

        $results = $stock->get();

        return response()->json($results);
    }

    public function editTax (Request $request)
    {
        dd($request->all());

        return response()->json($request);
    }

    public function deleteTax (String $id)
    {
        $deletingId = TaxSetting::find($id);
        if($deletingId){
            $deletingId->delete();
        };
    }
}
