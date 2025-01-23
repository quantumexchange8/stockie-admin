<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductItemRequest;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Models\PointHistory;
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
                                'productItems.inventoryItem:id,inventory_id,item_name,stock_qty,item_cat_id,status',
                                'productItems.inventoryItem.inventory:id,name',
                                // 'productItems.inventoryItem.itemCategory:id,low_stock_qty',
                                'saleHistories',
                                'discountItems'
                            ])
                            ->orderBy('product_name')
                            ->get()
                            ->map(function ($product) {
                                $product_items = $product->productItems;
                                $minStockCount = 0;
                                $product->image = $product->getFirstMediaUrl('product');

                                if (count($product_items) > 0) {
                                    $stockCountArr = [];

                                    foreach ($product_items as $key => $value) {
                                        $inventory_item = IventoryItem::select(['stock_qty', 'item_cat_id'])->find($value['inventory_item_id']);
                                        $stockCount = (int)bcdiv($inventory_item->stock_qty, (int)$value['qty']);

                                        array_push($stockCountArr, $stockCount);
                                    }
                                    $minStockCount = min($stockCountArr);
                                }
                                $product['stock_left'] = $minStockCount;

                                $product->discountItems = $product->discountItems->filter(fn($item) => $item->discount_id === $product->discount_id);

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

            // $rules['qty'] = $validatedData['bucket']
            //                         ? 'required|integer|min:2'
            //                         : 'required|integer|min:1';

            // $requestMessages['qty.min'] = $validatedData['bucket']
            //                         ? 'A minimum of 2 stocks available are required.'
            //                         : 'A minimum of 1 stock available is required.';

            // Validate product items data
            $productItemValidator = Validator::make($item, $rules, $requestMessages, $productItemRequest->attributes());
            
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
        if (!empty($allItemErrors)) return redirect()->back()->withErrors($allItemErrors)->withInput();

        $this->createProductAndItems($request, $validatedData, $validatedProductItems);

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Product has been successfully added to your menu.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }
    
    /**
     * Store newly created product from inventory items
     */
    public function storeFromInventoryItems(Request $request)
    {
        // Get 
        $productsData = $request->input('items');
        
        $productRequest = new ProductRequest();
        $productItemRequest = new ProductItemRequest();
        $validatedItems = [];
        $allItemErrors = [];

        $rules = array_merge($productRequest->rules(), $productItemRequest->rules());
        $requestMessages = array_merge($productRequest->messages(), $productItemRequest->messages());
        $requestAttributes = array_merge($productRequest->attributes(), $productItemRequest->attributes());

        foreach ($productsData as $index => $item) {
            // Validate product items data
            $productValidator = Validator::make($item, $rules, $requestMessages, $requestAttributes);
            
            if ($productValidator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($productValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $productValidator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedItems[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) return redirect()->back()->withErrors($allItemErrors)->withInput();

        // Separate items into two arrays in a single loop
        $validatedData = [];
        $validatedProductItems = [];

        foreach ($validatedItems as $item) {
            $validatedData[] = array_diff_key($item, array_flip(['inventory_item_id', 'qty']));

            $validatedProductItems[] = [
                'inventory_item_id' => $item['inventory_item_id'],
                'qty' => $item['qty'],
            ];
        }
        // dd($validatedData, $validatedProductItems);
        foreach ($validatedData as $key => $value) {
            $this->createProductAndItems($request, $validatedData[$key], [$validatedProductItems[$key]]);
        }

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Product has been successfully added to your menu.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    private function createProductAndItems($request, $validatedData, $validatedProductItems)
    {
        // dd($request->hasFile('image'));
        $newProduct = Product::create([
            'product_name' => $validatedData['product_name'],
            'bucket' => $validatedData['bucket'] ? 'set' : 'single',
            'price' => $validatedData['price'],
            'is_redeemable' => $validatedData['is_redeemable'],
            'point' => $validatedData['point'],
            'category_id' => $validatedData['category_id'],
            'status' => $this->getProductStatus($validatedProductItems),
            'availability' => 'Available',
        ]);

        activity()->useLog('create-product')
                        ->performedOn($newProduct)
                        ->event('added')
                        ->withProperties([
                            'created_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'category_name' => $newProduct->name,
                        ])
                        ->log("New product '$newProduct->name' is added.");

        if ($request->hasFile('image')) {
            $newProduct->addMedia($validatedData['image'])->toMediaCollection('product');
        }

        if (isset($validatedData['image']) && gettype($validatedData['image']) === 'string') {
            $newProduct->addMediaFromUrl($validatedData['image'])->toMediaCollection('product');
        }

        if (count($validatedProductItems) > 0) {
            foreach ($validatedProductItems as $key => $value) {
                ProductItem::create(attributes: [
                    'product_id' => $newProduct->id,
                    'inventory_item_id' => $value['inventory_item_id'],
                    'qty' => (string) $value['qty'],
                ]);
            }
        }

        return;
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
                    return 'Out of stock';
                } elseif ($inventoryItem->stock_qty < $item['qty']) {
                    return 'Low in stock';
                } else {
                    return 'In stock';
                }
            }
    
            return null; // Handle case where inventory item is not found (optional)
        });
    
        // Determine overall product status
        if ($stockStatuses->contains('Out of stock')) {
            return 'Out of stock';
        } elseif ($stockStatuses->contains('Low in stock')) {
            return 'Low in stock';
        }
    
        return 'In stock';
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
                                'productItems.inventoryItem:id,inventory_id,item_name,stock_qty',
                                'productItems.inventoryItem.inventory',
                                'saleHistories',
                                'pointHistories'
                            ])
                            ->orderBy('created_at', 'desc')
                            ->find($id);

        $product->image = $product->getFirstMediaUrl('product');
        
        $saleHistories = $product->saleHistories()->whereDate('created_at', '>=', $dateFilter[0])
                                                ->whereDate('created_at', '<=', $dateFilter[1])
                                                ->orderBy('created_at', 'desc')
                                                ->get();

        $redemptionHistories = $product->pointHistories()->whereDate('created_at', '>=', $dateFilter[0])
                                                        ->whereDate('created_at', '<=', $dateFilter[1])
                                                        ->with(['redeemableItem:id,product_name', 'handledBy:id,name'])
                                                        ->orderBy('created_at', 'desc')
                                                        ->get();
    
        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        return Inertia::render('Product/Partials/ProductDetail', [
            'message' => $message ?? [],
            'product' => $product,
            'saleHistories' => $saleHistories,
            'redemptionHistories' => $redemptionHistories,
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
                // if (isset($request['checkedFilters']['keepStatus']) && count($request['checkedFilters']['keepStatus']) > 0) {
                //     $query->whereIn('keep', $request['checkedFilters']['keepStatus']);
                // }
                
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

                if (isset($request['checkedFilters']['isRedeemable']) && count($request['checkedFilters']['isRedeemable']) > 0) {
                    $query->where('is_redeemable', 1);
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
                            'productItems.inventoryItem:id,inventory_id,item_name,stock_qty,item_cat_id,status',
                            'productItems.inventoryItem.inventory:id,name',
                            // 'productItems.inventoryItem.itemCategory:id,low_stock_qty',
                            'saleHistories',
                            'discountItems'
                        ])
                        ->orderBy('product_name')
                        ->get()
                        ->map(function ($product) {
                            $product_items = $product->productItems;
                            $minStockCount = 0;
                            $product->image = $product->getFirstMediaUrl('product');

                            if (count($product_items) > 0) {
                                $stockCountArr = [];

                                foreach ($product_items as $key => $value) {
                                    $inventory_item = IventoryItem::select(['stock_qty', 'item_cat_id'])->find($value['inventory_item_id']);
                                    $stockQty = $inventory_item->stock_qty;
                                    $stockCount = (int)bcdiv($stockQty, (int)$value['qty']);

                                    array_push($stockCountArr, $stockCount);
                                }
                                $minStockCount = min($stockCountArr);
                            }
                            $product['stock_left'] = $minStockCount;

                            $product->discountItems = $product->discountItems->filter(fn($item) => $item->discount_id === $product->discount_id);

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
                                'items' => $group_items,
                                'group_image' => $group->getFirstMediaUrl('inventory'),
                            ];
                        });
    }

    /**
     * Get product item's inventory item's stock quantity.
     */
    public function getInventoryItemStock(string $id)
    {
        $data = IventoryItem::with(['inventory:id,name', 'itemCategory:id,name'])
                            ->select(['inventory_id', 'item_name', 'item_cat_id', 'stock_qty', 'status'])
                            ->find($id);

        if ($data) {
            $groupName = $data->inventory->name;
            $unitName = $data->itemCategory->name;

            $data->formattedName = "$groupName - $data->item_name";
            $data->formattedProductName = "$data->item_name ($unitName)";
        }

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

            // $rules['qty'] = $validatedData['bucket']
            //                         ? 'required|integer|min:2'
            //                         : 'required|integer|min:1';

            // $requestMessages['qty.min'] = $validatedData['bucket']
            //                         ? 'A minimum of 2 stocks available are required.'
            //                         : 'A minimum of 1 stock available is required.';

            // Validate product items data
            $productItemValidator = Validator::make(
                $item,
                $rules,
                $requestMessages,
                $productItemRequest->attributes()
            );

            if (($inventoryItemStock->stock_qty - $item['qty']) < 0) {
                $allItemErrors["items.$index.qty"] = 'A minimum of 1 stock available is required.';
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
        if ($request->bucket && $request->itemsDeletedBasket && count($request->itemsDeletedBasket) > 0 && $existingProduct) {
            $existingProduct->productItems()
                            ->whereIn('id', $request->itemsDeletedBasket)
                            ->delete();
        }

        if (!is_null($existingProduct)) {
            $existingProduct->update([
                'product_name' => $validatedData['product_name'],
                'bucket' => $validatedData['bucket'] ? 'set' : 'single',
                'price' => $validatedData['price'],
                'is_redeemable' => $validatedData['is_redeemable'],
                'point' => $validatedData['point'],
                'category_id' => $validatedData['category_id'],
            ]);

            activity()->useLog('edit-product-detail')
                        ->performedOn($existingProduct)
                        ->event('updated')
                        ->withProperties([
                            'created_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'product_name' => $existingProduct->product_name,
                        ])
                        ->log("Product '$existingProduct->product_name' is updated.");

            if ($request->hasFile('image')) {
                $existingProduct->clearMediaCollection('product');
                $existingProduct->addMedia($validatedData['image'])->toMediaCollection('product');
            }
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


     /**
     * Get the redemption histories of the product.
     */
    public function getRedemptionHistories(Request $request, string $id)
    {
        $dateFilter = $request->input('dateFilter');
        
        $dateFilter = array_map(function ($date) {
                            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
                        }, $dateFilter);

        // Apply the date filter (single date or date range)
        $query = PointHistory::whereDate('created_at', count($dateFilter) === 1 ? '=' : '>=', $dateFilter[0])
                                ->when(count($dateFilter) > 1, function($subQuery) use ($dateFilter) {
                                    $subQuery->whereDate('created_at', '<=', $dateFilter[1]);
                                });

        $data = $query->with(['redeemableItem:id,product_name', 'handledBy:id,name'])
                        ->where('product_id', $id)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json($data);
    }

    public function updateAvailability(Request $request)
    {
        $selectedProduct = Product::where('id', $request->id)->first();

        if ($selectedProduct) {
            $selectedProduct->update(['availability' => $request->availabilityWord]);
        }

        $word = strtolower($request->availabilityWord);

        activity()->useLog('deactivate-product')
                    ->performedOn($selectedProduct)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'product_name' => $selectedProduct->product_name,
                    ])
                    ->log("Product '$selectedProduct->product_name' is now $word.");

        return redirect()->back();
    }

    /**
     * Get all the products with the specified category id.
     */
    public function getCategoryProducts(string $id)
    {
        $category = Category::with('products:id,product_name,bucket,category_id')->find($id);
        $products = $category->products;

        foreach ($products as $key => $product) {
            $product->image = $product->getFirstMediaUrl('product');
        };
        
        return response()->json($products);
    }

    /**
     * Store new product category.
     */
    public function storeCategory(Request $request)
    {
        $categoriesError = [];
        $validatedZones = [];

        foreach ($request->input('categories') as $categories) {
            if(!isset($categories['index'])) {
                continue;
            }

            $categoriesValidator = Validator::make($categories, [
                'index' => 'required|integer',
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories')->whereNull('deleted_at'),
                ]
            ], [
                'required' => 'This field is required.',
                'name.string' => 'Invalid input.',
                'name.unique' => 'Category name already exists. Try another one.'
            ]);

            if ($categoriesValidator->fails()) {
                foreach ($categoriesValidator->errors()->messages() as $field => $messages) {
                    $categoriesError["categories.{$categories['index']}.$field"] = $messages;
                }
            } else {
                $validated = $categoriesValidator->validated();
                if(isset($validated['index'])){
                    $validated['index'] = $categories['index'];
                }
                $validatedZones[] = $validated;
            }
        }

        if(!empty($categoriesError)){
            return redirect()->back()->withErrors($categoriesError);
        }

        foreach($validatedZones as $newCategories) {
            $newCategory = Category::create(['name' => $newCategories['name']]);
            activity()->useLog('create-category')
                        ->performedOn($newCategory)
                        ->event('added')
                        ->withProperties([
                            'created_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'category_name' => $newCategory->name,
                        ])
                        ->log("New product category '$newCategory->name' is added.");
        }

        return response()->json($this->getAllCategories());
    }

    /**
     * Update the details of the product category.
     */
    public function updateCategory(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'edit_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories','name')->ignore($id)->whereNull('deleted_at')],
            ]
        );

        $editZone = Category::find($id);
        $editZone->update(['name' => $validatedData['edit_name']]);

        activity()->useLog('edit-category-name')
                    ->performedOn($editZone)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'category_name' => $editZone->name,
                    ])
                    ->log("Category name '$editZone->name' is updated.");

        return response()->json($this->getAllCategories());
    }

    /**
     * Reassign all the category's products' category and then deleting the category.
     */
    public function reassignProductsCategory(Request $request, string $id)
    {
        $products = $request->input('items');
        
        $validatedProducts = [];
        $allItemErrors = [];

        foreach ($products as $index => $item) {
            // Validate product data
            $productValidator = Validator::make(
                $item, 
                [
                    'product_id' => 'required|integer',
                    'new_category_id' => 'required|integer',
                ], 
                [
                    'required' => 'This field is required.',
                    'integer' => 'This field must be an integer.',
                ]
            );
            
            if ($productValidator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($productValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $productValidator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedProducts[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) {
            return redirect()->back()->withErrors($allItemErrors);
        }

        foreach ($validatedProducts as $key => $product) {
            $existingProduct = Product::find($product['product_id']);
            $existingProduct->update(['category_id' => $product['new_category_id']]);
        }

        $category = Category::find($id);                                         

        if ($category) {
            activity()->useLog('delete-category')
                        ->performedOn($category)
                        ->event('deleted')
                        ->withProperties([
                            'edited_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'name' => $category->name,
                        ])
                        ->log("$category->name is deleted.");
            $category->delete();
            
        }

        return response()->json($this->getAllCategories());
    }
}
