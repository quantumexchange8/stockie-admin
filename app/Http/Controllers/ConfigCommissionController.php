<?php

namespace App\Http\Controllers;

use App\Models\ConfigEmployeeComm;
use App\Models\ConfigEmployeeCommItem;
use App\Models\IventoryItem;
use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfigCommissionController extends Controller
{
    public function index (Request $request)
    {
        $message = $request->session()->get('message');

        $commission = ConfigEmployeeComm::with('configCommItems.product')->get();        
   
        $commission = $commission->map(function($involved) {
            $products = [];
            $productIds = [];
            $productImage = [];

            foreach ($involved->configCommItems as $configItem) {
                $products[] = $configItem->product->product_name;
                $productIds[] = $configItem->Product->id;
                $productImage[] = $configItem->product->getFirstMediaUrl('product');
            }

            return [
                'id' => $involved->id,
                'comm_type' => $involved->comm_type,
                'rate' => $involved->rate,
                'product' => $products,
                'productIds' => $productIds,
                'image' => $productImage,
            ];
        });

        $productNames = Product::select('id', 'product_name')->get();

        $products = Product::whereDoesntHave('commItem')->pluck('id');
        $productsToAdd = Product::whereIn('id', $products)->get(['id', 'product_name']);
        $productsToAdd->each(function ($product){
            $product->image = $product->getFirstMediaUrl('product');
        });

        return response()->json([
            'commission' => $commission,
            'productNames' => $productNames,
            'message' => $message ?? [],
            'productToAdd' => $productsToAdd,
        ]);
    }

    public function addCommission(Request $request)
    {
        $validatedData = $request->validate([
            'commType' => ['required', 'string'],
            'commRate' => ['required', 'max:255'],
            'involvedProducts' => ['required']
        ]);

        $newData = ConfigEmployeeComm::create([
            'comm_type' => $validatedData['commType'],
            'rate' => $validatedData['commRate'],
        ]);
    
        $newDataId = $newData->id;
        foreach ($validatedData['involvedProducts'] as $productId) {
            // $productItems = Product::find($productId)?->productItems;
        
            // if ($productItems) {
            //     foreach ($productItems as $productItem) {
            //         ConfigEmployeeCommItem::create([
            //             'comm_id' => $newDataId,
            //             'item' => $productItem->id,
            //         ]);
            //     }
            // }

            $product = Product::find($productId);
        
            if ($product) {
                $newComm = ConfigEmployeeCommItem::create([
                    'comm_id' => $newDataId,
                    'item' => $product->id,
                ]);

                activity()->useLog('create-commission-type')
                            ->performedOn($newComm)
                            ->event('added')
                            ->withProperties([
                                'created_by' => auth()->user()->full_name,
                                'image' => auth()->user()->getFirstMediaUrl('user'),
                                'product_name' => $product->product_name,
                            ])
                            ->log("Commission for '$product->product_name' is added.");
            }
        }
        

        // $message = [
        //     'severity' => 'success',
        //     'summary' => 'New commission type has been successfully added.'
        // ];

        return redirect()->route('configurations');
    }

    public function deleteCommission (String $id)
    {
        if($id)
        {
            $deleteComm = ConfigEmployeeComm::where('id', $id)->first();
            $deleteCommItem = ConfigEmployeeCommItem::where('comm_id', $id)->get();
            $productNames = ConfigEmployeeCommItem::where('comm_id', $id)
                                                    ->with('product')
                                                    ->get()
                                                    ->pluck('product')
                                                    ->pluck('product_name')
                                                    ->implode(', ');
            activity()->useLog('delete-commission-type')
                        ->performedOn($deleteComm)
                        ->event('deleted')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'waiter_name' => $deleteComm->full_name,
                        ])
                        ->log("Commission type for $productNames are deleted.");
            $deleteComm->delete();

            $deleteCommItem->delete();
        }

        return redirect()->route('configurations')->with(['selectedTab' => 1]);
    }

    public function editCommission (Request $request)
    {
        if ($request->id) {
            //delete first, then re-create
            ConfigEmployeeCommItem::where('comm_id', $request->id)->delete();
            $editComm = ConfigEmployeeComm::where('id', $request->id)->first();
            $editComm->update([
                'comm_type' => $request->commType,
                'rate' => $request->commRate,
            ]);
            foreach ($request->involvedProducts as $products) {
                ConfigEmployeeCommItem::create([
                    'comm_id' => $request->id,
                    'item' => $products,
                ]);

                activity()->useLog('edit-commission-type')
                            ->performedOn($editComm)
                            ->event('updated')
                            ->withProperties([
                                'edited_by' => auth()->user()->full_name,
                                'image' => auth()->user()->getFirstMediaUrl('user'),
                                'product_name' => Product::where('id', $products)->first()->pluck('product_name'),
                            ])
                            ->log("Commission type for ':properties.product_name' is updated.");
            }

        }
        // else{
        //     $message = [
        //         'severity' => 'danger',
        //         'summary' => 'Error occurred. Please contact administrator.'
        //     ];
        // }

        return redirect()->back();
    }

    public function productDetails(Request $request, String $id)
    {
        $message = $request->session()->get('message');

        if ($id) {
            $comm = ConfigEmployeeComm::find($id);

            $commItems = ConfigEmployeeCommItem::where('comm_id', $id)
                                                ->select('comm_id', 'item')
                                                ->get();
            $commItemsArray = $commItems->pluck('item')->toArray();

            $otherCommItems = Product::pluck('id');
            $filteredOtherCommItems = $otherCommItems->filter(function ($otherCommItem) use ($commItemsArray) {
                return !in_array($otherCommItem, $commItemsArray);
            });
            $commItemsCount = $commItems->count();

            //products that already included under this commission
            $productDetails = [];
            foreach ($commItems as $commItem) {
                $product = Product::find($commItem->item);

                $commission = $comm->comm_type === 'Fixed amount per sold product'
                        ? $comm->rate
                        : $product->price * ($comm->rate/100);

                $productDetails[] = [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'price' => $product->price,
                    'bucket' => $product->bucket,
                    'commission' => $commission,
                    'rate' => $comm->rate,
                    'image' => $product->getFirstMediaUrl('product')
                ];
            }

            //products that are yet to be added 
            $otherProductDetails = [];
            foreach ($filteredOtherCommItems as $commItem) {
                $product = Product::find($commItem, ['product_name', 'price', 'id', 'bucket']); 

                $commission = ($comm->comm_type == 'Fixed amount per sold product') 
                    ? $comm->rate 
                    : floor($product->price * ($comm->rate / 100));
        
                $otherProductDetails[] = [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'price' => $product->price,
                    'bucket' => $product->bucket,
                    'commission' => $commission,
                ];
            }

            $commDetails = [
                'rate' => $comm->rate,
                'type' => $comm->comm_type,
                'commItemsCount' => $commItemsCount,
                'productDetails' => $productDetails,
                'otherProductDetails' => $otherProductDetails,
            ];
        } else {
            $message = [
                'severity' => 'danger',
                'summary' => 'Error occurred. Please contact administrator.'
            ];
        }

        $products = Product::whereDoesntHave('commItem')->pluck('id');
        $productsToAdd = Product::whereIn('id', $products)->get(['id', 'product_name']);
        $productsToAdd->each(function($productToAdd){
            $productToAdd->image = $productToAdd->getFirstMediaUrl('product');
        });

        return Inertia::render('Configuration/EmployeeCommission/Partials/ProductDetails', [
            'message' => $message ?? [],
            'productDetails' => $commDetails,
            'commissionDetails' => $comm,
            'productsToAdd' => $productsToAdd,
        ]);
    }

    public function deleteProduct (Request $request)
    {
        $deletingProduct = ConfigEmployeeCommItem::where('comm_id', $request->commissionId)
                                                    ->where('item', $request->id)
                                                    ->first();
        if ($deletingProduct) {
            $deletingProduct->delete();

            $message = [
                'severity' => 'success',
                'summary' => 'Product has been removed successfully.'
            ];
        } else {
            $message = [
                'severity' => 'error',
                'summary' => 'Error occurred. Please contact administrator.'
            ];
        }

        return redirect()->back()->with(['message' => $message]);
    }

    public function addProducts (Request $request)
    {
        foreach($request->input('addingProduct') as $addingId)
        ConfigEmployeeCommItem::create([
            'comm_id' => $request->input('comm_id'),
            'item' => $addingId,
        ]);

        $message = [
            'severity' => 'success',
            'summary' => 'Product has been added into commission.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    public function updateCommission (Request $request)
    {
        // dd($request->rate);
        ConfigEmployeeComm::where('id', $request->id)
                            ->update([
                                'comm_type' => $request->commType,
                                'rate' => $request->rate
                            ]);

        // $message = [
        //     'severity' => 'success',
        //     'summary' => 'Commission has been edited.'
        // ];

        // return redirect()->back()->with(['message' => $message]);
    }
}
