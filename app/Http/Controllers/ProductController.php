<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductItemRequest;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\SaleHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::with([
                                'productItems:id,product_id,inventory_item_id,qty', 
                                'category:id,name', 
                                'productItems.inventoryItem:id,stock_qty,item_cat_id,status',
                                'productItems.inventoryItem.itemCategory:id,low_stock_qty',
                                'saleHistories'
                            ])
                            ->orderBy('product_name')
                            ->get()
                            ->map(function ($product) {
                                $product_items = $product->productItems;
                                $minStockCount = 0;

                                if (count($product_items) > 0) {
                                    $stockCountArr = [];

                                    foreach ($product_items as $key => $value) {
                                        $inventory_item = IventoryItem::select(['stock_qty', 'item_cat_id'])
                                                                            ->with('itemCategory:id,low_stock_qty')
                                                                            ->find($value['inventory_item_id']);

                                        $stockQty = $inventory_item->stock_qty;
                                        $stockCount = (int)round($stockQty / (int)$value['qty']);

                                        array_push($stockCountArr, $stockCount);
                                    }
                                    $minStockCount = min($stockCountArr);
                                }
                                $product['stock_left'] = $minStockCount;

                                return $product;
                            });

        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        return Inertia::render('Product/Product', [
            'message' => $message ?? [],
            'products' => $products,
            'inventories' => $this->getAllInventories(),
            'categories' => $this->getAllCategories(),
        ]);
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
        
        $newProduct = Product::create([
            'product_name' => $validatedData['product_name'],
            'bucket' => $validatedData['bucket'] ? 'set' : 'single',
            'price' => $validatedData['price'],
            'point' => $validatedData['point'],
            'category_id' => $validatedData['category_id'],
            'keep' => $validatedData['keep'],
            'status' => $this->getProductStatus($validatedProductItems),
            'availability' => 'Available',
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

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Product has been successfully added to your menu.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    private function getProductStatus(array $product_items)
    {
        // Get inventory items with required stock quantities
        $inventoryItems = IventoryItem::whereIn('id', array_column($product_items, 'inventory_item_id'))
            ->get(['id', 'stock_qty'])
            ->keyBy('id');
    
        // Calculate stock status for each item
        $stockStatuses = collect($product_items)->map(function ($item) use ($inventoryItems) {
            $inventoryItem = $inventoryItems->get($item['inventory_item_id']);
    
            if ($inventoryItem) {
                // Determine stock status
                if ($inventoryItem->stock_qty == 0) {
                    return 'Out of Stock';
                } elseif ($inventoryItem->stock_qty < $item['qty']) {
                    return 'Low Stock';
                } else {
                    return 'In Stock';
                }
            }
    
            return null; // Handle case where inventory item is not found (optional)
        });
    
        // Determine overall product status
        if ($stockStatuses->contains('Out of Stock')) {
            return 'Out of Stock';
        } elseif ($stockStatuses->contains('Low Stock')) {
            return 'Low Stock';
        }
    
        return 'In Stock';
    }

    /**
     * Show product details.
     */
    public function showProductDetails(Request $request, string $id)
    {
        $dateFilter = [
            now()->subDays(30)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
        ];

        $product = Product::with([
                                'category:id,name',
                                'productItems',
                                'productItems.inventoryItem:id,item_name,stock_qty',
                                'saleHistories'
                            ])
                            ->orderBy('created_at', 'desc')
                            ->find($id);
        
        $saleHistories = $product->saleHistories()->whereDate('created_at', '>=', $dateFilter[0])
                                                ->whereDate('created_at', '<=', $dateFilter[1])
                                                ->orderBy('created_at', 'desc')
                                                ->get();

        
        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        return Inertia::render('Product/Partials/ProductDetail', [
            'message' => $message ?? [],
            'product' => $product,
            'saleHistories' => $saleHistories,
            'defaultDateFilter' => $dateFilter,
            'inventories' => $this->getAllInventories(),
            'categories' => $this->getAllCategories(),
        ]);
    }

    /**
     * Get all categories.
     */
    public function getAllCategories()
    {
        return Category::select(['id', 'name'])
                        ->orderBy('id')
                        ->get()
                        ->map(function ($category) {
                            return [
                                'text' => $category->name,
                                'value' => $category->id
                            ];
                        });
    }

    /**
     * Get products and its items.
     */
    public function getProducts(Request $request)
    {
        $queries = Product::query();

        $allCategories = Category::select(['id'])->orderBy('id')->get();

        $selectedCategory = (int) $request['selectedCategory'];

        // Check if there are any filters selected
        if (isset($request['checkedFilters'])) {
            $queries->where(function (Builder $query) use ($request) {
                // // Check if there are any item category filter option selected
                if (isset($request['checkedFilters']['keepStatus']) && count($request['checkedFilters']['keepStatus']) > 0) {
                    $query->whereIn('keep', $request['checkedFilters']['keepStatus']);
                }
                
                // Check if there are any stock level filter option selected
                if (isset($request['checkedFilters']['stockLevel']) && count($request['checkedFilters']['stockLevel']) > 0) {
                    foreach ($request['checkedFilters']['stockLevel'] as $value) {
                        match ($value) {
                            'In stock' => $query->orWhere('status', 'In stock'),
                            'Low in stock' => $query->orWhere('status', 'Low in stock'),
                            'Out of stock' => $query->orWhere('status', 'Out of stock'),
                        };
                    }
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

        $data = $queries->with([
                            'productItems:id,product_id,inventory_item_id,qty', 
                            'category:id,name', 
                            'productItems.inventoryItem:id,stock_qty,item_cat_id,status',
                            'productItems.inventoryItem.itemCategory:id,low_stock_qty',
                            'saleHistories'
                        ])
                        ->orderBy('product_name')
                        ->get()
                        ->map(function ($product) {
                            $product_items = $product->productItems;
                            $minStockCount = 0;

                            if (count($product_items) > 0) {
                                $stockCountArr = [];

                                foreach ($product_items as $key => $value) {
                                    $inventory_item = IventoryItem::select(['stock_qty', 'item_cat_id'])
                                                                        ->with('itemCategory:id,low_stock_qty')
                                                                        ->find($value['inventory_item_id']);

                                    $stockQty = $inventory_item->stock_qty;
                                    $stockCount = (int)round($stockQty / (int)$value['qty']);

                                    array_push($stockCountArr, $stockCount);
                                }
                                $minStockCount = min($stockCountArr);
                            }
                            $product['stock_left'] = $minStockCount;

                            return $product;
                        });
                        
        return response()->json($data);
    }

    /**
     * Get all inventories along with its items.
     */
    public function getAllInventories()
    {
        return Iventory::withWhereHas('inventoryItems')
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
    }

    /**
     * Get product item's inventory item's stock quantity.
     */
    public function getInventoryItemStock(string $id)
    {
        $data = IventoryItem::select(['item_name', 'stock_qty', 'status'])
                                ->find($id);
        
        return response()->json($data);
    }
    
    /**
     * Update product details.
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

        $existingProduct = isset($id) ? Product::with('productItems')->find($id) : null;
        
        // Delete product items
        if (count($request->itemsDeletedBasket) > 0 && !is_null($existingProduct)) {
            $existingProduct->productItems()
                            ->whereIn('id', $request->itemsDeletedBasket)
                            ->delete();
        }

        if (!is_null($existingProduct)) {
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

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Changes saved.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }
     
    /**
     * Delete product along with its related models.
     */
    public function deleteProduct(Request $request, string $id)
    {
        $existingProduct = Product::with('productItems')->find($id);

        $severity = 'error';
        $summary = 'Selected product unable to be deleted.';

        if ($existingProduct) {
            // Soft delete all related items in bulk
            if ($existingProduct->productItems()->count() > 0) {
                $existingProduct->productItems()->delete();
            }
    
            // Soft delete the product
            $existingProduct->delete();

            $severity = 'success';
            $summary = 'Selected product has been successfully deleted.';
        }

        $message = [ 
            'severity' => $severity, 
            'summary' => $summary
        ];

        return redirect()->back()->with(['message' => $message]);
    }
     
    /**
     * Delete product item.
     */
    public function deleteProductItem(Request $request, string $id)
    {
        $existingProductItem = ProductItem::find($id);

        $existingProductItem->delete();

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Selected product item has been successfully deleted.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    /**
     * Get product sale histories.
     */
    public function getProductSaleHistories(Request $request, string $id)
    {
        $dateFilter = $request->input('dateFilter');
        
        $dateFilter = array_map(function ($date) {
                            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
                        }, $dateFilter);

        // Apply the date filter (single date or date range)
        $data = SaleHistory::whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                                ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                                    $subQuery->whereDate('created_at', '<=', $dateFilter[1]);
                                })
                                ->where('product_id', $id)
                                ->orderBy('created_at', 'desc')
                                ->get();

        return response()->json($data);
    }

    // /**
    //  * Testing get table records.
    //  */
    // public function getTestingRecords()
    // {

    //     $data = Product::with('productItems:id,product_id,qty')
    //                     ->orderBy('id')
    //                     ->get()
    //                     ->map(function ($product) {
    //                         $totalQty = 0;

    //                         foreach ($product->productItems as $key => $value) {
    //                             $totalQty += $value->qty;
    //                         }

    //                         return [
    //                             'id' => $product->id,
    //                             'product_name' => $product->product_name,
    //                             'price' => $product->price,
    //                             'point' => $product->point,
    //                             'keep' => $product->keep,
    //                             'qty' => $totalQty,
    //                             'stock_qty' => $totalQty,
    //                         ];
    //                     });

    //     // dd($data);

    //     return response()->json($data);
    // }

    public function updateAvailability(Request $request)
    {
        // dd($request->all());
        $selectedProduct = Product::find($request->id);

        if ($selectedProduct) {
            $selectedProduct->update([
                'availability' => $request->availabilityWord,
            ]);
        } else {
        }
    }

}
