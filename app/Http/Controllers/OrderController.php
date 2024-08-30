<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderTableRequest;
use App\Models\IventoryItem;
use App\Models\OrderTable;
use App\Models\Product;
use App\Models\Waiter;
use Illuminate\Http\Request;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $zones = Zone::with(['tables', 'tables.orderTables'])
                        ->select('id', 'name')
                        ->get()
                        ->map(function ($zone) {
                            $zone->tables->map(function ($table) {
                                // Find the first order table with a status that is not 'Order Served'
                                $orderTable = $table->orderTables->first(function ($orderTable) {
                                    return $orderTable->status !== 'Order Served' && $orderTable->status !== 'Empty Seat';
                                });

                                // Filter order tables with the 'reserved' status
                                $reservations = $table->orderTables->filter(function ($orderTable) {
                                    return $orderTable->reservation === 'reserved';
                                })->sortBy(function ($reservation) {
                                    return $reservation->reservation_date;
                                })->map(function ($reservation) {
                                    // Separate reservation date and time
                                    $reservation['reservation_at'] = Carbon::parse($reservation->reservation_date)->format('d/m/Y');
                                    $reservation['reservation_time'] = Carbon::parse($reservation->reservation_date)->format('H:i');
                                    return $reservation;
                                });
    
                                // Assign the found order table as an object to the table's 'order_table' property
                                $table['order_table'] = $orderTable ?? null;

                                // Assign the filtered reservations to the 'reservations' property
                                $table['reservations'] = $reservations->isNotEmpty() ? $reservations->values() : null;
    
                                // Optionally remove the orderTables relationship if you don't need it in the response
                                unset($table->orderTables);
    
                                return $table;
                            });

                            return [
                                'text' => $zone->name,
                                'value' => $zone->id,
                                'tables' => $zone->tables
                            ];
                        });

        $waiters = Waiter::orderBy('id')->get();

        // Get the flashed messages from the session
        $message = $request->session()->get('message');

        return Inertia::render('Order/Order', [
            'message' => $message ?? [],
            'zones' => $zones,
            'waiters' => $waiters,
        ]);
    }

    /**
     * Store new order table.
     */
    public function storeOrderTable(OrderTableRequest $request)
    {
        $validatedData = $request->validated();

        OrderTable::create([
            'table_id'=>$validatedData['table_id'],
            'reservation' => $request->reservation ? 'reserved' : null,
            'pax' => $validatedData['pax'],
            'waiter_id' => $validatedData['waiter_id'],
            'status' => $validatedData['status'],
            'reservation_date' => $validatedData['reservation_date'],
            'order_id' => $validatedData['order_id']
        ]);

        $summary = $request->reservation 
            ? "Reservation has been made to '$request->table_no'."
            : "You've successfully check in customer to '$request->table_no'.";

        $message = [ 
            'severity' => 'success', 
            'summary' => $summary
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    /**
     * Update reserved order table's details.
     */
    public function updateReservation(OrderTableRequest $request, string $id)
    {
        $validatedData = $request->validated();
        
        if (!isset($id)) {
            $severity = 'error';
            $summary = "No reservation id found.";
        }

        if (isset($id)) {
            $reservation = OrderTable::find($id);
            
            if ($reservation) {
                $reservation->update([
                    'table_id'=>$validatedData['table_id'],
                    'reservation' => $request->reservation ? 'reserved' : null,
                    'pax' => $validatedData['pax'],
                    'waiter_id' => $validatedData['waiter_id'],
                    'status' => $validatedData['status'],
                    'reservation_date' => $validatedData['reservation_date'],
                    'order_id' => $validatedData['order_id']
                ]);

                $severity = 'success';
                $summary = "Changes saved";
            } else {
                $severity = 'error';
                $summary = "No reservation found.";
            }
        }

        $message = [ 
            'severity' => $severity, 
            'summary' => $summary
        ];

        return redirect()->back()->with(['message' => $message]);
    }

    /**
     * Delete reserved order table.
     */
    public function deleteReservation(Request $request, string $id)
    {
        $existingReservation = OrderTable::find($id);

        $message = [ 
            'severity' => 'error', 
            'summary' => 'No reservation found.'
        ];

        if ($existingReservation) {
            $existingReservation->delete();

            $message = [ 
                'severity' => 'success', 
                'summary' => 'Selected reservation has been deleted successfully.'
            ];
        }
        
        return Redirect::route('orders')->with(['message' => $message]);
    }

    /**
     * Get all products.
     */
    public function getAllProducts()
    {
        $products = Product::with(['productItems', 'category:id,name'])
                            ->orderBy('id')
                            ->get()
                            ->map(function ($product) {
                                $product_items = $product->productItems;
                                $minStockCount = 0;

                                if (count($product_items) > 0) {
                                    $stockCountArr = [];

                                    foreach ($product_items as $key => $value) {
                                        $inventory_item = IventoryItem::select('stock_qty')->find($value['inventory_item_id']);

                                        $stockQty = $inventory_item->stock_qty;
                                        
                                        $stockCount = (int)round($stockQty / (int)$value['qty']);
                                        array_push($stockCountArr, $stockCount);
                                    }
                                    $minStockCount = min($stockCountArr);
                                }
                                $product['stock_left'] = $minStockCount; 
                                $product['category_name'] = $product->category->name;
                                
                                unset($product->category);

                                return $product;
                            });
                            
        return response()->json($products);
    }
}
