<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountRequest;
use App\Models\BillDiscount;
use App\Models\Category;
use App\Models\ConfigDiscount;
use App\Models\ConfigDiscountItem;
use App\Models\Product;
use App\Models\Ranking;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Log;

class ConfigDiscountController extends Controller
{
    public function getDiscount(Request $request) {
        // dd($request->input('date'));
        $dateFilter = $request->input('date');

        if($request->input('id') !== []){
            $currentDiscount = ConfigDiscount::select('id', 'discount_from', 'discount_to')
                                                ->firstWhere('id', $request->input('id'));
        }
        $categories = Category::select(['id', 'name'])
                                ->orderBy('id')
                                ->get()
                                ->map(function ($category) {
                                    return [
                                        'text' => $category->name,
                                        'value' => $category->id
                                    ];
                                });
        // $productsAvailable = Product::with('discountItems.discount')
        $productsAvailable = Product::when(!empty($dateFilter), function ($query) use ($dateFilter) {
                                            $query->whereDoesntHave('discountItems.discount', function ($query) use ($dateFilter) {
                                                $query->where(function ($q) use ($dateFilter) {
                                                    if (count($dateFilter) === 1) {
                                                        $q->whereBetween($dateFilter[0], ['discount_from', 'discount_to']);
                                                    } else {
                                                        $q->where(function ($subQuery) use ($dateFilter) {
                                                            $subQuery->where('discount_from', '<=', $dateFilter[1])
                                                                    ->where('discount_to', '>=', $dateFilter[0]);
                                                        });
                                                    }
                                                });
                                            });
                                        })
                                        ->with([
                                            'discountItems' => function ($query) {
                                                $query->select('id', 'discount_id', 'product_id', 'price_before', 'price_after');
                                            },
                                            'discountItems.discount' => function ($query) {
                                                $query->select('id', 'discount_from', 'discount_to');
                                            }
                                        ])
                                        ->select('id', 'product_name', 'price', 'category_id', 'status', 'discount_id')
                                        ->get();
        

        $productsAvailable->each(function ($productAvailable) use ($currentDiscount) {
            $productAvailable->image = $productAvailable->getFirstMediaUrl('product');
        
            if($currentDiscount){
                $productAvailable->overlap = $productAvailable['discountItems']->some(function($discount) use ($currentDiscount) {
                    return $discount->discount->discount_from < $currentDiscount->discount_to &&
                            $discount->discount->discount_to > $currentDiscount->discount_from;
                });
            }
        });

        return response()->json([
            'categories' => $categories,
            'productsAvailable' => $productsAvailable,
        ]);
    }

    public function createDiscount (DiscountRequest $request) {
        // dd($request->all());
        $newDiscount = ConfigDiscount::create([
            'name' => $request->discount_name,
            'type' => $request->discount_type,
            'rate' => $request->discount_rate,
            'discount_from' => $request->discount_from,
            'discount_to' => $request->discount_to,
        ]);

        activity()->useLog('create-discount-item-group')
                    ->performedOn($newDiscount)
                    ->event('added')
                    ->withProperties([
                        'created_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'discount_name' => $newDiscount->name,
                    ])
                    ->log("Discount Item Group '$newDiscount->name' is added.");

        foreach ($request->discount_product as $discountProduct) {
            ConfigDiscountItem::create([
                'discount_id' => $newDiscount->id,
                'product_id' => $discountProduct['id'],
                'price_before' => $discountProduct['price'],
                'price_after' => $request->discount_type === 'percentage'
                                ? $discountProduct['price'] * (1 - $request->discount_rate / 100)
                                : $discountProduct['price'] - $request->discount_rate,
            ]);

            //only immediately write in IF today is within active period, else just put into discount_item
            //for task scheduler to handle and update by its own
            if (Carbon::now()->between($request->discount_from, $request->discount_to)) {
                Product::find($discountProduct['id'])->update([
                    'discount_id' => $newDiscount->id,
                ]);
            }
        }        
    }

