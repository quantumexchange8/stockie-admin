<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Product/Product');
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
    public function store(ProductRequest $request)
    {
        $data = $request->all();

        $newProduct = Product::create([
            'product_name' => $data['product_name'],
            'price' => $data['price'],
            'point' => $data['point'],
            'category_id' => 1,
            'keep' => $data['keep'],
        ]);

        $newProduct->save();

        ProductItem::create([
            'product_id' => $newProduct->id,
            'item' => $data['item'],
            'qty' => $data['qty'],
        ]);
        
        return Redirect::route('products.index');
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
    public function edit(string $id)
    {
        //
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

    /**
     * Show product details.
     */
    public function showProductDetails(string $id)
    {
        return Inertia::render('Product/Partials/ProductDetail', ['id' => $id]);
    }

    /**
     * Testing get table records.
     */
    public function getTestingRecords()
    {

        $data = Product::with('productItems:id,product_id,qty')
                        ->orderBy('id')
                        ->get()
                        ->map(function ($product) {
                            $totalQty = 0;

                            foreach ($product->productItems as $key => $value) {
                                $totalQty += $value->qty;
                            }

                            return [
                                'id' => $product->id,
                                'product_name' => $product->product_name,
                                'price' => $product->price,
                                'point' => $product->point,
                                'keep' => $product->keep,
                                'qty' => $totalQty,
                                'stock_qty' => $totalQty,
                            ];
                        });

        // dd($data);

        return response()->json($data);
    }
}
