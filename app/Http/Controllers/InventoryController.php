<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ItemCategory;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Http\Requests\InventoryItemRequest;
use App\Http\Requests\InventoryRequest;
use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\StockHistory;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $inventories = Iventory::with([
                                    'inventoryItems' => function ($query) {
                                        $query->selectRaw('iventory_items.*, SUM(keep_items.qty) as total_keep_qty')
                                                ->leftJoin('product_items', 'iventory_items.id', '=', 'product_items.inventory_item_id')
                                                ->leftJoin('order_item_subitems', 'product_items.id', '=', 'order_item_subitems.product_item_id')
                                                ->leftJoin('keep_items', 'order_item_subitems.id', '=', 'keep_items.order_item_subitem_id')
                                                ->groupBy('iventory_items.id');
                                    },
                                    'inventoryItems.itemCategory:id,name',
                                    'inventoryItems.productItems:id,inventory_item_id,product_id',
                                    'inventoryItems.productItems.product:id',
                                    'inventoryItems.productItems.orderSubitems:id,product_item_id',
                                ])
                                ->orderBy('id')
                                ->get()
                                ->map(function ($group) {
                                    $group->inventoryItems->each(function ($item) {
                                        // Collect unique product and assign to $item->products
                                        $item->products = $item->productItems
                                                ->pluck('product')
                                                ->unique('id')
                                                ->map(fn($product) => [
                                                    'id' => $product->id,
                                                    'image' => $product->getFirstMediaUrl('product'),
                                                ])
                                                ->values();

                                        $item->total_keep_qty = $item->total_keep_qty ?? 0;
                            
                                        unset($item->productItems);
                                        
                                    });

                                    return $group;
                                });

        $inventories->each(function($inventory){
            $inventory->inventory_image = $inventory->getFirstMediaUrl('inventory');
        });

        $endDate = Carbon::now()->setTimezone('Asia/Kuala_Lumpur')->addDay()->format('Y-m-d');
        $startDate = Carbon::now()->subDays(30)->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d');

        $recentKeepHistories = KeepHistory::with([
                                                'keepItem.orderItemSubitem.productItem.inventoryItem',
                                                'keepItem.customer:id,full_name,phone', 
                                            ])
                                            ->whereBetween('created_at', [$startDate, $endDate])
                                            ->where('status', 'Keep')
                                            ->orderBy('created_at', 'desc')
                                            ->limit(5)
                                            ->get()
                                            ->transform(function ($history) {
                                                $history->item_name = $history->keepItem->orderItemSubitem->productItem->inventoryItem->item_name;
                                                $history->customer_image = $history->keepItem->customer->getFirstMediaUrl('customer');
                                                unset($history->keepItem->orderItemSubitem);

                                                return $history;
                                            });

        $categories = Category::select(['id', 'name'])
                                ->orderBy('id')
                                ->get()
                                ->map(function ($category) {
                                    return [
                                        'text' => $category->name,
                                        'value' => $category->id
                                    ];
                                });

        $itemCategories = ItemCategory::select(['id', 'name'])
                                    ->orderBy('id')
                                    ->get()
                                    ->map(function ($category) {
                                        return [
                                            'text' => $category->name,
                                            'value' => $category->id
                                        ];
                                    });

        $keepItemsQuantity = KeepItem::where('status', 'Keep')->sum('qty');
        $keepItemsCm = KeepItem::where('status', 'Keep')->where('cm', '>', 0)->count();
        $keepItemsCount = $keepItemsCm + $keepItemsQuantity;

        return Inertia::render('Inventory/Inventory', [
            'inventories' => $inventories,
            // 'outOfStockItems' => $outOfStockItems,
            'recentKeepHistories' => $recentKeepHistories,
            'categories' => $categories,
            'itemCategories' => $itemCategories,
            'keepItemsCount' => (int)$keepItemsCount
        ]);
    }
    
    public function store(InventoryRequest $request)
    {
        // Get validated inventory data
        $validatedData = $request->validated();

        // Get inventory items
        $inventoryItemData = $request->input('items');
        
        $inventoryItemRequest = new InventoryItemRequest();
        $validatedInventoryItems = [];
        $allItemErrors = [];

        // Track unique codes and their first occurrence index
        $uniqueCodeTracker = [];

        foreach ($inventoryItemData as $index => $item) {
            $rules = $inventoryItemRequest->rules();
            $rules['item_code'] = 'required|string|max:255|unique:iventory_items,item_code';

            // Check for item code uniqueness
            if (isset($uniqueCodeTracker[$item['item_code']])) {
                // If this code has been seen before, add error only to the current (subsequent) item
                $allItemErrors["items.$index.item_code"] = ['This unique code has been taken'];
            } else {
                // First occurrence of this code
                $uniqueCodeTracker[$item['item_code']] = $index;
            }

            // Validate inventory items data
            $inventoryItemValidator = Validator::make($item, $rules, $inventoryItemRequest->messages(), $inventoryItemRequest->attributes());
            
            if ($inventoryItemValidator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($inventoryItemValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $inventoryItemValidator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedInventoryItems[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) {
            return redirect()->back()->withErrors($allItemErrors);
        }

        $newGroup = Iventory::create(['name' => $validatedData['name']]);

        activity()->useLog('create-inventory-group')
                        ->performedOn($newGroup)
                        ->event('added')
                        ->withProperties([
                            'created_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'category_name' => $newGroup->name,
                        ])
                        ->log("New product '$newGroup->name' is added.");

        if ($request->hasFile('image')) {
            $newGroup->addMedia($validatedData['image'])->toMediaCollection('inventory');
        };
        
        if (count($validatedInventoryItems) > 0) {
            foreach ($validatedInventoryItems as $key => $value) {
                if ($value['stock_qty'] === 0) {
                    $newStatus = 'Out of stock';
                } elseif ($value['stock_qty'] <= $value['low_stock_qty']) {
                    $newStatus = 'Low in stock';
                } else {
                    $newStatus = 'In stock';
                }

                IventoryItem::create([
                    'inventory_id' => $newGroup->id,
                    'item_name' => $value['item_name'],
                    'item_code' => $value['item_code'],
                    'item_cat_id' => $value['item_cat_id'],
                    'stock_qty' => $value['stock_qty'],
                    'low_stock_qty' => $value['low_stock_qty'],
                    'keep' => $value['keep'],
                    'status' => $newStatus,
                ]);    

                if ($value['stock_qty'] > 0) {
                    StockHistory::create([
                        'inventory_id' => $newGroup->id,
                        'inventory_item' => $value['item_name'],
                        'old_stock' => 0,
                        'in' => $value['stock_qty'],
                        'out' => 0,
                        'current_stock' => $value['stock_qty'],
                    ]);
                }
            }
        }

        // $message = [ 
        //     'severity' => 'success', 
        //     'summary' => 'Group added successfully.',
        //     'detail' => 'You can always add new stock to this group.'
        // ];

        // $newInventory = Iventory::select(['id', 'name', 'image'])
        //                         ->with([
        //                             'inventoryItems:id,inventory_id,item_name,item_cat_id', 
        //                             'inventoryItems.itemCategory:id,name'
        //                         ])->find(1);
                                
        // return response()->json(['newInventory' => $newInventory], 303);

        return redirect()->back();
    }

    /**
     * Get the newly created inventory and its items.
     */
    public function getLatestInventory()
    {
        $latestInventory = Iventory::select(['id', 'name', 'image'])
                        ->with([
                            'inventoryItems:id,inventory_id,item_name,item_cat_id', 
                            'inventoryItems.itemCategory:id,name'
                        ])
                        ->limit(1)
                        ->latest()
                        ->get();

        $latestInventory[0]->inventory_image = $latestInventory[0]->getFirstMediaUrl('inventory');

        $inventories = Iventory::with([
                                    'inventoryItems' => function ($query) {
                                        $query->selectRaw('iventory_items.*, SUM(keep_items.qty) as total_keep_qty')
                                                ->leftJoin('product_items', 'iventory_items.id', '=', 'product_items.inventory_item_id')
                                                ->leftJoin('order_item_subitems', 'product_items.id', '=', 'order_item_subitems.product_item_id')
                                                ->leftJoin('keep_items', 'order_item_subitems.id', '=', 'keep_items.order_item_subitem_id')
                                                ->groupBy('iventory_items.id');
                                    },
                                    'inventoryItems.itemCategory:id,name',
                                    'inventoryItems.productItems:id,inventory_item_id,product_id',
                                    'inventoryItems.productItems.product:id',
                                    'inventoryItems.productItems.orderSubitems:id,product_item_id',
                                ])
                                ->orderBy('id')
                                ->get()
                                ->map(function ($group) {
                                    $group->inventory_image = $group->getFirstMediaUrl('inventory');

                                    $group->inventoryItems->each(function ($item) {
                                        // Collect unique product and assign to $item->products
                                        $item->products = $item->productItems
                                                ->pluck('product')
                                                ->unique('id')
                                                ->map(fn($product) => [
                                                    'id' => $product->id,
                                                    'image' => $product->getFirstMediaUrl('product'),
                                                ])
                                                ->values();

                                        $item->total_keep_qty = $item->total_keep_qty ?? 0;
                            
                                        unset($item->productItems);
                                        
                                    });

                                    return $group;
                                });

        $data = [
            'latestInventory' => $latestInventory[0],
            'inventories' => $inventories
        ];

        return response()->json($data);
    }
    
    /**
     * Get inventory and its items.
     */
    public function getInventories(Request $request)
    {
        $filters = $request->input('checkedFilters', []);

        // $selectedCategory = (int) $request->input('selectedCategory', 0);
        // $allCategories = Category::select(['id'])->orderBy('id')->get();

        // Base query for filtering inventory items
        $itemQuery = IventoryItem::query();

        // Apply item category filters if present
        if (!empty($filters['itemCategory'])) {
            $itemQuery->whereIn('item_cat_id', $filters['itemCategory']);
        }

        // Apply stock level filters if present
        if (!empty($filters['stockLevel'])) {
            $itemQuery->where(function ($subQuery) use ($filters) {
                foreach ($filters['stockLevel'] as $level) {
                    switch ($level) {
                        case 'In Stock':
                            $subQuery->orWhere(function ($lowStockQuery) {
                                $lowStockQuery->whereColumn('stock_qty', '>', 'low_stock_qty');
                            });
                            break;
                        case 'Low Stock':
                            $subQuery->orWhere(function ($lowStockQuery) {
                                $lowStockQuery->where('stock_qty', '>', 0)->whereColumn('stock_qty', '<=', 'low_stock_qty');
                            });
                            break;
                        case 'Out of Stock':
                            $subQuery->orWhere('stock_qty', 0);
                            break;
                    }
                }
            });
        }
        
        // Apply item category filters if present
        if (!empty($filters['keepStatus'])) {
            foreach ($filters['keepStatus'] as $status) {
                $itemQuery->orWhere('keep', $status);
            };
        }


        // Get filtered inventory items with their item category
        $filteredItems = $itemQuery->with('itemCategory:id,name')->get();

        // Get inventory IDs with matching items
        $inventoryIds = $filteredItems->pluck('inventory_id')->unique();
    
        // Get inventories with matching items
        $inventoryQuery  = Iventory::query();

        // if ($selectedCategory) {
        //     if ($selectedCategory === 0) {
        //         $inventoryQuery->whereIn('category_id', $allCategories);
        //     } else {
        //         $inventoryQuery->where('category_id', $selectedCategory);
        //     }
        // }
    
        // Get inventories with filtered items
        $data = $inventoryQuery->whereIn('id', $inventoryIds)
                                ->with([
                                    'inventoryItems' => fn ($query) => $query->whereIn('id', $filteredItems->pluck('id')), 
                                    'inventoryItems.itemCategory:id,name',
                                    'inventoryItems.productItems:id,inventory_item_id,product_id',
                                    'inventoryItems.productItems.product:id',
                                    'inventoryItems.productItems.orderSubitems:id,product_item_id',
                                ])
                                ->orderBy('id')
                                ->get()
                                ->map(function ($group) {
                                    $group->inventoryItems->each(function ($item) {
                                        // Collect unique product and assign to $item->products
                                        $item->products = $item->productItems
                                                ->pluck('product')
                                                ->unique('id')
                                                ->map(fn($product) => [
                                                    'id' => $product->id,
                                                    'image' => $product->getFirstMediaUrl('product'),
                                                ])
                                                ->values();

                                        $item->total_keep_qty = $item->total_keep_qty ?? 0;
                            
                                        unset($item->productItems);
                                        
                                    });

                                    return $group;
                                });

        $data->each(function($inventory){
            $inventory->inventory_image = $inventory->getFirstMediaUrl('inventory');
        });

        return response()->json($data);
    }

    /**
     * Get inventory items.
     */
    public function getInventoryItems(string $id)
    {
        $data = Iventory::with('inventoryItems')->find($id);

        return response()->json($data);
    }

    /**
     * Update inventory item's stock.
     */
    public function updateInventoryItemStock(Request $request, string $id)
    {
        $data = $request->all();
        $replenishedItem = [];

        if (isset($id) && count($data['items']) > 0) {
            foreach ($data['items'] as $key => $value) {
                if ($value['add_stock_qty'] > 0) array_push($replenishedItem, '\'' . $value['item_name'] . '\'');

                $calculatedStock = $value['stock_qty'] + $value['add_stock_qty'];

                $existingItem = IventoryItem::where('id', $value['id'])->first();

                // if ($key === 2) dd($calculatedStock);
                
                if ($calculatedStock === 0) {
                    $newStatus = 'Out of stock';
                } elseif ($calculatedStock <= $existingItem->low_stock_qty) {
                    $newStatus = 'Low in stock';
                } else {
                    $newStatus = 'In stock';
                }

                $existingItem->update([
                    'stock_qty' => $calculatedStock,
                    'status' => $newStatus
                ]);

                activity()->useLog('replenish-stock')
                            ->performedOn($existingItem)
                            ->event('updated')
                            ->withProperties([
                                'edited_by' => auth()->user()->full_name,
                                'image' => auth()->user()->getFirstMediaUrl('user'),
                                'item_name' => $existingItem->item_name,
                            ])
                            ->log("$existingItem->item_name is replenished.");

                if ($value['add_stock_qty'] > 0) {
                    StockHistory::create([
                        'inventory_id' => $existingItem->inventory_id,
                        'inventory_item' => $value['item_name'],
                        'old_stock' => $value['stock_qty'],
                        'in' => $value['add_stock_qty'],
                        'out' => 0,
                        // 'out' => $value['add_stock_qty'] < 0 ? abs($value['add_stock_qty']) : 0,
                        'current_stock' => $existingItem->stock_qty,
                    ]);
                }
            }
        }
        
        $replenishedItem = implode(', ', $replenishedItem);
        
        $message = [ 
            'severity' => 'success', 
            'summary' => 'Stock replenished successfully.',
            'detail' => "You've replenished stock for $replenishedItem."
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    /**
     * Update inventory group and items' details.
     */
    public function updateInventoryAndItems(InventoryRequest $request, string $id)
    {
        // Get validated inventory data
        $inventoryData = $request->validated();

        // Get inventory items
        $inventoryItemData = $request->input('items');
        
        $inventoryItemRequest = new InventoryItemRequest();
        $validatedInventoryItems = [];
        $allItemErrors = [];

        // Track unique codes and their first occurrence index
        $uniqueCodeTracker = [];

        foreach ($inventoryItemData as $index => $item) {
            $rules = $inventoryItemRequest->rules();
            
            $rules['inventory_id'] = 'required|integer';
            
            $rules['item_code'] = isset($item['id']) 
                                    ?   [
                                            'required',
                                            'string',
                                            'max:255',
                                            Rule::unique('iventory_items')->ignore($item['id']),
                                        ]
                                    : $rules['item_code'] = 'required|string|max:255|unique:iventory_items,item_code';

            // Check for item code uniqueness
            if (isset($uniqueCodeTracker[$item['item_code']])) {
                // If this code has been seen before, add error only to the current (subsequent) item
                $allItemErrors["items.$index.item_code"] = ['This unique code has been taken'];
            } else {
                // First occurrence of this code
                $uniqueCodeTracker[$item['item_code']] = $index;
            }

            // Validate inventory items data
            $inventoryItemValidator = Validator::make($item, $rules, $inventoryItemRequest->messages(), $inventoryItemRequest->attributes());
            
            if ($inventoryItemValidator->fails()) {
                // Collect the errors for each item and add to the array with item index
                foreach ($inventoryItemValidator->errors()->messages() as $field => $messages) {
                    $allItemErrors["items.$index.$field"] = $messages;
                }
            } else {
                // Collect the validated item and manually add the 'id' field back
                $validatedItem = $inventoryItemValidator->validated();
                if (isset($item['id'])) {
                    $validatedItem['id'] = $item['id'];
                }
                $validatedInventoryItems[] = $validatedItem;
            }
        }

        // If there are any item validation errors, return them
        if (!empty($allItemErrors)) {
            return redirect()->back()->withErrors($allItemErrors);
        }

        // Update inventory data
        if (isset($id)) {
            $existingGroup = Iventory::find($id);

            $existingGroup->update([
                'name' => $inventoryData['name'],
            ]);

            activity()->useLog('edit-group-detail')
                        ->performedOn($existingGroup)
                        ->event('updated')
                        ->withProperties([
                            'created_by' => auth()->user()->full_name,
                            'image' => auth()->user()->getFirstMediaUrl('user'),
                            'group_name' => $existingGroup->name,
                        ])
                        ->log("Group name '$existingGroup->name' is updated.");

            if($request->hasFile('image')){
                $existingGroup->clearMediaCollection('inventory');
                $existingGroup->addMedia($inventoryData['image'])->toMediaCollection('inventory');
            }
        }

        // Update inventory items data
        if (count($validatedInventoryItems) > 0) {
            foreach ($validatedInventoryItems as $key => $value) {
                if ($value['stock_qty'] === 0) {
                    $newStatus = 'Out of stock';
                } elseif ($value['stock_qty'] <= $value['low_stock_qty']) {
                    $newStatus = 'Low in stock';
                } else {
                    $newStatus = 'In stock';
                }

                if (isset($value['id'])) {
                    $existingItem = IventoryItem::where('id', $request['id'])->first();

                    $existingItem->update([
                        'item_name' => $value['item_name'],
                        'item_code' => $value['item_code'],
                        'item_cat_id' => $value['item_cat_id'],
                        'stock_qty' => $value['stock_qty'],
                        'low_stock_qty' => $value['low_stock_qty'],
                        'keep' => $value['keep'],
                        'status' => $newStatus,
                    ]);

                    activity()->useLog('edit-item-detail')
                                ->performedOn($existingItem)
                                ->event('updated')
                                ->withProperties([
                                    'created_by' => auth()->user()->full_name,
                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                    'item_name' => $existingItem->item_name,
                                ])
                                ->log("Item details for '$existingItem->item_name' is updated.");
                } else {
                    $newItem = IventoryItem::create([
                        'inventory_id' => $id,
                        'item_name' => $value['item_name'],
                        'item_code' => $value['item_code'],
                        'item_cat_id' => $value['item_cat_id'],
                        'stock_qty' => $value['stock_qty'],
                        'low_stock_qty' => $value['low_stock_qty'],
                        'keep' => $value['keep'],
                        'status' => $newStatus,
                    ]);

                    activity()->useLog('create-inventory-item')
                                ->performedOn($newItem)
                                ->event('added')
                                ->withProperties([
                                    'created_by' => auth()->user()->full_name,
                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                    'item_name' => $newItem->item_name,
                                ])
                                ->log("Inventory item '$newItem->item_name' is added.");
                }
            }
        }

        // $message = [ 
        //     'severity' => 'success', 
        //     'summary' => 'Changes saved.'
        // ];

        return redirect()->back();
    }

    /**
     * Delete inventory along with all its items.
     */
    public function deleteInventory(Request $request, string $id)
    {
        $existingGroup = Iventory::with('inventoryItems')->find($id);
        $existingGroupItems = $existingGroup->inventoryItems;
        
        if (count($existingGroupItems) > 0) {
            foreach ($existingGroupItems as $key => $value) {
                if (isset($value['id'])) {
                    $existingItem = IventoryItem::find($value['id']);
                    $existingItem->delete();
                }
            }
        }

        activity()->useLog('delete-inventory-group')
                    ->performedOn($existingGroup)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'group_name' => $existingGroup->name,
                    ])
                    ->log("$existingGroup->name is deleted.");

        $existingGroup->delete();

        // $message = [ 
        //     'severity' => 'success', 
        //     'summary' => 'Selected group has been deleted successfully.',
        // ];

        return redirect()->back();
    }
    
    /**
     * View inventory keep histories.
     */
    public function viewKeepHistories(Request $request)
    {
        $dateFilter = [
            now()->subMonths(3)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->addDay()->format('Y-m-d')
        ];

        $keepHistories = KeepHistory::with([
                                            'keepItem.orderItemSubitem.productItem.inventoryItem',
                                            'keepItem.customer:id,full_name',
                                            'keepItem.waiter:id,full_name'
                                        ])
                                        ->whereBetween('created_at', $dateFilter)
                                        ->orderBy('created_at', 'desc')
                                        ->get()
                                        ->transform(function ($history) {
                                            $history->item_name = $history->keepItem->orderItemSubitem->productItem->inventoryItem->item_name;
                                            $history->customer_image = $history->keepItem->customer->getFirstMediaUrl('customer');
                                            $history->waiter_image = $history->keepItem->waiter->getFirstMediaUrl('user');
                                            unset($history->keepItem->orderItemSubitem);

                                            return $history;
                                        });

        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        return Inertia::render('Inventory/Partials/KeepHistory', [
            'message' => $message ?? [],
            'keepHistories' => $keepHistories
        ]);
    }

    /**
     * Get all keep histories.
     */
    public function getAllKeepHistory(Request $request)
    {
        $dateFilter = $request->input('dateFilter');
        $checkedFilters = $request->input('checkedFilters');
        $query = KeepHistory::query();

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

        if ($checkedFilters && is_array($checkedFilters)) {
            $query->whereHas('keepItem', function ($query) use ($checkedFilters) {
                
                // filtering with expire in by getting the largest value of all selection
                if (!empty($checkedFilters['expiresIn'])) {
                    $query->whereDate('expired_to', '<=', Carbon::now()->startOfDay()->addDays(max($checkedFilters['expiresIn'])));
                }

            });
            // filtering with selection in kept in
            if (!empty($checkedFilters['keptIn']) && count($checkedFilters['keptIn']) === 1) {
                if($checkedFilters['keptIn'][0] === 'cm') {
                    $query->where('qty', '=', '0');
                } elseif ($checkedFilters['keptIn'][0] === 'qty') {
                    $query->where('cm', '=', '0');
                }
            }
        }

        $data = $query->with([
                            'keepItem.orderItemSubitem.productItem.inventoryItem',
                            'keepItem.customer:id,full_name', 
                            'keepItem.waiter:id,full_name'
                        ])
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->transform(function ($history) {
                            $history->item_name = $history->keepItem->orderItemSubitem->productItem->inventoryItem->item_name;
                            $history->customer_image = $history->keepItem->customer->getFirstMediaUrl('customer');
                            $history->waiter_image = $history->keepItem->waiter->getFirstMediaUrl('user');
                            unset($history->keepItem->orderItemSubitem);

                            return $history;
                        });
                        
        return response()->json($data);
    }

    /**
     * View inventory stock histories.
     */
    public function viewStockHistories(Request $request)
    {
        $dateFilter = [
            now()->subDays(7)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
        ];

        $stockHistories = StockHistory::with('inventory:id,name,image')
                                        ->whereDate('created_at', '>=', $dateFilter[0])
                                        ->whereDate('created_at', '<=', $dateFilter[1])
                                        ->orderBy('created_at', 'desc')
                                        ->get();

        $stockHistories->each(function ($stockHistory) {
            if ($stockHistory->inventory) {
                $stockHistory->inventory->image = $stockHistory->inventory->getFirstMediaUrl('inventory');
            }
        });

        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        return Inertia::render('Inventory/Partials/StockHistory', [
            'message' => $message ?? [],
            'stockHistories' => $stockHistories,
        ]);
    }

    /**
     * Get all inventory stock histories.
     */
    public function getAllStockHistory(Request $request)
    {
        $dateFilter = $request->input('dateFilter');
        $query = StockHistory::query();

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

        $data = $query->with('inventory:id,name')
                        ->orderBy('created_at', 'desc')
                        ->get();

        $data->each(function ($stockHistory) {
            if ($stockHistory->inventory) {
                $stockHistory->inventory->image = $stockHistory->inventory->getFirstMediaUrl('inventory');
            }
        });
                        
        return response()->json($data);
    }

    public function activeKeptItem()
    {
        $activeKeptItem = $this->getKeptItem();

        return Inertia::render('Inventory/Partials/ActiveKeptItem', [
            'activeKeptItem' => $activeKeptItem,
        ]);
    }

    private function getKeptItem($dateFilter = null, $checkedFilters = null)
    {
        $query = KeepItem::with([
                                'customer:email,full_name,id,phone,point,ranking',
                                'customer.keepItems.waiter:id,full_name',
                                'customer.rank:id,name',
                                'customer.keepItems.orderItemSubitem.productItem.inventoryItem',
                                'orderItemSubitem.productItem.inventoryItem'
                            ])
                            ->where('status', 'Keep')
                            ->whereDate('created_at', '>=', now()->subMonths(6)->timezone('Asia/Kuala_Lumpur'));
    
        if ($dateFilter) {
            $dateFilter = array_map(function ($date) {
                return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
            }, $dateFilter);
    
            if (count($dateFilter) === 1) {
                $query->whereDate('created_at', '=', $dateFilter[0]);
            } else {
                $query->whereDate('created_at', '>=', $dateFilter[0])
                      ->whereDate('created_at', '<=', $dateFilter[1]);
            }
        }

        if ($checkedFilters) {
            if (!empty($checkedFilters['expiresIn']) && is_array($checkedFilters['expiresIn'])) {
                $query->whereNotNull('expired_to')
                    ->whereDate('expired_to', '<=', Carbon::now()->startOfDay()->addDays((int)(max($checkedFilters['expiresIn']))));
            }

            if(!empty($checkedFilters['keptIn']) && count($checkedFilters['keptIn']) === 1) {
                if($checkedFilters['keptIn'][0] === 'cm') {
                    $query->where('qty', '=', '0');
                } elseif ($checkedFilters['keptIn'][0] === 'qty') {
                    $query->where('cm', '=', '0');
                }
            }
        }
    
        $activeKeptItem = $query->get();
    
        $activeKeptItem->each(function ($keepItem) {
            if ($keepItem->customer) {
                $keepItem->customer->image = $keepItem->customer->getFirstMediaUrl('customer');
            }

            if ($keepItem->customer->rank) {
                $keepItem->customer->rank->image = $keepItem->customer->rank->getFirstMediaUrl('ranking');
            }

            foreach ($keepItem->customer->keepItems as $keepItem) {
                $keepItem->item_name = $keepItem->orderItemSubitem->productItem->inventoryItem['item_name'];
                $keepItem->image = $keepItem->orderItemSubitem->productItem 
                            ? $keepItem->orderItemSubitem->productItem->product->getFirstMediaUrl('product') 
                            : $keepItem->orderItemSubitem->productItem->inventoryItem->inventory->getFirstMediaUrl('inventory');
                unset($keepItem->orderItemSubitem);
                
                $keepItem->waiter->image = $keepItem->waiter->getFirstMediaUrl('user');
            }
        });
    
        return $activeKeptItem;
    }
    

    public function filterKeptItem(Request $request)
    {
        $dateFilter = $request->input('date_filter');
        $checkedFilter = $request->input('checkedFilters');
    
        $currentKeptItem = $this->getKeptItem($dateFilter, $checkedFilter);
    
        return response()->json($currentKeptItem);
    }
    

    /**
     * Testing get data for dropdown grouped option.
     */
    // public function getDropdownValue()
    // {
    //     $data = Iventory::withWhereHas('inventoryItems')
    //                     ->select(['id', 'name'])
    //                     ->orderBy('id')
    //                     ->get()
    //                     ->map(function ($group) {
    //                         $group_items = $group->inventoryItems->map(function ($item) {
    //                             return [
    //                                 'text' => $item->item_name, // Assuming `name` is the property you want to use for text
    //                                 'value' => $item->id,  // Assuming `id` is the property you want to use for value
    //                             ];
    //                         });

    //                         return [
    //                             'group_name' => $group->name,
    //                             'items' => $group_items
    //                         ];
    //                     });

    //     return response()->json($data);
    // }
}