    public function discountDetails() {
        $discount = ConfigDiscount::with('discountItems.product')
                                    ->whereHas('discountItems')
                                    ->orderBy('discount_from')
                                    ->get();

        $detailedDiscount = [];

        $discount->map(function ($discountDetails) use (&$detailedDiscount) {
            $discountItems = $discountDetails->discountItems->map(function ($discountItemDetails) use ($discountDetails) {
                return [
                    'product' => $discountItemDetails->product->product_name,
                    'before' => $discountItemDetails->price_before,
                    'discount' => $discountDetails->rate,
                    'after' => $discountItemDetails->price_after,
                    'start_on' => $discountDetails->discount_from->format('d/m/Y'),
                    'end_on' => $discountDetails->discount_to->format('d/m/Y'),
                    'type' => $discountDetails->type,
                    'product_id' => $discountItemDetails->product->id,
                    'original_data' => $discountItemDetails->product,
                    'image' => $discountItemDetails->product->getFirstMediaUrl('product'),
                ];
            })->toArray();
        
            $detailedDiscount[] = [ 
                'discount' => $discountDetails->name,
                'details' => $discountItems,
                'rate' => $discountDetails->rate,
                'type' => $discountDetails->type,
                'id' => $discountDetails->id,
                'start_on' => $discountDetails->discount_from->format('d/m/Y'),
                'end_on' => $discountDetails->discount_to->format('d/m/Y'),
            ];
        })->toArray();
        
        return response()->json($detailedDiscount);
    }

