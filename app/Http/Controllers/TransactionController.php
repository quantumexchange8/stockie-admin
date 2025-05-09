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
                'order.FilterOrderItems:id,order_id,product_id,item_qty,refund_qty,amount_before_discount,discount_amount,amount,discount_id',
                'order.FilterOrderItems.product:id,product_name,price',
                'order.FilterOrderItems.productDiscount:id,discount_id,price_before,price_after',
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
                                ->find($request->id);

            $customer = $transaction->customer;

            if ($customer) {
                // Handle deduction of customer earned points
                $oldPoint = $customer['point'];
                $afterPointDeduction = $oldPoint - $transaction->point_earned;

                $newPoint = $afterPointDeduction < 0 ? 0 : $afterPointDeduction;
                
                // Handle remove/delete customer entry rewards if the customer upranked due to the earned points from the current transaction
                $oldRank = $customer->rank;
                $isUpranked = $afterPointDeduction < $oldRank['min_amount']; 

                if ($isUpranked) {
                    $rewards = $customer->rewards->filter(function ($reward) use ($oldRank) {
                        return $reward->rankingReward['ranking_id'] === $oldRank['id'];
                    });

                    $rewards->each(function ($reward) {
                        $reward->update(['status' => 'Inactive']);
                        $reward->delete();
                    });
                }
                
                $customer->update([
                    'point' => $newPoint,
                    'total_spending' => $customer['total_spending'] - $transaction->grand_total
                ]);
            }

            // Handle restock of voided transaction's order items
            $existingOrder = $transaction->order;

            if ($existingOrder) {
                foreach ($existingOrder->orderItems as $item) {
                    if ($item['type'] === 'Normal') {
                        foreach ($item->subItems as $subItem) {
                            $inventoryItem = $subItem->productItem->inventoryItem;
                            
                            $restoredQty = $item['item_qty'] * $subItem['item_qty'];
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
                            $keepItem->update([
                                'qty' => $keepItem->qty + $item['item_qty'],
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
                                'qty' => round($item['item_qty'], 2),
                                'cm' => '0.00',
                                'keep_date' => $keepItem->updated_at,
                                'kept_balance' => $tempOrderItem->current_kept_amt + $item['item_qty'],
                                'status' => 'Keep',
                            ]);
                            
                            $tempOrderItem->increment('total_kept', $item['item_qty']);
                            $tempOrderItem->increment('current_kept_amt', $item['item_qty']);
                        }
                    }

                    $item->update(['status' => 'Cancelled']);

                    // Handle waiter commision & incentives
                    EmployeeCommission::where('id', $item->commission['id'])
                                        ->update(['amount', 0.00]);
                    
                        // Need to add handling for incentive: what if the transaction is before an existing incentive
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
            $calPoint = Setting::where('name', 'Point')->first(['point', 'value']);
            $refund_point = (int) round(((float) $request->params['refund_subtotal'] / (float) $calPoint->value) * (int) $calPoint->point);

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
                    $afterPointDeduction = $oldPoint - $refund_point;
    
                    $newPoint = $afterPointDeduction < 0 ? 0 : $afterPointDeduction;
                    
                    // Handle remove/delete customer entry rewards if the customer upranked due to the earned points from the current transaction
                    $oldRank = $customer->rank;
                    $isUpranked = $afterPointDeduction < $oldRank['min_amount']; 
    
                    if ($isUpranked) {
                        $rewards = $customer->rewards->filter(function ($reward) use ($oldRank) {
                            return $reward->rankingReward['ranking_id'] === $oldRank['id'];
                        });
    
                        $rewards->each(function ($reward) {
                            $reward->update(['status' => 'Inactive']);
                            $reward->delete();
                        });
                    }
                    
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
                                        'commission:id,order_item_id,comm_item_id', 
                                        ])->find($refund_item['id']);

                $oldRefundQty = $orderItem->refund_qty;
                $newRefundQty = $oldRefundQty + $refund_item['refund_quantities'];

                if ($orderItem['type'] === 'Normal') {
                    foreach ($orderItem->subItems as $subItem) {
                        $inventoryItem = $subItem->productItem->inventoryItem;
                        
                        $restoredQty = $refund_item['refund_quantities'] * $subItem['item_qty'];
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
                $afterPointDeduction = $oldPoint + $refund_point;

                $newPoint = $afterPointDeduction < 0 ? 0 : $afterPointDeduction;
                
                // Handle remove/delete customer entry rewards if the customer upranked due to the earned points from the current transaction
                $oldRank = $customer->rank;
                $isUpranked = $afterPointDeduction < $oldRank['min_amount']; 

                if ($isUpranked) {
                    $rewards = $customer->rewards->filter(function ($reward) use ($oldRank) {
                        return $reward->rankingReward['ranking_id'] === $oldRank['id'];
                    });

                    $rewards->each(function ($reward) {
                        $reward->update(['status' => 'Inactive']);
                        $reward->delete();
                    });
                }
                
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
