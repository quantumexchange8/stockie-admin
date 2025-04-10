<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PayoutConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallbackEinvoiceController extends Controller
{
    public function updateClientEinvoice(Request $request)
    {
        
        $data = $request->all();
        Log::debug('data from ', $data);

        $result = [
            "token" => $data['eCode'],
            "receipt_no" => $data['invoice_no'],
            "submitted_uuid" => $data['invoice_uuid'],
            "uuid" => $data['invoice_uuid'],
            "status" => $data["status"],
            "submission_date" => $data["submission_date"],
        ];

        $payoutConfig = PayoutConfig::first();
        $transaction = Payment::where('receipt_no', $result['receipt_no'])->first();
        $eCode = md5($result['receipt_no'] . 1 . $payoutConfig->api_key);

        if ($transaction) {

            if ($eCode === $result['token']) {

                $transaction->update([
                    'invoice_status' => $result['status'],
                    'submitted_uuid' => $result['submitted_uuid'],
                    'uuid' => $result['uuid'],
                    'submission_date' => $result['submission_date'],
                ]);

                Log::info('Transaction updated ReceiptNo.: ', ['receipt' => $result['receipt_no']]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Invoice updated successfully',
                ], 200);

            } else {
                Log::info('eCode not match');
            }

        } else {
            Log::info('Transaction not found');
        }

        return response()->json();
    }
}
