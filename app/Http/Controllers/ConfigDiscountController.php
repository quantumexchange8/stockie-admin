<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountRequest;
use App\Models\Category;
use App\Models\ConfigDiscount;
use App\Models\ConfigDiscountItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;

class ConfigDiscountController extends Controller
{
    public function getDiscount() {
        $categories = Category::select(['id', 'name'])
                                ->orderBy('id')
                                ->get()
                                ->map(function ($category) {
                                    return [
                                        'text' => $category->name,
                                        'value' => $category->id
                                    ];
                                });

        $productsAvailable = Product::with('discountItems.discount')
                                    ->whereDoesntHave('discountItems.discount')
                                    ->orWhereHas('discountItems.discount', function ($query) {
                                        $query->where('discount_from', '>', Carbon::today())
                                                ->orWhere('discount_to', '<', Carbon::today());
                                        })
                                    ->get();

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

    public function dateFilter(Request $request)
    {
        $dateFilter = [];
        $selectedProducts = $request->input('selectedProducts', []);

        if (!is_array($selectedProducts) || count($selectedProducts) === 0) {
            return response()->json($dateFilter); 
        }

        foreach ($selectedProducts as $selectedProduct) {
            if (isset($selectedProduct['discount_id']) && $selectedProduct['discount_id']) {
                $targetDiscount = ConfigDiscount::find($selectedProduct['discount_id']);
                        
                if ($targetDiscount) {
                    $startDate = Carbon::parse($targetDiscount->discount_from);
                    $endDate = Carbon::parse($targetDiscount->discount_to);

                    $dateRange = [];
                    for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                        $dateRange[] = $date->copy(); 
                    }

                    $dateFilter = array_merge($dateFilter, $dateRange);
                }
            }
        }

        return response()->json($dateFilter);
    }

    public function discountDetails() {
        $discount = ConfigDiscount::with('discountItems.product')
                                    ->whereHas('discountItems')
                                    ->orderByDesc('discount_to')
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

        ConfigDiscount::find($id)->delete();
        ConfigDiscountItem::where('discount_id', $id)->delete();
        Product::where('discount_id', $id)->update([
            'discount_id' => null,
        ]);
    }

    public function editDiscount(DiscountRequest $request) {
        $discount = ConfigDiscount::findOrFail($request->discount_id);
        
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
        ConfigDiscountItem::where('discount_id', $request->discount_id)
            ->whereNotIn('product_id', $newProductIds)
            ->delete();
    
        foreach ($request->discount_product as $product) {
            if (in_array($product['id'], $existingProductIds)) {
                //matched discount_id and matched product_id = update
                ConfigDiscountItem::where('discount_id', $request->discount_id)
                    ->where('product_id', $product['id'])
                    ->update([
                        'price_before' => $product['price'],
                        'price_after' => $request->discount_type === 'percentage' 
                                        ? $product['price'] * (1 - $request->discount_rate / 100) 
                                        : $product['price'] - $request->discount_rate,
                    ]);
            } else {
                //not matched discount_id but exists in updated product list = create
                ConfigDiscountItem::create([
                    'discount_id' => $request->discount_id,
                    'product_id' => $product['id'],
                    'price_before' => $product['price'],
                    'price_after' => $request->discount_type === 'percentage' 
                                    ? $product['price'] * (1 - $request->discount_rate / 100) 
                                    : $product['price'] - $request->discount_rate,
                ]);
            }
        }
    }
    
    

    public function editProductDetails (String $id){

        $discountItem = ConfigDiscountItem::where('discount_id', $id)
                                            ->with('product')
                                            ->get()
                                            ->pluck('product');

        return response()->json($discountItem);
    }
}
