<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductItemRequest;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

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
        // Get validated product data
        $validatedData = $request->validated();

        // Get product items
        $productItemData = $request->input('items');
        
        $productItemRequest = new ProductItemRequest();
        $validatedProductItems = [];
        $allItemErrors = [];

        foreach ($productItemData as $index => $item) {
            $rules = $productItemRequest->rules();
            $requestMessages = $productItemRequest->messages();

            $rules['qty'] = $validatedData['bucket']
                                    ? 'required|integer|min:2'
                                    : 'required|integer|min:1';

            $requestMessages['qty.min'] = $validatedData['bucket']
                                    ? 'A minimum of 2 stocks available are required.'
                                    : 'A minimum of 1 stock available is required.';

            // Validate product items data
            $productItemValidator = Validator::make(
                $item,
                $rules,
                $requestMessages,
                $productItemRequest->attributes()
            );
            
            if ($productItemValidator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($productItemValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $productItemValidator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedProductItems[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) {
            return redirect()->back()->withErrors($allItemErrors)->withInput();
        }

        // dd($validatedProductItems);
        
        $newProduct = Product::create([
            'product_name' => $validatedData['product_name'],
            'bucket' => $validatedData['bucket'] ? 'set' : 'single',
            'price' => $validatedData['price'],
            'point' => $validatedData['point'],
            'category_id' => $validatedData['category_id'],
            'keep' => $validatedData['keep'],
        ]);

        if (count($validatedProductItems) > 0) {
            foreach ($validatedProductItems as $key => $value) {
                ProductItem::create([
                    'product_id' => $newProduct->id,
                    'inventory_item_id' => $value['inventory_item_id'],
                    'qty' => (string) $value['qty'],
                ]);
            }
        }
        
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
     * Get all categories.
     */
    public function getAllCategories()
    {
        $data = Category::select(['id', 'name'])
                        ->orderBy('id')
                        ->get()
                        ->map(function ($category) {
                            return [
                                'text' => $category->name,
                                'value' => $category->id
                            ];
                        });

        return response()->json($data);
    }

    /**
     * Get products and its items.
     */
    public function getProducts(Request $request)
    {
        $queries = Product::query();

        $allCategories = Category::select(['id'])
                                ->orderBy('id')
                                ->get();

        $selectedCategory = (int) $request['selectedCategory'];

        // Join with product item table
        // $queries->join('product_items', 'product_items.product_id', '=', 'products.id');

        // Check if there are any filters selected
        if (isset($request['checkedFilters'])) {
            $queries->where(function (Builder $query) use ($request) {
                // // Check if there are any item category filter option selected
                if (isset($request['checkedFilters']['keepStatus']) && count($request['checkedFilters']['keepStatus']) > 0) {
                    $query->whereIn('keep', $request['checkedFilters']['keepStatus']);
                }
    
                // Check if there are any stock level filter option selected
                if (isset($request['checkedFilters']['stockLevel']) && count($request['checkedFilters']['stockLevel']) > 0) {
                    $stockLevelsQuery = ProductItem::select('product_id')
                                                    ->join('iventory_items', 'product_items.inventory_item_id', '=', 'iventory_items.id')
                                                    ->join('item_categories', 'iventory_items.item_cat_id', '=', 'item_categories.id')
                                                    ->groupBy('product_id');

                    foreach ($request['checkedFilters']['stockLevel'] as $value) {
                        switch ($value) {
                            case 'In Stock':
                                $stockLevelsQuery->orHavingRaw('MIN(iventory_items.stock_qty) > MIN(item_categories.low_stock_qty)');
                                break;
                            case 'Low Stock':
                                $stockLevelsQuery->orHavingRaw('MIN(iventory_items.stock_qty) <= MIN(item_categories.low_stock_qty) AND MIN(iventory_items.stock_qty) >= 1');
                                break;
                            case 'Out of Stock':
                                $stockLevelsQuery->orHavingRaw('MIN(iventory_items.stock_qty) = 0');
                                break;
                        }
                    }

                    $productIdsWithStockLevel = $stockLevelsQuery->pluck('product_id');
                    $query->whereIn('id', $productIdsWithStockLevel);
                }

                if (isset($request['checkedFilters']['priceRange']) && count($request['checkedFilters']['priceRange']) > 0) {
                    $query->whereBetween('price', array_map(fn($value) => (int)$value, $request['checkedFilters']['priceRange']));
                }
            });
        }

        if (isset($selectedCategory)) {
            if ($selectedCategory === 0) {
                $queries->whereIn('category_id', $allCategories);
            } else {
                $queries->where('category_id', $selectedCategory);
            }
        }

        $queries->with([
            'productItems:id,product_id,inventory_item_id,qty', 
            'category:id,name', 
            'productItems.inventoryItem:id,stock_qty,item_cat_id',
            'productItems.inventoryItem.itemCategory:id,low_stock_qty',
            'saleHistories'
        ]);
        $data = $queries->orderBy('id')
                        ->get()
                        ->map(function ($product) {
                            $product_items = $product->productItems;
                            $minStockCount = 0;

                            if (count($product_items) > 0) {
                                $stockCountArr = [];
                                $lowInStockArr = [];

                                foreach ($product_items as $key => $value) {
                                    $inventory_item = IventoryItem::select(['stock_qty', 'item_cat_id'])
                                                                        ->with('itemCategory:id,low_stock_qty')
                                                                        ->find($value['inventory_item_id']);

                                    $stockQty = $inventory_item->stock_qty;
                                    
                                    $stockCount = (int)round($stockQty / (int)$value['qty']);
                                    array_push($stockCountArr, $stockCount);

                                    $lowStockQtyLimit = $inventory_item->itemCategory->low_stock_qty;
                                    if ((int)$stockQty <= $lowStockQtyLimit && (int)$stockQty > 1) {
                                        array_push($lowInStockArr, 'Limited');
                                    }
                                    
                                    if ((int)$stockQty > $lowStockQtyLimit) {
                                        array_push($lowInStockArr, 'Available');
                                    } 
                                    
                                    if ((int)$stockQty === 0) {
                                        array_push($lowInStockArr, 'Unavailable');
                                    }
                                }
                                $minStockCount = min($stockCountArr);
                            }
                            $product['stock_left'] = $minStockCount; 

                            if (in_array('Unavailable', $lowInStockArr)) {
                                $product['stock_status'] = 'Out of stock';
                            } elseif (in_array('Limited', $lowInStockArr)) {
                                $product['stock_status'] = 'Low in stock';
                            } else {
                                $product['stock_status'] = 'In stock';
                            }

                            return $product;
                        });

        return response()->json($data);
    }

    /**
     * Get all inventories along with its items.
     */
    public function getAllInventories()
    {
        $data = Iventory::withWhereHas('inventoryItems')
                        ->select(['id', 'name'])
                        ->orderBy('id')
                        ->get()
                        ->map(function ($group) {
                            $group_items = $group->inventoryItems->map(function ($item) {
                                return [
                                    'text' => $item->item_name,
                                    'value' => $item->id,
                                ];
                            });

                            return [
                                'group_name' => $group->name,
                                'items' => $group_items
                            ];
                        });
        
        return response()->json($data);
    }

    /**
     * Get product item's inventory item's stock quantity.
     */
    public function getInventoryItemStock(string $id)
    {
        $data = IventoryItem::select('stock_qty')
                                ->find($id);
        
        return response()->json($data);
    }
    
    /**
     * Testing get table records.
     */
    public function updateProduct(ProductRequest $request, string $id)
    {
        // Get validated product data
        $validatedData = $request->validated();

        // Get product items
        $productItemData = $request->input('items');
        
        $productItemRequest = new ProductItemRequest();
        $validatedProductItems = [];
        $allItemErrors = [];

        foreach ($productItemData as $index => $item) {
            $rules = $productItemRequest->rules();
            $requestMessages = $productItemRequest->messages();
            $inventoryItemStock = IventoryItem::select('stock_qty')
                                                ->find($item['inventory_item_id']);

            $rules['qty'] = $validatedData['bucket']
                                    ? 'required|integer|min:2'
                                    : 'required|integer|min:1';

            $requestMessages['qty.min'] = $validatedData['bucket']
                                    ? 'A minimum of 2 stocks available are required.'
                                    : 'A minimum of 1 stock available is required.';

            // Validate product items data
            $productItemValidator = Validator::make(
                $item,
                $rules,
                $requestMessages,
                $productItemRequest->attributes()
            );

            if (($inventoryItemStock->stock_qty - $item['qty']) < 0) {
                $allItemErrors["items.$index.qty"] = $validatedData['bucket']
                                    ? 'A minimum of 2 stocks available are required.'
                                    : 'A minimum of 1 stock available is required.';
            }
            
            if ($productItemValidator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($productItemValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $productItemValidator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedProductItems[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) {
            return redirect()->back()->withErrors($allItemErrors)->withInput();
        }
        
        if (isset($id)) {
            $existingProduct = Product::find($id);

            $existingProduct->update([
                'product_name' => $validatedData['product_name'],
                'bucket' => $validatedData['bucket'] ? 'set' : 'single',
                'price' => $validatedData['price'],
                'point' => $validatedData['point'],
                'category_id' => $validatedData['category_id'],
                'keep' => $validatedData['keep'],
            ]);
        }

        if (count($validatedProductItems) > 0) {
            foreach ($validatedProductItems as $key => $value) {
                if (isset($value['id'])) {
                    $existingProductItem = ProductItem::find($value['id']);

                    $existingProductItem->update([
                        'inventory_item_id' => $value['inventory_item_id'],
                        'qty' => (string) $value['qty'],
                    ]);
                }
            }
        }
        
        return Redirect::route('products.index');
    }
     
    /**
     * Delete product along with its related models.
     */
    public function deleteProduct(Request $request, string $id)
    {
        $existingProduct = Product::with('productItems')
                                    ->find($id);

        $existingProductItems = $existingProduct->productItems ?? [];
        
        if (count($existingProductItems) > 0) {
            foreach ($existingProductItems as $key => $value) {
                if (isset($value['id'])) {
                    $existingItem = ProductItem::find($value['id']);
    
                    $existingItem->delete();
                }
            }
        }

        $existingProduct->delete();
    }
     
    /**
     * Delete product item.
     */
    public function deleteProductItem(Request $request, string $id)
    {
        $existingProductItem = ProductItem::find($id);

        $existingProductItem->delete();
    }

    /**
     * Get product sale histories.
     */
    public function getProductSaleHistories(Request $request)
    {
        $dateFilter = $request->input('dateFilter');

        $query = Product::query();

        if ($dateFilter && gettype($dateFilter) === 'array') {
            // Single date filter
            if (count($dateFilter) === 1) {
                $date = (new \DateTime($dateFilter[0]))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
                $query->whereDate('created_at', $date);
            }
            // Range date filter
            if (count($dateFilter) > 1) {
                $startDate = (new \DateTime($dateFilter[0]))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
                $endDate = (new \DateTime($dateFilter[1]))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
                $query->whereDate('created_at', '>=', $startDate)
                        ->whereDate('created_at', '<=', $endDate);
            }
        }

        $product = $query->with('saleHistories')
                            ->orderBy('created_at', 'desc')
                            ->find($request['id']);

                            
        $data = $product->saleHistories ?? [];
        
        return response()->json($data);
    }

    /**
     * Get product details and its items.
     */
    public function getProductWithItems(string $id)
    {
        $product = Product::with([
                                'category:id,name',
                                'productItems',
                                'productItems.inventoryItem:id,item_name,stock_qty'
                            ])
                            ->orderBy('created_at', 'desc')
                            ->find($id);

        $productItems = $product->productItems;
        
        $data = [
            'product' => $product,
            'productItems' => $productItems,
        ];
        
        return response()->json($data);
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
