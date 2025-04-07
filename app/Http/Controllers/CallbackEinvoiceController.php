<?php

namespace App\Http\Controllers;

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
            "transaction_number" => $data['invoice_no'],
            "uuid" => $data['invoice_uuid'],
            "status" => $data["status"],
            "submission_date" => $data["submission_date"],
        ];

        $payoutConfig = PayoutConfig::first();
        $transaction = Payment::where('receipt_no', $result['transaction_number'])->first();
        $eCode = md5($result['transaction_number'] . 1 . $payoutConfig->api_key);

        if ($transaction) {

            if ($eCode === $result['token']) {

                $transaction->update([
                    'invoice_status' => $result['status'],
                    'submitted_uuid' => $result['uuid'],
                    'submission_date' => $result['submission_date'],
                ]);

                Log::info('Transaction updated ReceiptNo.: ', $result['transaction_number']);

            } else {
                Log::info('eCode not match');
            }

        } else {
            Log::info('Transaction not found');
        }

        return response()->json();
    }
}
