<?php

namespace App\Http\Controllers;

use App\Models\ClassificationCode;
use App\Models\ConfigMerchant;
use App\Models\ConfigPromotion;
use App\Models\ItemCategory;
use App\Models\MSICCodes;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
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

        $promotions = $this->getPromotions();
        // $ActivePromotions = ConfigPromotion::where('status', 'Active')->get();
        // $InactivePromotions = ConfigPromotion::where('status', 'Inactive')->get();

        // $ActivePromotions->each(function ($active) {
        //     $active->promotion_image = $active->getFirstMediaUrl('promotion');
        // });

        // $InactivePromotions->each(function ($inactive) {
        //     $inactive->promotion_image = $inactive->getFirstMediaUrl('promotion');
        // });

        $merchant = ConfigMerchant::with('classificationCode', 'msicCode')->first();
        
        if ($merchant) {
            $merchant->code = $merchant->classificationCode->code . ' - ' . $merchant->classificationCode->description;
            $merchant->msic = $merchant->msicCode->Code . ' - ' . $merchant->msicCode->Description;
            unset($merchant->classificationCode);
            unset($merchant->msicCode);
            $merchant->merchant_image = $merchant->getFirstMediaUrl('merchant_settings');
        }
        $selectedTab = $request->session()->get('selectedTab');

        return Inertia::render('Configuration/MainConfiguration', [
            'ActivePromotions' => $promotions['Active'] ?? [],
            'InactivePromotions' => $promotions['Inactive'] ?? [],
            'merchant' => $merchant,
            'selectedTab' => $selectedTab ?? 0,
        ]);
    }

    private function getPromotions(){
        return ConfigPromotion::whereIn('status', ['Active', 'Inactive'])
            ->get()
            ->map(function ($promotion) {
                $promotion->promotion_image = $promotion->getFirstMediaUrl('promotion');
                return $promotion;
            })
            ->groupBy('status');
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
        // Validate form request
        $validatedData = $request->validate(
            [
                'title' => 'required|string',
                'description' => 'required|string',
                'promotionPeriod' => 'required',
                'promotion_image' => 'required|image|max:8000',
            ], 
            [
                'required' => 'This field is required.',
                'promotion_image.max' => 'The size of the image is too big.',
            ]
        );

        // $data = $request->all();
        $todayDate = now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');

        if ($todayDate >= $request->promotion_from && $todayDate <= $request->promotion_to) {
            $status = 'Active';
        } else {
            $status = 'Inactive';
        }

        $promotion = ConfigPromotion::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'promotion_from' => $request->promotion_from,
            'promotion_to' => $request->promotion_to,
            'status' => $status,
        ]);
        
        activity()->useLog('create-promotion')
                    ->performedOn($promotion)
                    ->event('added')
                    ->withProperties([
                        'created_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'promotion_name' => $promotion->title,
                    ])
                    ->log("Promotion '$promotion->title' is added."); 

        if ($request->hasfile('promotion_image'))
        {
            $promotion->addMedia($request->promotion_image)->toMediaCollection('promotion');
        }
        
        $promotions = $this->getPromotions();
        
        // return Redirect::route('configurations.promotions.index');
        return response()->json($promotions);

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
        // Validate form request
        $validatedData = $request->validate(
            [
                'title' => 'required|string',
                'description' => 'required|string',
                'promotionPeriod' => 'required',
                'promotion_image' => 'required',
            ], 
            ['required' => 'This field is required.']
        );

        $editPromotion = ConfigPromotion::where('id', $request->id)->first();

        $editPromotion->update([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'promotion_from' => $request->promotion_from,
            'promotion_to' => $request->promotion_to,
            'status' => $editPromotion->status,
        ]);

        activity()->useLog('edit-promotion-detail')
                    ->performedOn($editPromotion)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'title' => $editPromotion->title,
                    ])
                    ->log("Promotion '$editPromotion->title' is updated.");

        if($request->hasfile('promotion_image')) {
            $editPromotion->clearMediaCollection('promotion');
            $editPromotion->addMedia($request->promotion_image)->toMediaCollection('promotion');
        }

        return redirect()->back();

    }

    public function delete(Request $request)
    {
        
        $editPromotion = ConfigPromotion::where('id', $request->id)->first();

        activity()->useLog('delete-promotion')
                    ->performedOn($editPromotion)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'promotion_name' => $editPromotion->title,
                    ])
                    ->log("$editPromotion->title is deleted.");

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
            'name' => ['required', 'string'],
            'tin_no' => ['required', 'string'],
            'registration_no' => ['required', 'string'],
            'msic_code' => ['required', 'integer'],
            'phone_no' => ['required', 'string'],
            'email_address' => ['required', 'string', 'email:strict'],
            'sst_registration_no' => ['required', 'string'],
            'description' => ['required', 'string'],
            'classification_code' => ['required', 'integer'],
            'merchant_image' => ['required', 'max:8000'],
            'irbm_client_id' => ['required'],
            'irbm_client_key' => ['required'],
        ], [
            'required' => 'This field is required.',
            'string' => 'Invalid input. Please try another.',
            'integer' => 'Invalid input. Please try another.',
            'email' => 'Invalid format. Please try again.',
            'merchant_image.max' => 'The size of the image is too big.',
        ]);

        $config_merchant = ConfigMerchant::where('id', $request->id)->first();

        $config_merchant->update([
            'name' => $request->name,
            'merchant_name' => $request->name,
            'tin_no' => $request->tin_no,
            'registration_no' => $request->registration_no,
            'msic_code' => $request->msic_code,
            'merchant_contact' => $request->phone_no,
            'email_address' => $request->email_address,
            'sst_registration_no' => $request->sst_registration_no,
            'description' => $request->description,
            'classification_code' => $request->classification_code,
            'irbm_client_id' => $request->irbm_client_id,
            'irbm_client_key' => $request->irbm_client_key,
        ]);

        if($request->hasFile('image')){
            $config_merchant->clearMediaCollection('merchant_settings');
            $config_merchant->addMedia($request->image)->toMediaCollection('merchant_settings');
        }

        $config_merchant->save();

        activity()->useLog('edit-merchant-detail')
                        ->performedOn($config_merchant)
                        ->event('updated')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'merchant_name' => $config_merchant->name
                        ])
                        ->log("Merchant $config_merchant->name's detail updated.");

        // return redirect()->back();
        $data = $this->getMerchantDetails();

        return response()->json($data);
    }

    public function addTax(Request $request)
    {  
        $taxesErrors = [];
        $validatedTaxes = [];
        foreach ($request->input('items') as $taxes) { 
            if (!isset($taxes['id'])) {
                continue; 
            }

            $validator = Validator::make($taxes, [
                'id' => 'required|integer',
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($taxes) {
                        if (isset($taxes['remarks'])) {
                            Setting::query()
                                ->when($taxes['remarks'] === 'added', function ($query) use ($value) {
                                    $query->where('name', $value)->whereNull('deleted_at');
                                })
                                ->when($taxes['remarks'] === 'edited', function ($query) use ($value, $taxes) {
                                    $query->where('name', $value)
                                          ->where('id', '!=', $taxes['id'])
                                          ->whereNull('deleted_at');
                                })
                                ->exists() && $fail("Tax name has already been taken.");
                        }
                    },
                ],
                'value' => 'required|numeric|min:0',
                'remarks' => 'string',
            ], [
                'name.required' => 'Tax name is required.',
                'value.required' => 'Tax value is required.',
            ]);
            

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $taxesErrors["taxes.{$taxes['id']}.$field"] = $messages;
                }
            } else {
                $validated = $validator->validated();
                if (isset($validated['id'])) {
                    $validated['id'] = $taxes['id'];
                }
                $validatedTaxes[] = $validated;
            }
        }
        
        if (!empty($taxesErrors)) {
            return redirect()->back()->withErrors($taxesErrors);
        }

        foreach($validatedTaxes as $items){
            if(isset($items['remarks'])){
                if ($items['remarks'] === 'added') {
                    Setting::create([
                        'name' => $items['name'],
                        'value' => $items['value'],
                        'type' => 'tax',
                        'value_type' => 'percentage',
                    ]);
    
                } else {
                    Setting::find($items['id'])
                        ->update([
                        'name' => $items['name'],
                        'value' => round($items['value'], 2),
                        'type' => 'tax',
                        'value_type' => 'percentage',
                    ]);
                }
            }
        }
        return redirect()->back();
    }

    public function getTax()
    {
        $stock = Setting::query();

        $results = $stock->where('type', 'tax')->select('id', 'name', 'value')->get();

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

            activity()->useLog('edit-point-settings')
                        ->performedOn($isPointExist)
                        ->event('updated')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                        ])
                        ->log("Point settings updated.");
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

    public function getPointExpirationSettings() {
        $settings = Setting::where('type', 'expiration')
                            ->get(['id', 'name', 'value_type', 'value']);

        $pointExpiration = $settings->filter(fn ($s) => $s->name === 'Point Expiration')->first();
        $pointExpirationNotification = $settings->filter(fn ($s) => $s->name === 'Point Expiration Notification')->first();
    
        return response()->json([
            'daysToExpire' => $pointExpiration->value,
            'daysToNotify' => $pointExpirationNotification->value,
        ]);
    }

    public function setPointExpiration(Request $request)
    {
        $pointExpiration = Setting::where([
                                    ['name', 'Point Expiration'],
                                    ['type', 'expiration'],
                                ])
                                ->first();

        if ($pointExpiration && $pointExpiration->value != $request->days_to_expire) {
            $pointExpiration->update([
                // 'value_type' => 'day',
                'value' => $request->days_to_expire,
            ]);
        }

        $pointExpirationNotification = Setting::where([
                                    ['name', 'Point Expiration Notification'],
                                    ['type', 'expiration'],
                                ])
                                ->first();

        if ($pointExpirationNotification && $pointExpirationNotification->value != $request->days_to_notify) {
            $pointExpirationNotification->update([
                // 'value_type' => 'day',
                'value' => $request->days_to_notify,
            ]);
        }

    }


    public function getClassificationCodes()
    {
        $codes = ClassificationCode::all();

        return response()->json($codes);
    }

    public function getMSICCodes()
    {
        $codes = MSICCodes::where('code', '!=', '00000')->get();

        return response()->json($codes);
    }

    private function getMerchantDetails()
    {
        $merchant = ConfigMerchant::with('classificationCode', 'msicCode')->first();

        if ($merchant) {
            $merchant->code = $merchant->classificationCode->code . ' - ' . $merchant->classificationCode->description;
            $merchant->msic = $merchant->msicCode->Code . ' - ' . $merchant->msicCode->Description;
            unset($merchant->classificationCode);
            unset($merchant->msicCode);
            $merchant->merchant_image = $merchant->getFirstMediaUrl('merchant_settings');
        }

        return $merchant;
    }

    public function refetchMerchant()
    {
        $data = $this->getMerchantDetails();

        return response()->json($data);
    }

    public function editAddress(Request $request)
    {
        $request->validate([
            'id' => ['required'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'integer', 'digits:5'],
            'area' => ['required', 'string'],
            'state' => ['required', 'string'],
        ], [
            'required' => 'This field is required.',
            'string' => 'Invalid input. Please try again.',
            'integer' => 'Invalid input. Please try again.',
            'max' => 'This input must not more than 255 characters.',
            'digits' => 'Invalid format.'
        ]);
        
        $targetMerchant = ConfigMerchant::find($request->id);
        $targetMerchant->update([
            'merchant_address_line1' => $request->address_line1,
            'merchant_address_line2' => $request->address_line2,
            'postal_code' => $request->postal_code,
            'area' => $request->area,
            'state' => $request->state,
        ]);

        $data = $this->getMerchantDetails();

        return response()->json($data);
    }

    public function editTax(Request $request)
    {
        $taxesErrors = [];
        $validatedTaxes = [];

        foreach ($request->input('taxes') as $taxes) { 
            if (!isset($taxes['id'])) {
                continue; 
            }

            $validator = Validator::make($taxes, [
                'id' => 'required|integer',
                'name' => 'required|string|max:255',
                'percentage' => 'required|numeric|min:0',
            ], [
                'name.required' => 'Tax name is required.',
                'percentage.required' => 'Tax value is required.',
            ]);
            

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $taxesErrors["$field.{$taxes['id']}"] = $messages;
                }
            } else {
                $validated = $validator->validated();
                if (isset($validated['id'])) {
                    $validated['id'] = $taxes['id'];
                }
                $validatedTaxes[] = $validated;
            }
        }
        
        if (!empty($taxesErrors)) {
            return redirect()->back()->withErrors($taxesErrors);
        }

        foreach($validatedTaxes as $items) {
            $tax = Setting::where('id', $items['id'])->first();

            activity()->useLog('edit-tax-settings')
                        ->performedOn($tax)
                        ->event('updated')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'tax_name' => $tax->name
                        ])
                        ->log("Tax $tax->name updated.");

            $tax->update([
                'name' => $items['name'],
                'value' => round($items['percentage'], 2),
                'type' => 'tax',
                'value_type' => 'percentage',
            ]); 
        }

        return redirect()->back();
    }

    public function getCutOffTime()
    {
        $cutOffTime = Setting::where([
                                    ['name', 'Cut Off Time'],
                                    ['type', 'timer'],
                                ])
                                ->first();
        
        // Step 1: Convert '6.35' to hours and minutes
        [$hour, $minute] = explode('.', (string)$cutOffTime->value);
        $minute = str_pad((int) $minute, 2, '0', STR_PAD_RIGHT); // ensures it's 2 digits

        // Step 2: Format as 'HH:mm'
        $timeString = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . $minute;

        // Step 3 (optional): Convert to DateTime object (e.g., today at 06:35)
        $cutOffTime->value = Carbon::createFromFormat('H:i', $timeString);

        return response()->json($cutOffTime);
    }

    public function editCutOffTime(Request $request)
    {
        $request->validate([
            'start_at' => 'required|string',
        ], [
            'required' => 'This field is required.',
            'string' => 'Invalid input. Please try again.',
        ]);
        
        $cutOffTime = Setting::where([
                                    ['name', 'Cut Off Time'],
                                    ['type', 'timer'],
                                ])
                                ->first();

        $cutOffTime->update(['value' => $request->start_at]);

        $cutOffTime->refresh();
        
        // Step 1: Convert '6.35' to hours and minutes
        [$hour, $minute] = explode('.', (string)$cutOffTime->value);
        $minute = str_pad((int) $minute, 2, '0', STR_PAD_RIGHT); // ensures it's 2 digits

        // Step 2: Format as 'HH:mm'
        $timeString = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . $minute;

        // Step 3 (optional): Convert to DateTime object (e.g., today at 06:35)
        $cutOffTime->value = Carbon::createFromFormat('H:i', $timeString);

        return response()->json($cutOffTime);
    }

    public function getAutoUnlockDuration() {
        $setting = Setting::where('name', 'Table Auto Unlock')
                            ->first(['name', 'value_type', 'value']);
    
        return response()->json($setting);
    }

    public function updateAutoLockDuration(Request $request)
    {
        $isSettingExist = Setting::where('name', 'Table Auto Unlock')->first();

        if($isSettingExist) {
            $isSettingExist->update([
                'value_type' => $request->value_type,
                'value' => $request->value,
            ]);

            activity()->useLog('edit-security-settings')
                        ->performedOn($isSettingExist)
                        ->event('updated')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                        ])
                        ->log("Security settings updated.");
        } else {
            Setting::create([
                'name' => 'Table Auto Unlock',
                'type' => 'lock_timer',
                'value_type' => $request->value_type,
                'value' => $request->value,
            ]);
        }

    }
}
