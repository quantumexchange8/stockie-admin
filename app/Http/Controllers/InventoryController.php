<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ItemCategory;
use App\Models\Iventory;
use App\Models\IventoryItem;
use App\Http\Requests\InventoryItemRequest;
use App\Http\Requests\InventoryRequest;
use App\Models\KeepHistory;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Inventory/Inventory');
    }
    
    public function store(InventoryRequest $request)
    {
        $data = $request->all();

        if (count($data['items']) > 0) {
            $inventoryItemRequest = new InventoryItemRequest();
            $request->validate($inventoryItemRequest->rules(), $inventoryItemRequest->messages(), $inventoryItemRequest->attributes());
        }

        // dd($data);
        $image = $request->hasFile('image') ? $request->file('image')->getClientOriginalName() : '';

        $newGroup = Iventory::create([
            'name' => $data['name'],
            'category_id' => $data['category_id'],
            'image' => $image,
        ]);

        if (count($data['items']) > 0) {
            foreach ($data['items'] as $key => $value) {
                IventoryItem::create([
                    'inventory_id' => $newGroup->id,
                    'item_name' => $value['item_name'],
                    'item_code' => $value['item_code'],
                    'item_cat_id' => 0,
                    'stock_qty' => $value['stock_qty'],
                    'status' => $value['status'],
                ]);    
            }
        }
        return Redirect::route('inventory');
    }
    
    /**
     * Get inventory and its items.
     */
    public function getInventories()
    {
        $data = IventoryItem::with(['inventory', 'itemCategory:id,low_stock_qty'])
                        ->orderBy('inventory_id')
                        ->get();

        $inventory = Iventory::with(['inventoryItems'])->get();

        // dd($inventory);

        return response()->json($data);
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
     * Get all item categories.
     */
    public function getAllItemCategories()
    {
        $data = ItemCategory::select(['id', 'name'])
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
     * Get inventory items.
     */
    public function getInventoryItems(string $id)
    {
        $data = Iventory::with('inventoryItems')
                        ->find($id);
        // dd($data);

        return response()->json($data);
    }

    /**
     * Update inventory item's stock.
     */
    public function updateInventoryItemStock(Request $request, string $id)
    {
        $data = $request->all();

        if (isset($id) && count($data['items']) > 0) {
            foreach ($data['items'] as $key => $value) {
                $existingItem = IventoryItem::find($value['id']);

                $existingItem->update([
                    'stock_qty' => $value['stock_qty'] + $value['add_stock_qty'],
                ]);    
            }
        }
        return Redirect::route('inventory');
    }

    /**
     * Update inventory item's stock.
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

            if (isset($item['id'])) {
                // Add unique rule while igoring self
                $rules['item_code'] = [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('iventory_items')->ignore($item['id'], 'id'),
                ];
            } else {
                // Add unique rule 
                $rules['item_code'] = 'required|string|max:255|unique:iventory_items,item_code';
            }

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
                if (isset($value['id'])) {
                    $existingItem = IventoryItem::find($value['id']);

                    $existingItem->update([
                        'item_name' => $value['item_name'],
                        'item_code' => $value['item_code'],
                        'item_cat_id' => $value['item_cat_id'],
                        'stock_qty' => $value['stock_qty'],
                        'status' => $value['status'],
                    ]);
                } else {
                    IventoryItem::create([
                        'inventory_id' => $id,
                        'item_name' => $value['item_name'],
                        'item_code' => $value['item_code'],
                        'item_cat_id' => $value['item_cat_id'],
                        'stock_qty' => $value['stock_qty'],
                        'status' => $value['status'],
                    ]);
                }
            }
        }

        return Redirect::route('inventory');
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
    }
    
    /**
     * Get recent keep history.
     */
    public function getRecentKeepHistory()
    {
        $endDate = Carbon::now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d');
        $startDate = Carbon::now()->subDays(30)->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d');

        $data = KeepHistory::whereBetween('created_at', [$startDate, $endDate])
                            ->get();
        // dd($data);

        return response()->json($data);
    }

    /**
     * Testing get data for dropdown grouped option.
     */
    public function getDropdownValue()
    {
        $data = Iventory::withWhereHas('inventoryItems')
                        ->select(['id', 'name'])
                        ->orderBy('id')
                        ->get()
                        ->map(function ($group) {
                            $group_items = $group->inventoryItems->map(function ($item) {
                                return [
                                    'text' => $item->item_name, // Assuming `name` is the property you want to use for text
                                    'value' => $item->id,  // Assuming `id` is the property you want to use for value
                                ];
                            });

                            return [
                                'group_name' => $group->name,
                                'items' => $group_items
                            ];
                        });

        return response()->json($data);
    }
}