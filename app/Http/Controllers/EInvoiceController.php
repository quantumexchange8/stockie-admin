<?php

namespace App\Http\Controllers;

use App\Jobs\SubmitConsolidatedInvoiceJob;
use App\Models\ConfigMerchant;
use App\Models\ConsidateInvoice;
use App\Models\ConsolidatedInvoice;
use App\Models\MSICCodes;
use App\Models\Payment;
use App\Models\PayoutConfig;
use App\Models\State;
use App\Models\Token;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class EInvoiceController extends Controller
{
    protected $env;

    public function __construct()
    {
        $this->env = env('APP_ENV');
    }
    //
    public function einvoice()
    {


        return Inertia::render('Einvoice/Einvoice');
    }

    public function getLastMonthSales()
    {

        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth(); // e.g. 2025-02-01
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();     // e.g. 2025-02-29

        $transactions = Payment::query()
            ->where('invoice_status', 'pending')
            ->whereBetween('receipt_end_date', [$startOfLastMonth, $endOfLastMonth])
            ->get();

        return response()->json($transactions);
    }

    public function getConsolidateInvoice()
    {

        $consolidateInvoice = ConsolidatedInvoice::with(['invoice_child'])->first();

        return response()->json($consolidateInvoice);
    }

    public function submitConsolidate(Request $request)
    {
        // dd($request->all());
        $period = $request->input('period');
        list($startDate, $endDate) = explode(' - ', $period);

        $c_period_start = Carbon::createFromFormat('d/m/Y', trim($startDate))->startOfDay();
        $c_period_end = Carbon::createFromFormat('d/m/Y', trim($endDate))->endOfDay();

        $payout = PayoutConfig::first();

        // 1. create consolidate parent id
        $consoParent = ConsolidatedInvoice::create([
            'c_invoice_no' => RunningNumberService::getID('consolidate'),
            'c_datetime' => Carbon::now(),
            'docs_type' => 'sales_transaction',
            'c_period_start' => $c_period_start, // "2025-03-01 00:00:00"
            'c_period_end' => $c_period_end, // "2025-03-31 23:59:59"
            'cancel_expired_at' => Carbon::now()->addDays(3),
        ]);

        $payment_amount = 0;
        $payment_total_amount = 0;

        // store all receipt no
        $receiptNo = [];
        
        // 2. update all invoice status
        foreach ($request->consolidateInvoice as $consolidate) {

            $consolidateId = Payment::find($consolidate['id']);

            // dd($consolidateId);
            $consolidateId->update([
                'invoice_status' => 'consolidated',
                'consolidated_parent_id' => $consoParent->id,
            ]);

            $payment_amount += $consolidateId->total_amount;
            $payment_total_amount += $consolidateId->grand_total;

            $receiptNo[] = $consolidateId->receipt_no;
        }

        $params = [
            'receiptNo' => $receiptNo,
        ];

        $updateConsoCt = Http::withHeaders([
            'CT-API-KEY' => $payout->api_key,
            'MERCHANT-ID' => $payout->merchant_id,
        ])->post($payout->url . 'api/update-consolidate-invoice', $params);

        Log::info('updateConsoCt', [
            'status' => $updateConsoCt->status()
        ]);

        $consoParent->c_amount = $payment_amount;
        $consoParent->c_total_amount = $payment_total_amount;
        $consoParent->save();

        $invoice = ConsolidatedInvoice::where('id', $consoParent->id)->with(['invoice_child'])->first();
        $payments = $invoice->invoice_child;
        $merchantDetail = ConfigMerchant::first();
        $msic = MSICCodes::find($merchantDetail->msic_id);
        $state = State::where('State', $merchantDetail->state)->first();
        $checkToken = Token::latest()->first();
        $now = Carbon::now();

        if (!$invoice) {
            Log::error("Invoice ID {$consoParent->id} not found.");
            return;
        }

        $token = $this->fetchToken($merchantDetail, $checkToken);
        if (!$token) {
            Log::error('Failed to fetch token');
            return;
        }

        // queue job here
        SubmitConsolidatedInvoiceJob::dispatch($consoParent->id, $token)->onQueue('consolidate_invoice');

        return redirect()->back();
    }

    private function fetchToken($merchantDetail, $checkToken)
    {
        if (!$checkToken || Carbon::now() >= $checkToken->expired_at) {
            $access_token_api = $this->env === 'production'
                ? 'https://preprod-api.myinvois.hasil.gov.my/connect/token' 
                : 'https://preprod-api.myinvois.hasil.gov.my/connect/token';

            $response = Http::asForm()->post($access_token_api, [
                'client_id' => $merchantDetail->irbm_client_id, 
                'client_secret' => $merchantDetail->irbm_client_key,
                'grant_type' => 'client_credentials',
                'scope' => 'InvoicingAPI',
            ]);

            if ($response->successful()) {
                Token::where('merchant_id', $merchantDetail->id)->delete();

                return Token::create([
                    'merchant_id' => $merchantDetail->id,
                    'token' => $response['access_token'],
                    'expired_at' => Carbon::now()->addHour(),
                ])->token;
            } else {
                Log::error('Failed to get access token', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                return null;
            }
        }

        return $checkToken->token;
    }
}
