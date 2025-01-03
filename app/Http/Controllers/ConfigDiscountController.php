<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountRequest;
use App\Models\Category;
use App\Models\ConfigDiscount;
use App\Models\ConfigDiscountItem;
use App\Models\Product;
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

        ConfigDiscount::find($id)->delete();
        ConfigDiscountItem::where('discount_id', $id)->delete();
        Product::where('discount_id', $id)->update([
            'discount_id' => null,
        ]);
    }

    public function editDiscount(DiscountRequest $request, String $id) {

        // dd($request);
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
        ConfigDiscountItem::where('discount_id', $id)
            ->whereNotIn('product_id', $newProductIds)
            ->delete();
    
        foreach ($request->discount_product as $product) {
            if (in_array($product['id'], $existingProductIds)) {
                //matched discount_id and matched product_id = update
                ConfigDiscountItem::where('discount_id', $id)
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
}
