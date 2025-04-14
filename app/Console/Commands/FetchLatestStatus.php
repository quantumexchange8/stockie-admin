<?php

namespace App\Console\Commands;

use App\Models\ConsolidatedInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchLatestStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:latest-status-submission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fetch latest status of invoice submission';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        
        $consolidateInvoice = ConsolidatedInvoice::where('status', 'Submitted')->get();

        foreach ($consolidateInvoice as $invoice) {

            $response = Http::get('https://preprod-api.myinvois.hasil.gov.my/api/v1.0/' . $invoice->uuid . '/details');

            if ($response->successful()) {

                if ($response['status'] === 'Valid') {
                    $invoice->update([
                        'status' => $response['status'],
                        'longId' => $response['longId'],
                    ]);
                } else {
                    $invoice->update([
                        'status' => $response['status'],
                        'remark' => $response['validationResults']['validationSteps'],
                    ]);
                }
            }

        }

    }
}
