<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\EmployeeCommission;
use App\Models\IventoryItem;
use App\Models\KeepHistory;
use App\Models\KeepItem;
use App\Models\OrderItem;
use App\Models\OrderItemSubitem;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\PointHistory;
use App\Models\RefundDetail;
use App\Models\RunningNumber;
use App\Models\SaleHistory;
use App\Models\Setting;
use App\Models\StockHistory;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    //

    public function transactionListing()
    {

        return Inertia::render('TransactionListing/TransactionListing');
    }

    public function getSalesTransaction(Request $request)
    {

        $transactions = Payment::query()
                                ->whereNot('status', 'pending')
                                ->with([
                                    'customer', 
                                    'order:id,amount,total_amount', 
                                    'order.FilterOrderItems:id,order_id,product_id,item_qty,refund_qty,amount_before_discount,discount_amount,amount,discount_id,status',
                                    'order.FilterOrderItems.product:id,product_name,price',
                                    'order.FilterOrderItems.productDiscount:id,discount_id,price_before,price_after',
                                    'order.FilterOrderItems.subItems.keepItems.oldestKeepHistory' => function ($query) {
                                        $query->where('status', 'Keep');
                                    },
                                    'voucher:id,reward_type,discount',
                                    'paymentMethods:id,payment_id,payment_method,amount,created_at'
                                ]);

        if ($request->dateFilter) {
            $startDate = Carbon::parse($request->dateFilter[0])->timezone('Asia/Kuala_Lumpur')->startOfDay();
            $endDate = Carbon::parse($request->dateFilter[1] ?? $request->dateFilter[0])->timezone('Asia/Kuala_Lumpur')->endOfDay();
    
            $transactions->where(function($query) use ($startDate, $endDate) {
                $query->whereDate('receipt_end_date', '>=', $startDate)
                        ->whereDate('receipt_end_date', '<=', $endDate);
            });
        }

        $transactions = $transactions->latest()->get();

        $transactions->each(function ($transaction) {
            if ($transaction->customer) {
                $transaction->customer->profile_photo = $transaction->customer->getFirstMediaUrl('customer');
            }

            $transaction->order['FilterOrderItems']->each(function ($orderItem) {
                // $orderItem['total_keep_subitem_qty'] = $orderItem->subItems->reduce(function ($totalKeptSubItems, $subItem) {
                //     $keptSubItems = $subItem->keepItems->reduce(function ($totalKeepQty, $keepItem) {
                //         return $totalKeepQty + (int)$keepItem->oldestKeepHistory['qty'];
                //     }, 0);

                //     return $totalKeptSubItems + $keptSubItems;
                // }, 0);
                
                $minimumQty = [];
                $totalKeepSubitemQty = 0;

                $orderItem->subItems->each(function ($subItem) use (&$totalKeepSubitemQty, &$minimumQty, $orderItem) {
                    $subItem['subitem_keep_count'] = $subItem->keepItems->reduce(function ($totalKeepQty, $keepItem) {
                        return $totalKeepQty + (int)$keepItem->oldestKeepHistory['qty'];
                    }, 0);
                    
                    $totalKeepSubitemQty += $subItem['subitem_keep_count'];

                    $untouchedQty = floor(($subItem['item_qty'] * $orderItem['item_qty'] - $subItem['subitem_keep_count']) / $subItem['item_qty']);
                    // dd($orderItem, $subItem, $untouchedQty);
                    array_push($minimumQty, $untouchedQty);

                    return $subItem;
                });

                // dd($orderItem, $minimumQty);
                $orderItem['total_keep_subitem_qty'] = $totalKeepSubitemQty;
                $orderItem['remaining_qty'] = min($minimumQty) - $orderItem['refund_qty'];
                
                return $orderItem;
            });

            $transaction->order['total_kept_item_qty'] = $transaction->order['FilterOrderItems']->reduce(function ($totalKeptItems, $orderItem) {
                return $totalKeptItems + $orderItem['total_keep_subitem_qty'];
            }, 0);
        });

        return response()->json($transactions);
    }

    public function getRefundTransaction(Request $request)
    {

        $transactions = PaymentRefund::query()
                                    ->with([
                                        'customer', 
                                        'refund_details',
                                        'refund_details.FilterOrderItems:id,order_id,product_id,item_qty,refund_qty,amount_before_discount,discount_amount,amount',
                                        'refund_details.FilterOrderItems.product:id,product_name,price',
                                        'product:id,product_name,price',
                                    ]);

        if ($request->dateFilter) {
            $startDate = Carbon::parse($request->dateFilter[0])->timezone('Asia/Kuala_Lumpur')->startOfDay();
            $endDate = Carbon::parse($request->dateFilter[1] ?? $request->dateFilter[0])->timezone('Asia/Kuala_Lumpur')->endOfDay();
    
            $transactions->where(function($query) use ($startDate, $endDate) {
                $query->whereDate('created_at', '>=', $startDate)
                        ->whereDate('created_at', '<=', $endDate);
            });
        }

        $transactions = $transactions->latest()->get();

        $transactions->each(function ($transaction) {
            if ($transaction->customer) {
                $transaction->customer->profile_photo = $transaction->customer->getFirstMediaUrl('customer');
            }
        });

        return response()->json($transactions);
    }

    public function voidTransaction(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $transaction = Payment::with([
                                    'shiftTransaction',
                                    'customer.rank',
                                    'customer.rewards' => fn ($query) => $query->where('status', 'Active'),
                                    'customer.rewards.rankingReward' => fn ($query) => $query->where('status', 'Active'),
                                    'order.orderItems.subItems.productItem.inventoryItem:id,item_name,stock_qty,low_stock_qty,inventory_id,current_kept_amt', 
                                    'order.orderItems.commission:id,order_item_id,comm_item_id', 
                                ])
                                ->find($request->params['id']);

            $customer = $transaction->customer;

            if ($customer) {
                // Handle deduction of customer earned points
                $oldPoint = $customer['point'];
                $newPoint = $oldPoint - $transaction->point_earned;

                $usableHistories = PointHistory::where(function ($query) {
                                                        $query->where(function ($subQuery) {
                                                                $subQuery->where([
                                                                            ['type', 'Earned'],
                                                                            ['expire_balance', '>', 0],
                                                                        ])
                                                                        ->whereNotNull('expired_at')
                                                                        ->whereDate('expired_at', '>', now());
                                                            })
                                                            ->orWhere(function ($subQuery) {
                                                                $subQuery->where([
                                                                            ['type', 'Adjusted'],
                                                                            ['expire_balance', '>', 0]
                                                                        ])
                                                                        ->whereNotNull('expired_at')
                                                                        ->whereColumn('new_balance', '>', 'old_balance')
                                                                        ->whereDate('expired_at', '>', now());
                                                            });
                                                    })
                                                    ->where('customer_id', $customer->id)
                                                    ->orderBy('expired_at', 'asc') // earliest expiry first
                                                    ->get();

                $remainingPoints = $transaction->point_earned;

                foreach ($usableHistories as $history) {
                    if ($remainingPoints <= 0) break;

                    $deductAmount = min($remainingPoints, $history->expire_balance);
                    $history->expire_balance -= $deductAmount;
                    $history->save();

                    $remainingPoints -= $deductAmount;
                }
                
                // Handle remove/delete customer entry rewards if the customer upranked due to the earned points from the current transaction
                // $oldRank = $customer->rank;
                // $isUpranked = $afterPointDeduction < $oldRank['min_amount']; 

                // if ($isUpranked) {
                //     $rewards = $customer->rewards->filter(function ($reward) use ($oldRank) {
                //         return $reward->rankingReward['ranking_id'] === $oldRank['id'];
                //     });

                //     $rewards->each(function ($reward) {
                //         $reward->update(['status' => 'Inactive']);
                //         $reward->delete();
                //     });
                // }
                
                $customer->update([
                    'point' => $newPoint,
                    'total_spending' => $customer['total_spending'] - $transaction->grand_total
                ]);
            }

            // Handle restock of voided transaction's order items
            $existingOrder = $transaction->order;

            if ($existingOrder) {
                foreach ($existingOrder['orderItems'] as $item) {
                    if ($item['type'] === 'Normal') {
                        foreach ($item->subItems as $subItem) {
                            $totalQtyReturned = 0;
                            $totalInitialKeptQty = 0;

                            $keepItems = KeepItem::with([
                                                    'orderItemSubitem.productItem.inventoryItem',
                                                    'oldestKeepHistory' => function ($query) {
                                                        $query->where('status', 'Keep');
                                                    },
                                                ])
                                                ->where('order_item_subitem_id', $subItem->id)
                                                ->get(); 

                            if ($keepItems && $keepItems->count() > 0) {
                                $keepItems->each(function ($ki) use ($totalQtyReturned, $totalInitialKeptQty) {
                                    $inventoryItem = $ki->orderItemSubitem->productItem->inventoryItem;
    
                                    activity()->useLog('delete-kept-item')
                                                ->performedOn($ki)
                                                ->event('deleted')
                                                ->withProperties([
                                                    'edited_by' => auth()->user()->full_name,
                                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                                    'item_name' => $inventoryItem->item_name,
                                                ])
                                                ->log(":properties.item_name is deleted.");
                                                
                                    KeepHistory::create([
                                        'keep_item_id' => $ki->id,
                                        'qty' => $ki->qty,
                                        'cm' => number_format((float) $ki->cm, 2, '.', ''),
                                        'keep_date' => $ki->created_at,
                                        'kept_balance' => $ki->qty > $ki->cm ? $inventoryItem->current_kept_amt - $ki->qty : $inventoryItem->current_kept_amt,
                                        'remark' => 'void',
                                        'user_id' => auth()->user()->id,
                                        'kept_from_table' => $ki->kept_from_table,
                                        'redeemed_to_table' => 'void',
                                        'status' => 'Deleted',
                                    ]);

                                    if ($ki->qty > $ki->cm) {
                                        $inventoryItem->decrement('total_kept', $ki->qty);
                                        $inventoryItem->decrement('current_kept_amt', $ki->qty);
                                    }

                                    $ki->update([
                                        'status' => 'Deleted'
                                    ]);

                                    $totalQtyReturned += $ki->qty;
                                    $totalInitialKeptQty += (int)$ki->oldestKeepHistory['qty'];
                                });

                            }

                            $inventoryItem = $subItem->productItem->inventoryItem;
                            
                            $restoredQty = $keepItems->count() > 0 
                                    ? ($item['item_qty'] * $subItem['item_qty'] - $totalInitialKeptQty) +  $totalQtyReturned
                                    : $item['item_qty'] * $subItem['item_qty'];
                                    
                            $oldStockQty = $inventoryItem->stock_qty;
                            $newStockQty = $oldStockQty + $restoredQty;

                            // Update inventory with restored stock
                            $newStatus = match(true) {
                                $newStockQty === 0 => 'Out of stock',
                                $newStockQty <= $inventoryItem->low_stock_qty => 'Low in stock',
                                default => 'In stock'
                            };

                            $inventoryItem->update([
                                'stock_qty' => $newStockQty,
                                'status' => $newStatus
                            ]);
                            $inventoryItem->refresh();

                            if ($restoredQty > 0) {
                                StockHistory::create([
                                    'inventory_id' => $inventoryItem->inventory_id,
                                    'inventory_item' => $inventoryItem->item_name,
                                    'old_stock' => $oldStockQty,
                                    'in' => $restoredQty,
                                    'out' => 0,
                                    'current_stock' => $inventoryItem->stock_qty,
                                    'kept_balance' => $inventoryItem->current_kept_amt,
                                ]);
                            }
                        }
                    }

                    if ($item['type'] === 'Keep') {
                        $keepItem = KeepItem::with('oldestKeepHistory')->find($item['keep_id']);
                        $keepType = $keepItem->oldestKeepHistory->qty > $keepItem->oldestKeepHistory->cm ? 'qty' : 'cm';

                        if ($keepType === 'qty') {
                            $totalQtyReturned = 0;
                            $totalInitialKeptQty = 0;

                            $keepItems = KeepItem::with([
                                                    'orderItemSubitem.productItem.inventoryItem',
                                                    'oldestKeepHistory' => function ($query) {
                                                        $query->where('status', 'Keep');
                                                    },
                                                ])
                                                ->where('order_item_subitem_id', $item->subItems[0]['id'])
                                                ->get(); 

                            if ($keepItems && $keepItems->count() > 0) {
                                $keepItems->each(function ($ki) use ($totalQtyReturned, $totalInitialKeptQty) {
                                    $inventoryItem = $ki->orderItemSubitem->productItem->inventoryItem;
    
                                    activity()->useLog('delete-kept-item')
                                                ->performedOn($ki)
                                                ->event('deleted')
                                                ->withProperties([
                                                    'edited_by' => auth()->user()->full_name,
                                                    'image' => auth()->user()->getFirstMediaUrl('user'),
                                                    'item_name' => $inventoryItem->item_name,
                                                ])
                                                ->log(":properties.item_name is deleted.");
                                                
                                    KeepHistory::create([
                                        'keep_item_id' => $ki->id,
                                        'qty' => $ki->qty,
                                        'cm' => number_format((float) $ki->cm, 2, '.', ''),
                                        'keep_date' => $ki->created_at,
                                        'kept_balance' => $ki->qty > $ki->cm ? $inventoryItem->current_kept_amt - $ki->qty : $inventoryItem->current_kept_amt,
                                        'remark' => 'void',
                                        'user_id' => auth()->user()->id,
                                        'kept_from_table' => $ki->kept_from_table,
                                        'redeemed_to_table' => 'void',
                                        'status' => 'Deleted',
                                    ]);
                                    
                                    if ($ki->qty > $ki->cm) {
                                        $inventoryItem->decrement('total_kept', $ki->qty);
                                        $inventoryItem->decrement('current_kept_amt', $ki->qty);
                                    }

                                    $ki->update([
                                        'status' => 'Deleted'
                                    ]);

                                    $totalQtyReturned += $ki->qty;
                                    $totalInitialKeptQty += (int)$ki->oldestKeepHistory['qty'];
                                });

                            }

                            $returnKeepQty = $keepItems->count() > 0 
                                    ? ($item['item_qty'] - $totalInitialKeptQty) + $totalQtyReturned 
                                    : $item['item_qty'];
                            
                            $keepItem->update([
                                'qty' => $keepItem->qty + $returnKeepQty,
                                'status' => 'Keep',
                            ]);

                            $keepItem->save();
                            $keepItem->refresh();

                            $associatedSubItem = OrderItemSubitem::where('id', $keepItem['order_item_subitem_id'])
                                                                    ->with(['productItem:id,inventory_item_id'])
                                                                    ->first();

                            $tempInventoryItem = $associatedSubItem->productItem->inventory_item_id;
                            $tempOrderItem = IventoryItem::find($tempInventoryItem);
                            
                            KeepHistory::create([
                                'keep_item_id' => $item['keep_id'],
                                'order_item_id' => $item['order_item_id'],
                                'qty' => round($returnKeepQty, 2),
                                'cm' => '0.00',
                                'keep_date' => $keepItem->updated_at,
                                'kept_balance' => $tempOrderItem->current_kept_amt + $returnKeepQty,
                                'kept_from_table' => $keepItem->kept_from_table,
                                'status' => 'Keep',
                            ]);
                            
                            $tempOrderItem->increment('total_kept', $returnKeepQty);
                            $tempOrderItem->increment('current_kept_amt', $returnKeepQty);
                        }
                    }

                    $item->update(['status' => 'Cancelled']);

                    if ($item->commission) {
                        // Handle waiter commision & incentives
                        EmployeeCommission::where('id', $item->commission['id'])
                                            ->update(['amount', 0.00]);
                        
                            // Need to add handling for incentive: what if the transaction is before an existing incentive
                    }
                }

                $existingOrder->update(['status' => 'Order Cancelled']);
            }

            // // Handle sale histories
            // SaleHistory::where('order_id', $existingOrder['id'])->delete();

            // Handle modification to the associated shift transaction details
            $shift = $transaction->shiftTransaction;
            $shift->update([
                'total_void' => $shift['total_void'] + $transaction->grand_total,
                'net_sales' => $shift['net_sales'] - $transaction->grand_total
            ]);

            $transaction->status = 'Voided';
            $transaction->save();

            return redirect()->back();
        });
    }

    public function refundTransaction(Request $request)
    {
        return DB::transaction(function () use ($request) {

            // dd($request->all());
            $calPoint = Setting::where('name', 'Point')->first(['point', 'value']);
            $refund_point = round(((float) $request->params['refund_subtotal'] / (float) $calPoint->value) * $calPoint->point, 2);

            if ($request->params['customer_id'] !== 'Guest') {
                $customer = Customer::with([
                                        'rank',
                                        'rewards' => fn ($query) => $query->where('status', 'Active'),
                                        'rewards.rankingReward' => fn ($query) => $query->where('status', 'Active'),
                                    ])
                                    ->find($request->params['customer_id']);

                if ($customer) {
                    // Handle deduction of customer earned points
                    $oldPoint = $customer->point;
                    $newPoint = $oldPoint - $refund_point;

                    $usableHistories = PointHistory::where(function ($query) {
                                                            $query->where(function ($subQuery) {
                                                                    $subQuery->where([
                                                                                ['type', 'Earned'],
                                                                                ['expire_balance', '>', 0],
                                                                            ])
                                                                            ->whereNotNull('expired_at')
                                                                            ->whereDate('expired_at', '>', now());
                                                                })
                                                                ->orWhere(function ($subQuery) {
                                                                    $subQuery->where([
                                                                                ['type', 'Adjusted'],
                                                                                ['expire_balance', '>', 0]
                                                                            ])
                                                                            ->whereNotNull('expired_at')
                                                                            ->whereColumn('new_balance', '>', 'old_balance')
                                                                            ->whereDate('expired_at', '>', now());
                                                                });
                                                        })
                                                        ->where('customer_id', $customer->id)
                                                        ->orderBy('expired_at', 'asc') // earliest expiry first
                                                        ->get();

                    $remainingPoints = $refund_point;

                    foreach ($usableHistories as $history) {
                        if ($remainingPoints <= 0) break;

                        $deductAmount = min($remainingPoints, $history->expire_balance);
                        $history->expire_balance -= $deductAmount;
                        $history->save();

                        $remainingPoints -= $deductAmount;
                    }
    
                    // $newPoint = $afterPointDeduction < 0 ? 0 : $afterPointDeduction;
                    
                    // // Handle remove/delete customer entry rewards if the customer upranked due to the earned points from the current transaction
                    // $oldRank = $customer->rank;
                    // $isUpranked = $afterPointDeduction < $oldRank['min_amount']; 
    
                    // if ($isUpranked) {
                    //     $rewards = $customer->rewards->filter(function ($reward) use ($oldRank) {
                    //         return $reward->rankingReward['ranking_id'] === $oldRank['id'];
                    //     });
    
                    //     $rewards->each(function ($reward) {
                    //         $reward->update(['status' => 'Inactive']);
                    //         $reward->delete();
                    //     });
                    // }
                    
                    $customer->update([
                        'point' => $newPoint,
                        'total_spending' => $customer['total_spending'] - $request->params['refund_subtotal']
                    ]);
                }
            }

            $paymentRefund = PaymentRefund::create([
                'payment_id' => $request->params['id'],
                'customer_id' => $request->params['customer_id'],
                'refund_no' => RunningNumberService::getID('refund'),
                'subtotal_refund_amount' => $request->params['refund_subtotal'],
                'refund_sst' => $request->params['refund_sst'] ?? null,
                'refund_service_tax' => $request->params['refund_service_tax'] ?? null,
                'refund_rounding' => $request->params['refund_rounding'] ?? null,
                'total_refund_amount' => $request->params['refund_total'],
                'refund_point' => $refund_point,
                'refund_method' => $request->params['form']['refund_method'],
                'others_remark' => $request->params['form']['refund_others'] ?? null,
                'refund_remark' => $request->params['form']['refund_reason'],
                'status' => 'Completed'
            ]);

            foreach($request->params['form']['refund_item'] as $refund_item) {
                $orderItem = OrderItem::with([
                                        'product.commItem.configComms',
                                        'subItems.productItem.inventoryItem:id,item_name,stock_qty,low_stock_qty,inventory_id,current_kept_amt', 
                                        'subItems.keepItems',
                                        'commission:id,order_item_id,comm_item_id', 
                                        ])->find($refund_item['id']);

                $oldRefundQty = $orderItem->refund_qty;
                $newRefundQty = $oldRefundQty + $refund_item['refund_quantities'];

                if ($orderItem['type'] === 'Normal') {
                    foreach ($orderItem->subItems as $subItem) {
                        $restoredQty = $refund_item['refund_quantities'] * $subItem['item_qty'];
                        $inventoryItem = $subItem->productItem->inventoryItem;
                        $oldStockQty = $inventoryItem->stock_qty;
                        $newStockQty = $oldStockQty + $restoredQty;

                        // Update inventory with restored stock
                        $newStatus = match(true) {
                            $newStockQty === 0 => 'Out of stock',
                            $newStockQty <= $inventoryItem->low_stock_qty => 'Low in stock',
                            default => 'In stock'
                        };

                        $inventoryItem->update([
                            'stock_qty' => $newStockQty,
                            'status' => $newStatus
                        ]);
                        $inventoryItem->refresh();

                        if ($restoredQty > 0) {
                            StockHistory::create([
                                'inventory_id' => $inventoryItem->inventory_id,
                                'inventory_item' => $inventoryItem->item_name,
                                'old_stock' => $oldStockQty,
                                'in' => $restoredQty,
                                'out' => 0,
                                'current_stock' => $inventoryItem->stock_qty,
                                'kept_balance' => $inventoryItem->current_kept_amt,
                            ]);
                        }
                    }

                    // $orderItem->update(['status' => 'Cancelled']);
    
                    // Handle waiter commision & incentives
                    $configCommItem = $orderItem->product->commItem;
            
                    if ($configCommItem) {
                        $commissionType = $configCommItem->configComms->comm_type;
                        $commissionRate = $configCommItem->configComms->rate;
                        $commissionAmount = $commissionType === 'Fixed amount per sold product' 
                                ? $commissionRate * ($orderItem->item_qty - $newRefundQty)
                                : $orderItem->product->price * ($orderItem->item_qty - $newRefundQty) * ($commissionRate / 100);

                        $waiterCommission = EmployeeCommission::where('id', $orderItem->commission['id'])->first();
                        $waiterCommission->update(['amount', $commissionAmount]);
                    }
                    
                        // Need to add handling for incentive: what if the transaction is before an existing incentive
                }

                $orderItem->update(['refund_qty' => $newRefundQty]);
                $orderItem->save();

                RefundDetail::create([
                    'payment_refund_id' => $paymentRefund->id,
                    'order_item_id' => $refund_item['id'],
                    'refund_qty' => $refund_item['refund_quantities'],
                    'refund_amount' => $refund_item['refund_amount'],
                    'product_id' => $refund_item['product_id'],
                ]);
            }
            
            $transaction = Payment::with('shiftTransaction')->find($request->params['id']);
            $shift = $transaction->shiftTransaction;

            $shift->update([
                'total_refund' => $shift['total_refund'] + $paymentRefund['total_refund_amount'],
                'net_sales' => $shift['net_sales'] - $paymentRefund['total_refund_amount']
            ]);

            return redirect()->back();
        });
    }

    public function voidRefundTransaction(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $paymentRefund = PaymentRefund::with(['refund_details', 'payment.shiftTransaction'])->find($request['params']['id']);

            $calPoint = Setting::where('name', 'Point')->first(['point', 'value']);
            $refund_point = (int) round(((float) $paymentRefund->subtotal_refund_amount / (float) $calPoint->value) * (int) $calPoint->point);

            $customer = Customer::find($paymentRefund->customer_id);
            if ($customer) {
                $customer->point += $refund_point;
                $customer->save();
            }

            if ($customer) {
                // Handle deduction of customer earned points
                $oldPoint = $customer->point;
                $newPoint = $oldPoint + $refund_point;

                // $newPoint = $afterPointDeduction < 0 ? 0 : $afterPointDeduction;
                
                // // Handle remove/delete customer entry rewards if the customer upranked due to the earned points from the current transaction
                // $oldRank = $customer->rank;
                // $isUpranked = $afterPointDeduction < $oldRank['min_amount']; 

                // if ($isUpranked) {
                //     $rewards = $customer->rewards->filter(function ($reward) use ($oldRank) {
                //         return $reward->rankingReward['ranking_id'] === $oldRank['id'];
                //     });

                //     $rewards->each(function ($reward) {
                //         $reward->update(['status' => 'Inactive']);
                //         $reward->delete();
                //     });
                // }
                
                $customer->update([
                    'point' => $newPoint,
                    'total_spending' => $customer['total_spending'] - $request->params['refund_subtotal']
                ]);
            }

            $refundDetails = $paymentRefund->refund_details;

            $refundDetails->each(function ($refund_item) {
                $orderItem = OrderItem::with([
                                            'product.commItem.configComms',
                                            'subItems.productItem.inventoryItem:id,item_name,stock_qty,low_stock_qty,inventory_id,current_kept_amt', 
                                            'commission:id,order_item_id,comm_item_id', 
                                        ])
                                        ->find($refund_item['order_item_id']);
                                        
                $oldRefundQty = $orderItem->refund_qty;
                $newRefundQty = $oldRefundQty - $refund_item['refund_quantities'];

                if ($orderItem['type'] === 'Normal') {
                    foreach ($orderItem->subItems as $subItem) {
                        $inventoryItem = $subItem->productItem->inventoryItem;
                        
                        $stockToBeSold = $refund_item['refund_quantities'] * $subItem['item_qty'];
                        $oldStockQty = $inventoryItem->stock_qty;
                        $newStockQty = $oldStockQty - $stockToBeSold;

                        // Update inventory with restored stock
                        $newStatus = match(true) {
                            $newStockQty === 0 => 'Out of stock',
                            $newStockQty <= $inventoryItem->low_stock_qty => 'Low in stock',
                            default => 'In stock'
                        };

                        $inventoryItem->update([
                            'stock_qty' => $newStockQty,
                            'status' => $newStatus
                        ]);
                        $inventoryItem->refresh();

                        StockHistory::create([
                            'inventory_id' => $inventoryItem->inventory_id,
                            'inventory_item' => $inventoryItem->item_name,
                            'old_stock' => $oldStockQty,
                            'in' => 0,
                            'out' => $stockToBeSold,
                            'current_stock' => $inventoryItem->stock_qty,
                            'kept_balance' => $inventoryItem->current_kept_amt,
                        ]);
                    }

                    // $orderItem->update(['status' => 'Cancelled']);
    
                    // Handle waiter commision & incentives
                    $configCommItem = $orderItem->product->commItem;
            
                    if ($configCommItem) {
                        $commissionType = $configCommItem->configComms->comm_type;
                        $commissionRate = $configCommItem->configComms->rate;
                        $commissionAmount = $commissionType === 'Fixed amount per sold product' 
                                ? $commissionRate * ($orderItem->item_qty - $newRefundQty)
                                : $orderItem->product->price * ($orderItem->item_qty - $newRefundQty) * ($commissionRate / 100);

                        $waiterCommission = EmployeeCommission::where('id', $orderItem->commission['id'])->first();
                        $waiterCommission->update(['amount', $commissionAmount]);
                    }
                    
                        // Need to add handling for incentive: what if the transaction is before an existing incentive
                }

                $orderItem->update(['refund_qty' => $newRefundQty]);
                $orderItem->save();
            });
            
            $shift = $paymentRefund->payment->shiftTransaction;
            $shift->update([
                'total_refund' => $shift['total_refund'] - $paymentRefund['total_refund_amount'],
                'net_sales' => $shift['net_sales'] + $paymentRefund['total_refund_amount']
            ]);

            $paymentRefund->status = 'Voided';
            $paymentRefund->save();

            return redirect()->back();
        });
    }
}
