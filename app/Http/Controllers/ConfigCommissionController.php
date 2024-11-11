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

        $commission = ConfigEmployeeComm::with('configCommItems.productItems.product')->get();        
   
        $commission = $commission->map(function($involved) {
            $products = [];
            $productIds = [];
            $productImage = [];
            foreach ($involved->configCommItems as $configItem) {
                foreach ($configItem->productItems as $productItem) {
                    $products[] = $productItem->product->product_name;
                    $productIds[] = $productItem->product->id;
                    $productImage[] = $productItem->product->getFirstMediaUrl('product');
                }
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

        $items = ProductItem::whereDoesntHave('commItems')->pluck('product_id');
        $productsToAdd = Product::whereIn('id', $items)->get(['id', 'product_name']);
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
            'commRate' => ['required', 'integer', 'max:255'],
            'involvedProducts' => ['required']
        ]);
    
        $newData = ConfigEmployeeComm::create([
            'comm_type' => $validatedData['commType'],
            'rate' => $validatedData['commRate'],
        ]);
    
        $newDataId = $newData->id;
    
        foreach ($validatedData['involvedProducts'] as $products) {
            ConfigEmployeeCommItem::create([
                'comm_id' => $newDataId,
                'item' => $products,
            ]);
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
            ConfigEmployeeComm::where('id', $id)->delete();
            ConfigEmployeeCommItem::where('comm_id', $id)->delete();
            $message = [
                'severity' => 'success',
                'summary' => 'Commission type has been deleted.'
            ];

        }
        else
        {
            $message = [
                'severity' => 'danger',
                'summary' => 'No commission type found. Please contact administrator.'
            ];
        }

        return redirect()->route('configurations')->with(['message' => $message]);
    }

    public function editCommission (Request $request)
    {
        // dd($request->all());
        if($request->id)
        {
            //delete first, then re-create
            ConfigEmployeeCommItem::where('comm_id', $request->id)->delete();
            ConfigEmployeeComm::where('id', $request->id)->update([
                'comm_type' => $request->commType,
                'rate' => $request->commRate,
            ]);
            foreach ($request->involvedProducts as $products) {
                ConfigEmployeeCommItem::create([
                    'comm_id' => $request->id,
                    'item' => $products,
                ]);
            }
        }
        // else{
        //     $message = [
        //         'severity' => 'danger',
        //         'summary' => 'Error occurred. Please contact administrator.'
        //     ];
        // }

        // return redirect()->back()->with(['message' => $message]);
    }

    public function productDetails(Request $request, String $id)
    {
        $message = $request->session()->get('message');

        if($id)
        {
            $comm = ConfigEmployeeComm::where('id', $id)
                                        ->select('comm_type', 'rate', 'id')
                                        ->first();

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
                $products = Product::where('id', $commItem->item)
                    ->select('product_name', 'price', 'id', 'bucket')
                    ->get(); 
            
                foreach ($products as $product) {
                    if ($comm->comm_type == 'Fixed amount per sold product') {
                        $commission = $comm->rate; 
                    } else {
                        $commission = $product->price * ($comm->rate / 100); 
                    }
            
                    $productDetails[] = [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'price' => $product->price,
                        'bucket' => $product->bucket,
                        'commission' => $commission,
                        'rate' => $comm->rate,
                    ];
                }
            }

            //products that are yet to be added 
            $otherProductDetails = [];
            foreach ($filteredOtherCommItems as $commItem) {
                $products = Product::where('id', $commItem)
                    ->select('product_name', 'price', 'id', 'bucket')
                    ->get(); 
                foreach ($products as $product) {
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
            }

            $commDetails = [
                'rate' => $comm->rate,
                'type' => $comm->comm_type,
                'commItemsCount' => $commItemsCount,
                'productDetails' => $productDetails,
                'otherProductDetails' => $otherProductDetails,
            ];
        }
        else{
            $message = [
                'severity' => 'danger',
                'summary' => 'Error occurred. Please contact administrator.'
            ];
        }

        $productItems = ProductItem::whereDoesntHave('commItems')->pluck('product_id');
        $productsToAdd = Product::whereIn('id', $productItems)->get(['id', 'product_name']);

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
        if($deletingProduct)
        {
            $deletingProduct->delete();

            $message = [
                'severity' => 'success',
                'summary' => 'Product has been removed successfully.'
            ];
        }
        else
        {
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
