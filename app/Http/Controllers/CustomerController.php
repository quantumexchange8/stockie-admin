<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\IventoryItem;
use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\Order;
use App\Models\Point;
use App\Models\Ranking;
use App\Models\RankingReward;
use Carbon\Carbon;
use Hash;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Log;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with([
                                    'ranking',
                                    'keepItems' => function ($query) {
                                        $query->where('status', 'Keep')
                                            ->with(['orderItemSubitem.productItem.product', 'waiter']);
                                    }
                                ])->withCount([
                                            'keepItems' => function ($query) {
                                                $query->where('status', 'Keep')->where('qty', '>', 0);
                                    }
                                ])->get();

        $customers = $customers->map(function ($customer) {
            $activeKeepItems = $customer->keepItems
                ->where('qty', '>', 0)
                ->sortByDesc('created_at')
                ->map(function ($keepItem) {
                    $itemName = $keepItem->orderItemSubitem->productItem->product->product_name ?? 'N/A';
                        return [
                        'id' => $keepItem->id,
                        'item' => $itemName,
                        'qty' => $keepItem->qty,
                        'created_at' => $keepItem->created_at->format('d/m/Y, h:i A'),
                        'expired_from' => Carbon::parse($keepItem->expired_from)->format('d/m/Y'),
                        'expired_to' => Carbon::parse($keepItem->expired_to)->format('d/m/Y'),
                        'waiter_name' => $keepItem->waiter->full_name ?? 'N/A',
                    ];
                })->toArray(); 

                return [
                "id" => $customer->id,
                "tier" => $customer->ranking->name ?? 'N/A', 
                "name" => $customer->full_name,
                "email" => $customer->email,
                "phone" => $customer->phone,
                "points" => $customer->point,
                "keep" => $customer->keep_items_count,
                "keep_items" => $activeKeepItems,
                "created_at" => $customer->created_at->format("d/m/Y"),
            ];
        });
        $message = $request->session()->get('message');

        return Inertia::render("Customer/Customer", [
            'customers' => $customers,
            'message'=> $message ?? [],
        ]);
    }

    public function deleteCustomer(CustomerRequest $request, string $id)
    {
        $customer = Customer::find($id);
        $user = Auth::user();
        if($customer && (Hash::check($request->password, $user->password)) ) {
            $customer->delete();
            $message = [
                'severity' => 'success',
                'summary' => 'Selected customer has been deleted successfully.',
        ];
        }
        else{
            $message = [
                'severity' => 'error',
                'summary' => 'Error deleting customer.',
            ];
        }

        return Redirect::route('customer')->with(['message'=> $message]);
    }

    public function getFilteredCustomers(Request $request)
    {
        $queries = Customer::query();
    
        if (isset($request['checkedFilters'])) {
            $queries->with('ranking')->where(function (Builder $query) use ($request) {
                // Apply filter for 'tier'
                if (isset($request['checkedFilters']['tier']) && count($request['checkedFilters']['tier']) > 0) {
                    $query->whereHas('ranking', function ($rankQuery) use ($request) {
                        $rankQuery->whereIn('name', $request['checkedFilters']['tier']);
                    });
                }
    
                // Apply filter for 'points'
                if (isset($request['checkedFilters']['points']) && count($request['checkedFilters']['points']) === 2) {
                    $query->whereBetween('point', array_map('intval', $request['checkedFilters']['points']));
                }
            });
    
            // Apply filter for 'keepItems'
            if (isset($request['checkedFilters']['keepItems']) && count($request['checkedFilters']['keepItems']) > 0) {
                $queries->withCount('keepItems')->having('keep_items_count', '>', 0);
            }
        }
    
        $queries->with('ranking')->withCount('keepItems');

        $results = $queries->get();

        $customers = $results->map(function ($query) {

                $activeKeepItems = $query->keepItems
                ->where('qty', '>', 0)
                ->sortByDesc('created_at')
                ->map(function ($keepItem) {
                    $itemName = $keepItem->orderItemSubitem->productItem->product->product_name ?? 'N/A';
                        return [
                        'id' => $keepItem->id,
                        'item' => $itemName,
                        'qty' => $keepItem->qty,
                        'created_at' => Carbon::parse($keepItem->created_at)->format('d/m/Y, h:i A'),
                        'expired_from' => Carbon::parse($keepItem->expired_from)->format('d/m/Y'),
                        'expired_to' => Carbon::parse($keepItem->expired_to)->format('d/m/Y'),
                        'waiter_name' => $keepItem->waiter->full_name ?? 'N/A',
                    ];
                })->toArray(); 

            return [
                "id" => $query->id,
                "tier" => $query->ranking->name ?? 'N/A',
                "name" => $query->full_name,
                "email" => $query->email,
                "phone" => $query->phone,
                "points" => $query->point,
                "keep_items" => $activeKeepItems,
                "keep" => $query->keep_items_count,
                "created_at" => Carbon::parse($query->created_at)->format("d/m/Y"),
            ];
        });
        return response()->json($customers);
    }

    public function returnItem (Request $request) {
        $selectedItem = KeepItem::find($request->id);
        if ($selectedItem !== null) {
            $selectedItem->update([
                'qty' => $selectedItem->qty - $request->qty,
            ]);

            $selectedItem = KeepHistory::create([
                'keep_item_id' => $selectedItem->id,
                'qty' => ($request->initial_qty - $request->qty),
                'cm' => $selectedItem->cm,
                'keep_date' => $selectedItem->expired_from,
                'status' => 'returned',
            ]);

            $message = [
                'severity' => 'success',
                'summary' => 'Item has been returned to the customer.'
            ];
    }else{
        $message = [
            'severity' => 'danger',
            'summary' => 'Error occurred.'
        ];
    };

        return redirect()->back()->with(['message' => $message]);
        
    }

    public function keepHistory (string $id) {

        $customer = Customer::find($id);
        $allKeepHistories = [];

        foreach ($customer->keepItems as $keepItem) {
            $keepHistories = $keepItem->keepHistories;

            foreach ($keepHistories as $history) {
                $allKeepHistories[] = [
                    'id' => $history->id,
                    'item_name' => $keepItem->orderItemSubitem->productItem->product->product_name,
                    'keep_item_id' => $keepItem->id,
                    'qty'=> $history->qty,
                    'status' => $history->status,
                    'created_at' => Carbon::parse($history->created_at)->format('d/m/Y, h:i A'),
                    'expired_date' => Carbon::parse($keepItem->expired_to)->format('d/m/Y'),
                    'waiter_name' => $keepItem->waiter->full_name ?? null,
                ];
            }
        };

        // Log::info(array_values($allKeepHistories));
        $sortedKeepHistories = collect($allKeepHistories)->sortByDesc('created_at')->values()->all();
        return response()->json($sortedKeepHistories);
    }

    public function customerPoints () {
        $redeemables = Point::select('id','name', 'point')->get();
        
        return response()->json($redeemables);
    }

    public function redeemHistory(string $id)
    {
        $orders = Order::with('pointHistories') 
            ->where('customer_id', $id)
            ->orderBy('created_at','desc')
            ->get();

        $earned = $orders->map(function ($order) {
            $positivePoints = $order->orderItems->sum('point_earned');
            $pointNames = $order->orderItems->where('type', 'Redemption')
                ->map(function ($item) {
                    return [
                        "id" => $item->point->id,
                        "name" => $item->point->name ?? 'N/A',
                        "qty" => $item->item_qty,
                        "redeemed" => ($item->point_redeemed * $item->item_qty),
                    ];
                })->unique('name'); 

            $subject = $positivePoints > 0 ? $order->order_no : null;
            return [
                'created_at' => Carbon::parse($order->created_at)->format('d/m/Y, h:i A'),
                'subject' => $subject,
                'earned' => $positivePoints,
                'used' => $pointNames,
            ];
        });
        return response()->json($earned);
    }

    public function tierRewards(string $id)
    {
        $customerTier = Customer::where('id', $id)->pluck('ranking')->first();
        $tierName = Ranking::where('id', $customerTier)->pluck('name')->first();
        $rankingRewards = RankingReward::where('ranking_id', $customerTier)->get();
    
        $response = [];
    
        foreach ($rankingRewards as $reward) {
            $formattedDate = Carbon::parse($reward->valid_period_to)->format('d/m/Y');
            if (Carbon::parse($reward->valid_period_to)->isPast()) {
                $rewardData = [
                    'reward_type' => $reward->reward_type,
                    'status' => 'expired',
                    'valid_period_to' => $formattedDate,
                    'reward' => null,
                ];
            } else {
                switch ($reward->reward_type) {
                    case 'Discount (Amount)':
                        $calculatedReward = $reward->discount;
                        break;
                    case 'Discount (Percentage)':
                        $calculatedReward = $reward->discount * 100;
                        break;
                    case 'Bonus Point':
                        $calculatedReward = $reward->bonus_point;
                        break;
                    case 'Free Item':
                        $rewardNo = $reward->free_item;
                        $calculatedReward = IventoryItem::where('id', $rewardNo)->pluck('item_name')->first();
                        break;
                    default:
                        $calculatedReward = null;
                }
                $formattedDate = Carbon::parse($reward->valid_period_to)->format('d/m/Y');
                $rewardData = [
                    'reward_type' => $reward->reward_type,
                    'status' => $reward->min_purchase,
                    'valid_period_to' => $formattedDate,
                    'reward' => $calculatedReward,
                ];
            }
    
            $response[] = $rewardData;
        }
     
        $result = [
            'tier_name' => $tierName,
            'rewards' => $response,
        ];
    
        return response()->json($result);
    }
    
}
