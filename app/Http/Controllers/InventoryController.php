<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ItemCategory;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Http\Requests\InventoryItemRequest;
use App\Http\Requests\InventoryRequest;
use App\Models\KeepHistory;
use App\Models\StockHistory;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $inventories = Iventory::with(['inventoryItems', 'inventoryItems.itemCategory:id,name,low_stock_qty'])
                                ->orderBy('id')
                                ->get();

        $endDate = Carbon::now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d');
        $startDate = Carbon::now()->subDays(30)->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d');

        $recentKeepHistories = KeepHistory::with([
                                                'keepItem', 
                                                'keepItem.orderItemSubitem', 
                                                'keepItem.orderItemSubitem.productItem', 
                                                'keepItem.orderItemSubitem.productItem.inventoryItem',
                                                'keepItem.customer:id,full_name', 
                                            ])
                                            ->whereBetween('created_at', [$startDate, $endDate])
                                            ->limit(5)
                                            ->get()
                                            ->map(function ($history) {
                                                $history->item_name = $history->keepItem->orderItemSubitem->productItem->inventoryItem->item_name;
                                    
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

        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        return Inertia::render('Inventory/Inventory', [
            'message' => $message ?? [],
            'inventories' => $inventories,
            'recentKeepHistories' => $recentKeepHistories,
            'categories' => $categories,
            'itemCategories' => $itemCategories,
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

        foreach ($inventoryItemData as $index => $item) {
            $rules = $inventoryItemRequest->rules();
            $rules['item_code'] = 'required|string|max:255|unique:iventory_items,item_code';

            // Validate inventory items data
            $inventoryItemValidator = Validator::make(
                $item,
                $rules,
                $inventoryItemRequest->messages(),
                $inventoryItemRequest->attributes()
            );
            
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
            return redirect()->back()->withErrors($allItemErrors)->withInput();
        }

        $image = $request->hasFile('image') ? $request->file('image')->getClientOriginalName() : '';

        $newGroup = Iventory::create([
            'name' => $validatedData['name'],
            'category_id' => $validatedData['category_id'],
            'image' => $image,
        ]);

        if (count($validatedInventoryItems) > 0) {
            foreach ($validatedInventoryItems as $key => $value) {
                $itemCategory = ItemCategory::select('low_stock_qty')->find($value['item_cat_id']);

                if ($value['stock_qty'] === 0) {
                    $newStatus = 'Out of stock';
                } elseif ($value['stock_qty'] <= $itemCategory->low_stock_qty) {
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

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Group added successfully.',
            'detail' => 'You can always add new stock to this group.'
        ];

        return redirect()->back()->with(['message' => $message]);
    }
    
    /**
     * Get inventory and its items.
     */
    public function getInventories(Request $request)
    {
        $filters = $request->input('checkedFilters', []);

        $selectedCategory = (int) $request->input('selectedCategory', 0);

        $allCategories = Category::select(['id'])
                                ->orderBy('id')
                                ->get();

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
                            $subQuery->orWhere('stock_qty', '>', 0);
                            break;
                        case 'Low Stock':
                            $subQuery->orWhere(function ($lowStockQuery) {
                                $lowStockQuery->where('stock_qty', '>', 0)
                                            ->whereRaw('stock_qty <= (SELECT low_stock_qty FROM item_categories WHERE item_categories.id = item_cat_id)');
                            });
                            break;
                        case 'Out of Stock':
                            $subQuery->orWhere('stock_qty', 0);
                            break;
                    }
                }
            });
        }

        // Get filtered inventory items with their item category
        $filteredItems = $itemQuery->with('itemCategory:id,name,low_stock_qty')->get();

        // Get inventory IDs with matching items
        $inventoryIds = $filteredItems->pluck('inventory_id')->unique();
    
        // Get inventories with matching items
        $inventoryQuery  = Iventory::query();

        if ($selectedCategory) {
            if ($selectedCategory === 0) {
                $inventoryQuery->whereIn('category_id', $allCategories);
            } else {
                $inventoryQuery->where('category_id', $selectedCategory);
            }
        }
    
        // Get inventories with filtered items
        $data = $inventoryQuery->whereIn('id', $inventoryIds)
                                ->with(['inventoryItems' => function ($query) use ($filteredItems) {
                                    $query->whereIn('id', $filteredItems->pluck('id'));
                                }, 'inventoryItems.itemCategory:id,name,low_stock_qty'])
                                ->orderBy('id')
                                ->get();

        return response()->json($data);
    }

    /**
     * Get inventory items.
     */
    public function getInventoryItems(string $id)
    {
        $data = Iventory::with('inventoryItems')
                        ->find($id);

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

                $existingItem = IventoryItem::with('itemCategory:id,low_stock_qty')->find($value['id']);

                // if ($key === 2) dd($calculatedStock);
                
                if ($calculatedStock === 0) {
                    $newStatus = 'Out of stock';
                } elseif ($calculatedStock <= $existingItem->itemCategory['low_stock_qty']) {
                    $newStatus = 'Low in stock';
                } else {
                    $newStatus = 'In stock';
                }

                $existingItem->update([
                    'stock_qty' => $calculatedStock,
                    'status' => $newStatus
                ]);

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

        foreach ($inventoryItemData as $index => $item) {
            $rules = $inventoryItemRequest->rules();
            
            $rules['inventory_id'] = 'required|integer';
            
            $rules['item_code'] = isset($item['id']) 
                                    ?   [
                                            'required',
                                            'string',
                                            'max:255',
                                            Rule::unique('iventory_items')->ignore($item['id'], 'id'),
                                        ]
                                    : $rules['item_code'] = 'required|string|max:255|unique:iventory_items,item_code';
            // if (isset($item['id'])) {
            //     // Add unique rule while igoring self
            //     $rules['item_code'] = [
            //         'required',
            //         'string',
            //         'max:255',
            //         Rule::unique('iventory_items')->ignore($item['id'], 'id'),
            //     ];
            // } else {
            //     // Add unique rule 
            //     $rules['item_code'] = 'required|string|max:255|unique:iventory_items,item_code';
            // }

            // Validate inventory items data
            $inventoryItemValidator = Validator::make(
                $item,
                $rules,
                $inventoryItemRequest->messages(),
                $inventoryItemRequest->attributes()
            );
            
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
            return redirect()->back()->withErrors($allItemErrors)->withInput();
        }

        // Update inventory data
        if (isset($id)) {
            $existingGroup = Iventory::find($id);

            $existingGroup->update([
                'name' => $inventoryData['name'],
                'category_id' => $inventoryData['category_id'],
            ]);
        }

        // Update inventory items data
        if (count($validatedInventoryItems) > 0) {
            foreach ($validatedInventoryItems as $key => $value) {
                $itemCategory = ItemCategory::select('low_stock_qty')->find($value['item_cat_id']);

                if ($value['stock_qty'] === 0) {
                    $newStatus = 'Out of stock';
                } elseif ($value['stock_qty'] <= $itemCategory->low_stock_qty) {
                    $newStatus = 'Low in stock';
                } else {
                    $newStatus = 'In stock';
                }

                if (isset($value['id'])) {
                    $existingItem = IventoryItem::find($value['id']);

                    $existingItem->update([
                        'item_name' => $value['item_name'],
                        'item_code' => $value['item_code'],
                        'item_cat_id' => $value['item_cat_id'],
                        'stock_qty' => $value['stock_qty'],
                        'status' => $newStatus,
                    ]);
                } else {
                    IventoryItem::create([
                        'inventory_id' => $id,
                        'item_name' => $value['item_name'],
                        'item_code' => $value['item_code'],
                        'item_cat_id' => $value['item_cat_id'],
                        'stock_qty' => $value['stock_qty'],
                        'status' => $newStatus,
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
     * Delete inventory along with all its items.
     */
    public function deleteInventory(Request $request, string $id)
    {
        $data = $request->all();

        $existingGroup = Iventory::with('inventoryItems')
                                    ->find($id);

        $existingGroupItems = $existingGroup->inventoryItems;
        
        if (count($existingGroupItems) > 0) {
            foreach ($existingGroupItems as $key => $value) {
                if (isset($value['id'])) {
                    $existingItem = IventoryItem::find($value['id']);
    
                    $existingItem->delete();
                }
            }
        }

        $existingGroup->delete();

        $message = [ 
            'severity' => 'success', 
            'summary' => 'Selected group has been deleted successfully.',
        ];

        return redirect()->back()->with(['message' => $message]);
    }
    
    /**
     * View inventory keep histories.
     */
    public function viewKeepHistories(Request $request)
    {
        $dateFilter = [
            now()->subDays(7)->timezone('Asia/Kuala_Lumpur')->format('Y-m-d'),
            now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d')
        ];

        $keepHistories = KeepHistory::with([
                                            'keepItem', 
                                            'keepItem.orderItemSubitem', 
                                            'keepItem.orderItemSubitem.productItem', 
                                            'keepItem.orderItemSubitem.productItem.inventoryItem',
                                            'keepItem.customer:id,full_name', 
                                        ])
                                        ->whereBetween('created_at', [$dateFilter[0], $dateFilter[1]])
                                        ->where('status', 'Keep')
                                        ->orderBy('created_at', 'desc')
                                        ->get()
                                        ->map(function ($history) {
                                            $history->item_name = $history->keepItem->orderItemSubitem->productItem->inventoryItem->item_name;
                                
                                            unset($history->keepItem->orderItemSubitem);

                                            return $history;
                                        });

        // dd($keepHistories);

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

        $data = $query->with([
                            'keepItem', 
                            'keepItem.orderItemSubitem', 
                            'keepItem.orderItemSubitem.productItem', 
                            'keepItem.orderItemSubitem.productItem.inventoryItem',
                            'keepItem.customer:id,full_name', 
                        ])
                        ->where('status', 'Keep')
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($history) {
                            $history->item_name = $history->keepItem->orderItemSubitem->productItem->inventoryItem->item_name;
                
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

        $stockHistories = StockHistory::with('inventory:id,name')
                                        ->whereDate('created_at', '>=', $dateFilter[0])
                                        ->whereDate('created_at', '<=', $dateFilter[1])
                                        ->orderBy('created_at', 'desc')
                                        ->get();

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
                        
        return response()->json($data);
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