    public function deleteDiscount(String $id) {

        $deleteDiscount = ConfigDiscount::find($id);
        activity()->useLog('delete-discount')
                    ->performedOn($deleteDiscount)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'discount_name' => $deleteDiscount->name,
                    ])
                    ->log("$deleteDiscount->name is deleted.");
        $deleteDiscount->delete();
        ConfigDiscountItem::where('discount_id', $id)->delete();
        Product::where('discount_id', $id)->update([
            'discount_id' => null,
        ]);
    }

    public function editDiscount(DiscountRequest $request, String $id) {

        $discount = ConfigDiscount::findOrFail($id);
        
        //update main discount details
        $discount->update([
            'name' => $request->discount_name,
            'type' => $request->discount_type,
            'rate' => $request->discount_rate,
            'discount_from' => $request->discount_from,
            'discount_to' => $request->discount_to,
        ]);
    
        $existingProductIds = $discount->discountItems()->pluck('product_id')->toArray();
        $newProductIds = array_column($request->discount_product, 'id');
    
        //matched discount_id but not exist in updated product list = delete
        $deleteItem = ConfigDiscountItem::where('discount_id', $id)
                                        ->with('product:id,product_name')
                                        ->whereNotIn('product_id', $newProductIds)
                                        ->get();

        foreach ($deleteItem as $item) {
            activity()->useLog('delete-discount-item')
                        ->performedOn($item)
                        ->event('deleted')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'product_name' => $item->product->product_name,
                        ])
                        ->log(":properties.product_name is deleted.");
        }

        $deleteItem->delete();
    
        foreach ($request->discount_product as $product) {
            if (in_array($product['id'], $existingProductIds)) {
                //matched discount_id and matched product_id = update
                $editItem = ConfigDiscountItem::where('discount_id', $id)
                                                ->where('product_id', $product['id'])
                                                ->with('product:id,product_name')
                                                ->first();
                $editItem->update([
                    'price_before' => $product['price'],
                    'price_after' => $request->discount_type === 'percentage' 
                                    ? $product['price'] * (1 - $request->discount_rate / 100) 
                                    : $product['price'] - $request->discount_rate,
                ]);

                activity()->useLog('edit-discount-item-group')
                            ->performedOn($editItem)
                            ->event('updated')
                            ->withProperties([
                                'edited_by' => auth()->user()->full_name,
                                'image' => auth()->user()->getFirstMediaUrl('user'),
                                'category_name' => $editItem->product_name,
                            ])
                            ->log("$editItem->product_name's discount detail is updated.");   
            } else {
                //not matched discount_id but exists in updated product list = create
                ConfigDiscountItem::create([
                    'discount_id' => $id,
                    'product_id' => $product['id'],
                    'price_before' => $product['price'],
                    'price_after' => $request->discount_type === 'percentage' 
                                    ? $product['price'] * (1 - $request->discount_rate / 100) 
                                    : $product['price'] - $request->discount_rate,
                ]);
            }
        }
    }

    public function editProductDetails (String $id, Request $request)
    {
        $discountItems = Product::with(['discountItems.discount'])
                                ->whereHas('discountItems', function ($query) use ($id) {
                                    $query->where('discount_id', $id);
                                })
                                ->get();

        $discountItems->each(function ($product) {
            if ($product) {
                $product->image = $product->getFirstMediaUrl('product');
            }
        });

        return response()->json($discountItems);
    }

    private function getTiers() {
        $tiers = Ranking::select('id', 'name')->get();

        return $tiers;
    }

    private function getBillDiscounts() {
        $bill_discounts = BillDiscount::all();
    
        foreach ($bill_discounts as $bill_discount) {
            $tiers = $bill_discount->tier;
    
            if (is_array($tiers)) {
                foreach ($tiers as &$tier) {
                    $ranking = Ranking::find($tier); // Find the ranking using the tier ID
                    if ($ranking) {
                        $tier = $ranking->name;
                    }
                }
            }
    
            // Reassign the modified tiers back to the model
            $bill_discount->tier_names = $tiers;
        }
    
        return $bill_discounts;
    }
    

    public function getAllTiers(){
        $tiers = $this->getTiers();

        return response()->json($tiers);
    }

    public function getAllBillDiscounts(){
        $bill_discounts = $this->getBillDiscounts();

        return response()->json($bill_discounts);
    }

    public function addBillDiscount(Request $request) {
        $request->validate([
            'discount_name' => ['required', 'string'],
            'discount_isRate' => ['present'],
            'discount_rate' => ['required', 'numeric'],
            'discount_period' => ['required'],
            'available_on' => ['required', 'string'],
            'time_period_from' => ['nullable'],
            'time_period_to' => ['nullable'],
            'discount_criteria' => ['required', 'string'],
            'discount_requirement' => ['required', 'numeric'],
            'is_stackable' => ['present', 'boolean'],
            'conflict' => ['string'],
            'customer_usage' => ['nullable', 'numeric'],
            'customer_usage_renew' => ['nullable', 'string'],
            'total_usage' => ['nullable', 'numeric'],
            'total_usage_renew' => ['nullable', 'string'],
            'tier' => ['nullable', 'array'],
            'payment_method' => ['nullable', 'array'],
            'is_auto_applied' => ['present', 'boolean'],
        ], [
            'required' => 'This field is required.',
            'numeric' => 'Invalid input. Please try again.',
            'array' => 'Invalid input. Please try again.',
            'boolean' => 'Invalid input. Please try again.',
            'string' => 'Invalid input. Please try again.',
        ]);

        $today = Carbon::today('Asia/Kuala_Lumpur');

        $startDate = Carbon::parse($request['discount_period'][0])->tz('Asia/Kuala_Lumpur')->startOfDay(); // Start of the discount period
        $endDate = isset($request['discount_period'][1]) 
            ? Carbon::parse($request['discount_period'][1])->tz('Asia/Kuala_Lumpur')->startOfDay() 
            : $startDate;

        $availableOn = $request['available_on'];

        // Logic for the validity check
        $isValid = ($startDate->lessThanOrEqualTo($today)) && 
            ($endDate->greaterThanOrEqualTo($today)) &&
            (
                $availableOn === 'everyday' ||
                ($availableOn === 'weekday' && $today->isWeekday()) || // Check if today is a weekday
                ($availableOn === 'weekend' && $today->isWeekend())   // Check if today is a weekend
            );

        // dd($request['time_period_from']);
        BillDiscount::create([
            'name' => $request['discount_name'],
            'discount_type' => $request['discount_isRate'] === true ? 'percentage' : 'amount',
            'discount_rate' => $request['discount_rate'],
            'discount_from' => Carbon::parse($request['discount_period'][0])->tz('Asia/Kuala_Lumpur')->startOfDay()->format('Y-m-d H:i:s'),
            'discount_to' => $request['discount_period'][1] !== null
                                ? Carbon::parse($request['discount_period'][1])->tz('Asia/Kuala_Lumpur')->endOfDay()->format('Y-m-d H:i:s') 
                                :  Carbon::parse($request['discount_period'][0])->tz('Asia/Kuala_Lumpur')->endOfDay()->format('Y-m-d H:i:s'),
            'available_on' => $request['available_on'],
            'start_time' => $request['time_period_from'] !== '' ? $request['time_period_from'] : null,
            'end_time' => $request['time_period_to'] !== '' ? $request['time_period_to'] : null,
            'criteria' => $request['discount_criteria'],
            'requirement' => $request['discount_requirement'],
            'is_stackable' => $request['is_stackable'],
            'conflict' => $request['is_stackable'] === false ? $request['conflict'] : '',
            'customer_usage' => $request['customer_usage'],
            'customer_usage_renew' => $request['customer_usage_renew'],
            'total_usage' => $request['total_usage'],
            'total_usage_renew' => $request['total_usage_renew'],
            'tier' => $request['tier'],
            'payment_method' => $request['payment_method'],
            'is_auto_applied' => $request['is_auto_applied'],
            'status' => $isValid === true ? 'active' : 'inactive'
        ]);
    }

    public function editBillDiscount(Request $request, $id) {
        // Validate request inputs
        $request->validate([
            'discount_name' => ['required', 'string'],
            'discount_isRate' => ['present'],
            'discount_rate' => ['required', 'numeric'],
            'discount_period' => ['required', 'array', 'size:2'],
            'available_on' => ['required', 'string'],
            'time_period_from' => ['nullable', 'string'],
            'time_period_to' => ['nullable', 'string'],
            'discount_criteria' => ['required', 'string'],
            'discount_requirement' => ['required', 'numeric'],
            'is_stackable' => ['present', 'boolean'],
            'conflict' => ['nullable', 'string'],
            'customer_usage' => ['nullable', 'numeric'],
            'customer_usage_renew' => ['nullable', 'string'],
            'total_usage' => ['nullable', 'numeric'],
            'total_usage_renew' => ['nullable', 'string'],
            'tier' => ['nullable', 'array'],
            'payment_method' => ['nullable', 'array'],
            'is_auto_applied' => ['present', 'boolean'],
        ], [
            'required' => 'This field is required.',
            'numeric' => 'Invalid input. Please try again.',
            'array' => 'Invalid input. Please try again.',
            'boolean' => 'Invalid input. Please try again.',
            'string' => 'Invalid input. Please try again.',
        ]);
    
        $today = Carbon::today('Asia/Kuala_Lumpur');
    
        // Parse start and end dates
        $startDate = Carbon::parse($request['discount_period'][0])->tz('Asia/Kuala_Lumpur')->startOfDay();
        $endDate = isset($request['discount_period'][1]) 
            ? Carbon::parse($request['discount_period'][1])->tz('Asia/Kuala_Lumpur')->endOfDay() 
            : $startDate;
    
        // Determine validity based on available_on
        $availableOn = $request['available_on'];
        $isValid = ($startDate->lessThanOrEqualTo($today)) && 
                   ($endDate->greaterThanOrEqualTo($today)) &&
                   (
                       $availableOn === 'everyday' ||
                       ($availableOn === 'weekday' && $today->isWeekday()) ||
                       ($availableOn === 'weekend' && $today->isWeekend())
                   );
    
        // Retrieve the specific BillDiscount record
        $billDiscount = BillDiscount::findOrFail($id);
    
        // Update the record
        $billDiscount->update([
            'name' => $request['discount_name'],
            'discount_type' => $request['discount_isRate'] === true ? 'percentage' : 'amount',
            'discount_rate' => $request['discount_rate'],
            'discount_from' => $startDate->format('Y-m-d H:i:s'),
            'discount_to' => $endDate->format('Y-m-d H:i:s'),
            'available_on' => $availableOn,
            'start_time' => $request['time_period_from'] ?: null,
            'end_time' => $request['time_period_to'] ?: null,
            'criteria' => $request['discount_criteria'],
            'requirement' => $request['discount_requirement'],
            'is_stackable' => $request['is_stackable'],
            'conflict' => $request['is_stackable'] === false ? $request['conflict'] : null,
            'customer_usage' => $request['customer_usage'],
            'customer_usage_renew' => $request['customer_usage_renew'],
            'total_usage' => $request['total_usage'],
            'total_usage_renew' => $request['total_usage_renew'],
            'tier' => $request['tier'],
            'payment_method' => $request['payment_method'],
            'is_auto_applied' => $request['is_auto_applied'],
            'status' => $isValid ? 'active' : 'inactive',
        ]);
    
        return redirect()->back();
    }
    
    public function deleteBillDiscount(String $id){
        $targetDiscount = BillDiscount::findOrFail($id);
        $targetDiscount->delete();

        return redirect()->back();
    }

    public function updateBillStatus(Request $request, string $id){
        $targetDiscount = BillDiscount::findOrFail($id);

        $targetDiscount->update([
            'status' => $request->status,
        ]);
        
        $targetDiscount->save();

        return redirect()->back();
    }
}
