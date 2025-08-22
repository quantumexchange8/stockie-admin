<?php

namespace App\Http\Controllers;

use App\Jobs\SubmitConsolidatedInvoiceJob;
use App\Models\ConfigMerchant;
use App\Models\ConsidateInvoice;
use App\Models\ConsolidatedInvoice;
use App\Models\MSICCodes;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\PayoutConfig;
use App\Models\State;
use App\Models\Token;
use App\Services\RunningNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function getLastMonthSales(Request $request)
    {
        
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth(); // e.g. 2025-02-01
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();     // e.g. 2025-02-29

        $dateFilter = $request->input('dateFilter');

        if ($dateFilter && ($dateFilter >= $startOfLastMonth && $dateFilter <= $endOfLastMonth)) {
            $startOfLastMonth = Carbon::createFromFormat('Y-m-d', $dateFilter)->startOfDay();
            $endOfLastMonth = Carbon::createFromFormat('Y-m-d', $dateFilter)->endOfDay();
        } else {
            $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
            $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        }

        $transactions = Payment::query()
            ->where('invoice_status', 'pending')
            ->whereBetween('receipt_end_date', [$startOfLastMonth, $endOfLastMonth])
            ->get();

        return response()->json($transactions);
    }

    public function getLastMonthRefundSales(Request $request)
    {
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth(); // e.g. 2025-02-01
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();     // e.g. 2025-02-29

        $dateFilter = $request->input('dateFilter');

        if ($dateFilter && ($dateFilter >= $startOfLastMonth && $dateFilter <= $endOfLastMonth)) {
            $startOfLastMonth = Carbon::createFromFormat('Y-m-d', $dateFilter)->startOfDay();
            $endOfLastMonth = Carbon::createFromFormat('Y-m-d', $dateFilter)->endOfDay();
        } else {
            $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
            $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        }

        $transactions = PaymentRefund::query()
            ->where('invoice_status', 'Completed')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->get();

        return response()->json($transactions);
    }

    public function getConsolidateInvoice()
    {

        $consolidateInvoice = ConsolidatedInvoice::with(['invoice_child'])->first();

        return response()->json($consolidateInvoice);
    }

    // Consolidate invoice
    public function submitConsolidate(Request $request)
    {
        // dd($request->all());
        $period = $request->input('period');
        list($startDate, $endDate) = explode(' - ', $period);

        $c_period_start = Carbon::createFromFormat('d/m/Y', trim($startDate))->startOfDay();
        $c_period_end = Carbon::createFromFormat('d/m/Y', trim($endDate))->endOfDay();

        // Check Consolidate Invoice Row
        if (!$this->checkConsolidateRow($c_period_start, $c_period_end, $request->consolidateInvoice)) {
            return redirect()->back()->withErrors('You can only submit 100 e-Invoices at a time.');
        }

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

            $receiptNo[] = ['invoice_no' => $consolidateId->receipt_no];
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
        // SubmitConsolidatedInvoiceJob::dispatch($consoParent->id, $token)->onQueue('consolidate_invoice');

        $totalPayments = $payments->count();
        $chunkSize = 50;
        $chunks = ceil($totalPayments / $chunkSize);

        $taxTotal[] = [
            "TaxAmount" => [["_"=> 0, "currencyID" => "MYR"]],
            "TaxSubtotal" => [
                [
                    "TaxableAmount" => [["_"=> 0, "currencyID" => "MYR"]],
                    "TaxAmount" => [["_"=> 0, "currencyID" => "MYR"]],
                    "TaxCategory" => [
                        [
                            "ID" => [["_" => "E"]],
                            "TaxScheme" => [
                                [
                                    "ID" => [
                                        [
                                            "_" => "OTH",
                                            "schemeID" => "UN/ECE 5153",
                                            "schemeAgencyID" => "6"
                                        ]
                                    ]
                                ]
                            ],
                            "TaxExemptionReason" => [
                                ["_" => "Exempt New Means of Transport"]
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $invoiceLines = [];
        for ($i = 0; $i < $chunks; $i++) {
            $start = $i * $chunkSize + 1;
            $end = min(($i + 1) * $chunkSize, $totalPayments);

            $chunkPayments = $payments->slice($start - 1, $chunkSize);
            $totalAmount = $chunkPayments->sum('total_amount');
            $totalSst = $chunkPayments->sum('sst_amount');
            $totalService = $chunkPayments->sum('service_tax_amount');
            $totalTax = $totalSst + $totalService;

            $invoiceLines[] = [
                "ID" => [["_" => (string)($i + 1)]],
                "InvoicedQuantity" => [["_" => 1, "unitCode" => "C62"]],
                "LineExtensionAmount" => [["_" => $totalAmount, "currencyID" => "MYR"]],
                "TaxTotal" => $taxTotal,
                "Item" => [[
                    "CommodityClassification" => [[
                        "ItemClassificationCode" => [[
                            "_" => '004', 
                            "listID" => "CLASS"
                        ]]
                    ]],
                    "Description" => [["_"=> "Receipt $start - $end"]]
                ]],
                "Price" => [[
                    "PriceAmount" => [[
                        "_" => $totalAmount,
                        "currencyID" => "MYR"
                    ]]
                ]],
                "ItemPriceExtension" => [[
                    "Amount" => [[
                        "_" => $totalAmount,
                        "currencyID" => "MYR"
                    ]]
                ]]
            ];
        }

        // STEP 1: Build raw invoice data without UBLExtensions / Signature (WIthout ublextension & signature)
        $invoiceData = [
            "_D" => "urn:oasis:names:specification:ubl:schema:xsd:Invoice-2",
            "_A" => "urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2",
            "_B" => "urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2",
            "Invoice" => [[
                "ID" => [["_" => $invoice->c_invoice_no]],
                "IssueDate" => [["_" => Carbon::now('UTC')->format('Y-m-d')]],
                "IssueTime" => [["_" => Carbon::now('UTC')->format('H:i:s')."Z"]],
                "InvoiceTypeCode" => [
                    [
                        "_" => "01",
                        "listVersionID" => "1.0"
                    ]
                ],
                "DocumentCurrencyCode" => [["_" => "MYR"]],
                "TaxCurrencyCode" => [["_" => "MYR"]],
                "AccountingSupplierParty" => [[
                    "Party" => [[
                        "PartyLegalEntity" => [[
                            "RegistrationName" => [["_" => $merchantDetail->merchant_name]]
                        ]],
                        "PartyIdentification" => [
                            [
                                "ID" => [[
                                    "_" => $merchantDetail->tin_no,
                                    "schemeID" => "TIN"
                                ]]
                            ], [
                                "ID" => [[
                                    "_" => $merchantDetail->registration_no,
                                    "schemeID" => "BRN"
                                ]]
                            ], [
                                "ID" => [[
                                    "_" => $merchantDetail->sst_registration_no ?? "NA",
                                    "schemeID" => "SST"
                                ]]
                            ], [
                                "ID" => [[
                                    "_" => "NA",
                                    "schemeID" => "TTX"
                                ]]
                            ]
                        ],
                        "IndustryClassificationCode" => [[
                            "name" => $msic->Description, 
                            "_" => $msic->Code            
                        ]],
                        "PostalAddress" => [[
                            "AddressLine" => [
                                [
                                    "Line" => [["_" => $merchantDetail->merchant_address_line1]]
                                ],
                                [
                                    "Line" => [["_" => $merchantDetail->merchant_address_line2 ?? ""]]
                                ],
                            ],
                            "CityName" => [["_" => $merchantDetail->area]],
                            "CountrySubentityCode" => [["_" => $state->Code]],
                            "Country" => [[
                                "IdentificationCode" => [[
                                    "listID" => "ISO3166-1",
                                    "listAgencyID" => "6",
                                    "_" => "MYS"
                                ]]
                            ]]
                        ]],
                        "Contact" => [
                            [
                                "Telephone" => [["_" => $merchantDetail->merchant_contact]],
                                "ElectronicMail" => [["_" => $merchantDetail->email_address]]
                            ]
                        ]
                    ]]
                ]],
                "AccountingCustomerParty" => [[
                    "Party" => [[
                        "PartyLegalEntity" => [[
                            "RegistrationName" => [["_" => "General Public"]]
                        ]],
                        "PartyIdentification" => [
                            [
                                "ID" => [[
                                    "schemeID" => "TIN",
                                    "_" => "EI00000000010"
                                ]]
                            ], 
                            [
                                "ID" => [[
                                    "schemeID" => "BRN",
                                    "_" => "NA"
                                ]]
                            ],
                            [
                                "ID" => [[
                                    "schemeID" => "SST",
                                    "_" => "NA"
                                ]]
                            ],
                            [
                                "ID" => [[
                                    "schemeID" => "TTX",
                                    "_" => "NA"
                                ]]
                            ]
                        ],
                        "PostalAddress" => [[
                            "AddressLine" => [
                                [
                                    "Line" => [["_" => "NA"]]
                                ],
                                [
                                    "Line" => [["_" => ""]]
                                ],
                                [
                                    "Line" => [["_" => ""]]
                                ],
                            ],
                            "CityName" => [["_" => $merchantDetail->area]],
                            "CountrySubentityCode" => [["_" => $state->Code]],
                            "Country" => [[
                                "IdentificationCode" => [[
                                    "_" => "MYS",
                                    "listID" => "ISO3166-1",
                                    "listAgencyID" => "6"
                                ]]
                            ]]
                        ]],
                        "Contact" => [
                            [
                                "Telephone" => [["_" => "NA"]],
                                "ElectronicMail" => [["_" => "NA"]]
                            ],
                        ]
                    ]]
                ]],
                "LegalMonetaryTotal" => [
                    [
                        "TaxExclusiveAmount" => [[
                            "_" => $invoice->c_amount,
                            "currencyID" => "MYR"  
                        ]],
                        "TaxInclusiveAmount" => [[
                            "_" => $invoice->c_total_amount,
                            "currencyID" => "MYR"
                        ]],
                        "PayableAmount" => [[
                            "_" => $invoice->c_total_amount,
                            "currencyID" => "MYR"
                        ]],
                    ]
                ],
                "TaxTotal" => [
                    [
                        "TaxAmount" => [[
                            "_" => $invoice->c_amount,
                            "currencyID" => "MYR"
                        ]],
                        "TaxSubtotal" => [[
                            "TaxableAmount" => [[
                                "_" => $invoice->c_amount,
                                "currencyID" => "MYR"
                            ]],
                            "TaxAmount" => [[
                                "_" => $invoice->c_amount,
                                "currencyID" => "MYR"
                            ]],
                            "TaxCategory" => [[
                                "ID" => [["_" => "01"]],
                                "TaxScheme" => [[
                                    "ID" => [[
                                        "_" => "OTH",
                                        "schemeID" => "UN/ECE 5153",
                                        "schemeAgencyID" => "6",
                                    ]]
                                ]],
                            ]]
                        ]]
                    ]
                ],
                "InvoiceLine" => $invoiceLines,
            ]]
        ];

        $jsonDocument = json_encode($invoiceData);
        $base64Document = base64_encode($jsonDocument);

        $documentHash = hash('sha256', $jsonDocument);

        // // STEP 2 & 3: Canonicalize
        // function canonicalizeJson($data) {
        //     if (is_array($data)) {
        //         if (array_keys($data) !== range(0, count($data) - 1)) {
        //             ksort($data); // Sort keys alphabetically
        //         }
        //         foreach ($data as &$value) {
        //             $value = canonicalizeJson($value);
        //         }
        //     }
        //     return $data;
        // }
        // // Canonicalize the data (ensure deterministic structure)
        // $canonicalData = canonicalizeJson($invoiceData);

        // // Minify & ensure no unnecessary escapes
        // $canonicalJson = json_encode($canonicalData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        // Log::debug('canonicalJson: ' . $canonicalJson);

        // // SHA-256 binary hash
        // $binaryHash = hash('sha256', $canonicalJson, true);

        // // Convert binary hash → HEX string
        // $hexHash = bin2hex($binaryHash);

        // // Convert HEX string → Base64
        // $docDigest = base64_encode(hex2bin($hexHash));
        // // Debug
        // Log::debug('canonicalJson: ' . $canonicalJson);
        // Log::debug('DocDigest: ' . $docDigest);

        // // Load PFX file from storage
        // $pfxFile = storage_path('certs/signing.pfx');
        // $p12Password = env('PRIVATE_KEY_PASS');
        // $p12Content = file_get_contents($pfxFile);

        // // Extract the certificate and private key from the .p12 file
        // $certs = [];
        // if (!openssl_pkcs12_read($p12Content, $certs, $p12Password)) {
        //     if ($error = openssl_error_string()) {
        //         Log::error('Detailed error message', ['error' => $error]);
        //     }
        //     throw new \Exception("Unable to read PFX file or incorrect password.");
        // }

        // // The private key
        // $privateKey = openssl_pkey_get_private($certs['pkey']);
        // Log::info('private key', ['private key' => $privateKey]);

        // // Data to sign
        // $dataToSign = $binaryHash;

        // // Sign the data
        // $signature = '';
        // if (!openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        //     throw new \Exception("Unable to read PFX file or incorrect password.");
        // }

        // // Convert binary signature to Base64 (Sig)
        // $sig = base64_encode($signature);
        // Log::debug('Sig: ' . $sig);

        // $signatureBase64 = $sig;

        // // 1. Clean & decode the certificate (PEM → DER)
        // $cleanCert = str_replace(
        //     ["-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----", "\r", "\n"],
        //     '',
        //     $certs['cert']
        // );
        // $derCert = base64_decode($cleanCert);

        // // 2. CertDigest (Step 5's DigestValue)
        // $certHashBase64 = base64_encode(hash('sha256', $derCert, true));

        // // 3. SigningTime (UTC ISO 8601 format)
        // $signingTime = Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z');

        // // 4. Parse issuer & serial
        // $certInfo = openssl_x509_parse($certs['cert']);
        // $issuerName = '';
        // foreach ($certInfo['issuer'] as $key => $value) {
        //     $issuerName .= strtoupper($key) . '=' . $value . ',';
        // }

        // $serialNumber = $certInfo['serialNumber']; // decimal

        // // Build the signature block according to Step 8 mapping
        // $signatureBlock = [
        //     "UBLExtensions" => [[
        //         "UBLExtension" => [[
        //             "ExtensionContent" => [[
        //                 "UBLDocumentSignatures" => [[
        //                     "SignatureInformation" => [[
        //                         "Signature" => [[
        //                             "ID" => "urn:oasis:names:specification:ubl:signature:Invoice",
        //                             "Object" => [[
        //                                 "QualifyingProperties" => [[
        //                                     "Target" => "signature",
        //                                     "SignedProperties" => [[
        //                                         "ID" => "id-xades-signed-props",
        //                                         "SignedSignatureProperties" => [[
        //                                             "SigningTime" => [[
        //                                                 "_" => $signingTime,
        //                                             ]],
        //                                             "SigningCertificate" => [[
        //                                                 "Cert" => [[
        //                                                     "CertDigest" => [[
        //                                                         "DigestMethod" => [[
        //                                                             "_" => "",
        //                                                             "Algorithm" => "http://www.w3.org/2001/04/xmlenc#sha256"
        //                                                         ]],
        //                                                         "DigestValue" => [[
        //                                                             "_" => $certHashBase64,
        //                                                         ]],
        //                                                     ]],
        //                                                     "IssuerSerial" => [[
        //                                                         "X509IssuerName" => [[
        //                                                             "_" => $issuerName,
        //                                                         ]],
        //                                                         "X509SerialNumber" => [[
        //                                                             "_" => $serialNumber,
        //                                                         ]]
        //                                                      ]]
        //                                                 ]]
        //                                             ]]
        //                                         ]]
        //                                     ]]
        //                                 ]]
        //                             ]],
        //                             "SignedInfo" => [[
        //                                 "CanonicalizationMethod" => [[
        //                                     "@Algorithm" => "http://www.w3.org/TR/2001/REC-xml-c14n-20010315"
        //                                 ]],
        //                                 "SignatureMethod" => [[
        //                                     "_" => "",
        //                                     "@Algorithm" => "http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"
        //                                 ]],
        //                                 "Reference" => [
        //                                     [
        //                                         "@Id" => "id-doc-signed-data",
        //                                         "@URI" => "",
        //                                         "DigestMethod" => [[
        //                                             "@Algorithm" => "http://www.w3.org/2001/04/xmlenc#sha256"
        //                                         ]],
        //                                         "DigestValue" => [["_"=> $docDigest]]
        //                                     ],
        //                                     [
        //                                         "@URI" => "#id-xades-signed-props",
        //                                         "DigestMethod" => [[
        //                                             "@Algorithm" => "http://www.w3.org/2001/04/xmlenc#sha256"
        //                                         ]],
        //                                         "DigestValue" => [["_"=> $docDigest]] // From your SignedProperties
        //                                     ]
        //                                 ]
        //                             ]],
        //                             "SignatureValue" => [["_"=> $sig]],
        //                             "KeyInfo" => [[
        //                                 "X509Data" => [[
        //                                     "X509Certificate" => [["_"=> $cleanCert]],
        //                                     "X509SubjectName" => [["_"=> $issuerName]],
        //                                     "X509IssuerSerial" => [[
        //                                         "X509IssuerName" => [[
        //                                             "_" => $issuerName,
        //                                         ]],
        //                                         "X509SerialNumber" => [[
        //                                             "_" => $serialNumber
        //                                         ]]
        //                                     ]],
        //                                 ]]
        //                             ]]
        //                         ]]
        //                     ]]
        //                 ]]
        //             ]]
        //         ]]
        //     ]],
        //     "Signature" => [[
        //         "ID" => [[
        //             "_" => "urn:oasis:names:specification:ubl:signature:Invoice"
        //         ]],
        //         "SignatureMethod" => [[
        //             "_" => "urn:oasis:names:specification:ubl:dsig:enveloped:xades",
        //         ]]
        //     ]]
        // ];

        // // Merge signature block at the top of Invoice
        // $invoiceData['Invoice'][0] = array_merge(
        //     $invoiceData['Invoice'][0],
        //     $signatureBlock
        // );

        // // Convert to JSON for debugging
        // $finalJson = json_encode($invoiceData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        // Log::debug("cleanCert: " . $cleanCert);
        // Log::debug("Final Invoice JSON: " . $finalJson);

        // Log::debug('final document ', ['final document' => $finalJson]);

        $document = [
            'documents' => [
                [
                    'format' => 'JSON',
                    'document' => $base64Document, // base64 of invoice JSON
                    'documentHash' => $documentHash,          // HEX hash of JSON
                    'codeNumber' => $invoice->c_invoice_no,
                ]
            ]
        ];

        Log::debug('document ', ['document' => $document]);

        if ($this->env === 'production') {
            $docsSubmitApi = 'https://preprod-api.myinvois.hasil.gov.my/api/v1.0/documentsubmissions';
        } else {
            $docsSubmitApi = 'https://preprod-api.myinvois.hasil.gov.my/api/v1.0/documentsubmissions';
        }

        $submiturl = Http::withToken($token)->post($docsSubmitApi, $document);

        if ($submiturl->successful()) {
            Log::debug('submission ', ['submission' => $submiturl]);

            // Check if the response contains 'acceptedDocuments'
            if (!empty($submiturl['acceptedDocuments'])) {
                $submission_uuid = $submiturl['submissionUid'] ?? null;
                $uuid = $submiturl['acceptedDocuments'][0]['uuid'] ?? null;
                $status = 'Submitted';
                $remark = null;
            }

            // Check if the response contains 'rejectedDocuments'
            if (!empty($submiturl['rejectedDocuments'])) {
                $submission_uuid = $submiturl['submissionUid'] ?? null;
                $uuid = $submiturl['rejectedDocuments'][0]['uuid'] ?? null;
                $status = 'rejected';
                $remark = $submiturl['rejectedDocuments'][0]['reason'] ?? null;
            }
            
            $invoice->submitted_uuid = $submission_uuid;
            $invoice->uuid = $uuid;
            $invoice->status = $status;
            $invoice->remark = $remark;
            $invoice->save();
        }

        return redirect()->back();
    }

    public function refundConsolidate(Request $request)
    {

        $period = $request->input('period');
        list($startDate, $endDate) = explode(' - ', $period);

        $c_period_start = Carbon::createFromFormat('d/m/Y', trim($startDate))->startOfDay();
        $c_period_end = Carbon::createFromFormat('d/m/Y', trim($endDate))->endOfDay();

        $payout = PayoutConfig::first();

        // 1. create consolidate parent id
        $consoParent = ConsolidatedInvoice::create([
            'c_invoice_no' => RunningNumberService::getID('consolidate'),
            'c_datetime' => Carbon::now(),
            'docs_type' => 'refund_transaction',
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

            $consolidateId = PaymentRefund::find($consolidate['id']);

            // dd($consolidateId);
            $consolidateId->update([
                'invoice_status' => 'consolidated',
                'consolidated_parent_id' => $consoParent->id,
            ]);

            $payment_amount += $consolidateId->total_amount;
            $payment_total_amount += $consolidateId->grand_total;

            $receiptNo[] = ['invoice_no' => $consolidateId->receipt_no];
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
        // SubmitConsolidatedInvoiceJob::dispatch($consoParent->id, $token)->onQueue('consolidate_invoice');

        $totalPayments = $payments->count();
        $chunkSize = 50;
        $chunks = ceil($totalPayments / $chunkSize);

        $taxTotal[] = [
            "TaxAmount" => [["_"=> 0, "currencyID" => "MYR"]],
            "TaxSubtotal" => [
                [
                    "TaxableAmount" => [["_"=> 0, "currencyID" => "MYR"]],
                    "TaxAmount" => [["_"=> 0, "currencyID" => "MYR"]],
                    "TaxCategory" => [
                        [
                            "ID" => [["_" => "E"]],
                            "TaxScheme" => [
                                [
                                    "ID" => [
                                        [
                                            "_" => "OTH",
                                            "schemeID" => "UN/ECE 5153",
                                            "schemeAgencyID" => "6"
                                        ]
                                    ]
                                ]
                            ],
                            "TaxExemptionReason" => [
                                ["_" => "Exempt New Means of Transport"]
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $invoiceLines = [];
        for ($i = 0; $i < $chunks; $i++) {
            $start = $i * $chunkSize + 1;
            $end = min(($i + 1) * $chunkSize, $totalPayments);

            // Get payments in this chunk
            $chunkPayments = $payments->slice($start - 1, $chunkSize);
            $totalAmount = $chunkPayments->sum('total_amount');
            $totalSst = $chunkPayments->sum('sst_amount');
            $totalService = $chunkPayments->sum('service_tax_amount');
            $totalTax = $totalSst + $totalService;


            // Create InvoiceLine array
            $invoiceLines[] = [
                "ID" => [["_" => (string)($i + 1)]],
                "InvoicedQuantity" => [["_" => 1, "unitCode" => "C62"]],
                "LineExtensionAmount" => [["_" => $totalAmount, "currencyID" => "MYR"]],
                "TaxTotal" => $taxTotal,
                "Item" => [[
                    "CommodityClassification" => [[
                        "ItemClassificationCode" => [[
                            "_" => '004', // consolidate
                            "listID" => "CLASS"
                        ]]
                    ]],
                    "Description" => [["_"=> "Receipt $start - $end"]]
                ]],
                "Price" => [[
                    "PriceAmount" => [[
                        "_" => $totalAmount,
                        "currencyID" => "MYR"
                    ]]
                ]],
                "ItemPriceExtension" => [[
                    "Amount" => [[
                        "_" => $totalAmount,
                        "currencyID" => "MYR"
                    ]]
                ]]
            ];
        }

        $invoiceData = [
            "_D" => "urn:oasis:names:specification:ubl:schema:xsd:Invoice-2",
            "_A" => "urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2",
            "_B" => "urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2",
            "Invoice" => [[
                "ID" => [["_" => $invoice->c_invoice_no]],
                "IssueDate" => [["_" => Carbon::now('UTC')->format('Y-m-d')]],
                "IssueTime" => [["_" => Carbon::now('UTC')->format('H:i:s')."Z"]],
                "InvoiceTypeCode" => [
                    [
                        "_" => "04",
                        "listVersionID" => "1.0"
                    ]
                ],
                "DocumentCurrencyCode" => [["_" => "MYR"]],
                "TaxCurrencyCode" => [["_" => "MYR"]],
                "AccountingSupplierParty" => [[
                    "Party" => [[
                        "PartyLegalEntity" => [[
                            "RegistrationName" => [["_" => $merchantDetail->merchant_name]]
                        ]],
                        "PartyIdentification" => [
                            [
                                "ID" => [[
                                    "_" => $merchantDetail->tin_no,
                                    "schemeID" => "TIN"
                                ]]
                            ], [
                                "ID" => [[
                                    "_" => $merchantDetail->registration_no,
                                    "schemeID" => "BRN"
                                ]]
                            ], [
                                "ID" => [[
                                    "_" => $merchantDetail->sst_registration_no ?? "NA",
                                    "schemeID" => "SST"
                                ]]
                            ], [
                                "ID" => [[
                                    "_" => "NA",
                                    "schemeID" => "TTX"
                                ]]
                            ]
                        ],
                        "IndustryClassificationCode" => [[
                            "name" => $msic->Description, // Business Activity Description
                            "_" => $msic->Code            // MSIC Code (if applicable)
                        ]],
                        "PostalAddress" => [[
                            "AddressLine" => [
                                [
                                    "Line" => [["_" => $merchantDetail->merchant_address_line1]]
                                ],
                                [
                                    "Line" => [["_" => $merchantDetail->merchant_address_line2 ?? ""]]
                                ],
                            ],
                            "CityName" => [["_" => $merchantDetail->area]],
                            "CountrySubentityCode" => [["_" => $state->Code]],
                            "Country" => [[
                                "IdentificationCode" => [[
                                    "listID" => "ISO3166-1",
                                    "listAgencyID" => "6",
                                    "_" => "MYS"
                                ]]
                            ]]
                        ]],
                        "Contact" => [
                            [
                                "Telephone" => [["_" => $merchantDetail->merchant_contact]],
                                "ElectronicMail" => [["_" => $merchantDetail->email_address]]
                            ]
                        ]
                    ]]
                ]],
                "AccountingCustomerParty" => [[
                    "Party" => [[
                        "PartyLegalEntity" => [[
                            "RegistrationName" => [["_" => "General Public"]]
                        ]],
                        "PartyIdentification" => [
                            [
                                "ID" => [[
                                    "schemeID" => "TIN",
                                    "_" => "EI00000000010"
                                ]]
                            ], 
                            [
                                "ID" => [[
                                    "schemeID" => "BRN",
                                    "_" => "NA"
                                ]]
                            ],
                            [
                                "ID" => [[
                                    "schemeID" => "SST",
                                    "_" => "NA"
                                ]]
                            ],
                            [
                                "ID" => [[
                                    "schemeID" => "TTX",
                                    "_" => "NA"
                                ]]
                            ]
                        ],
                        "PostalAddress" => [[
                            "AddressLine" => [
                                [
                                    "Line" => [["_" => "NA"]]
                                ],
                                [
                                    "Line" => [["_" => ""]]
                                ],
                                [
                                    "Line" => [["_" => ""]]
                                ],
                            ],
                            "CityName" => [["_" => $merchantDetail->area]],
                            "CountrySubentityCode" => [["_" => $state->Code]],
                            "Country" => [[
                                "IdentificationCode" => [[
                                    "_" => "MYS",
                                    "listID" => "ISO3166-1",
                                    "listAgencyID" => "6"
                                ]]
                            ]]
                        ]],
                        "Contact" => [
                            [
                                "Telephone" => [["_" => "NA"]],
                                "ElectronicMail" => [["_" => "NA"]]
                            ],
                        ]
                    ]]
                ]],
                "LegalMonetaryTotal" => [
                    [
                        // Required
                        "TaxExclusiveAmount" => [[
                            "_" => $invoice->c_amount,
                            "currencyID" => "MYR"  // Currency Code
                        ]],
                        "TaxInclusiveAmount" => [[
                            "_" => $invoice->c_total_amount,
                            "currencyID" => "MYR"  // Currency Code
                        ]],
                        "PayableAmount" => [[
                            "_" => $invoice->c_total_amount,
                            "currencyID" => "MYR"  // Currency Code
                        ]],
                    ]
                ],
                "TaxTotal" => [
                    [
                        "TaxAmount" => [[
                            "_" => $invoice->c_amount,
                            "currencyID" => "MYR"
                        ]],
                        "TaxSubtotal" => [[
                            "TaxableAmount" => [[
                                "_" => $invoice->c_amount,
                                "currencyID" => "MYR"
                            ]],
                            "TaxAmount" => [[
                                "_" => $invoice->c_amount,
                                "currencyID" => "MYR"
                            ]],
                            "TaxCategory" => [[
                                "ID" => [["_" => "01"]],
                                "TaxScheme" => [[
                                    "ID" => [[
                                        "_" => "OTH",
                                        "schemeID" => "UN/ECE 5153",
                                        "schemeAgencyID" => "6",
                                    ]]
                                ]],
                            ]]
                        ]]
                    ]
                ],
                "InvoiceLine" => $invoiceLines,
            ]]
        ];

        $jsonDocument = json_encode($invoiceData);
        $base64Document = base64_encode($jsonDocument);

        $documentHash = hash('sha256', $jsonDocument);

        $document = [
            'documents' => [
                [
                    'format' => 'JSON',
                    'document' => $base64Document,
                    'documentHash' => $documentHash,
                    'codeNumber' => $invoice->c_invoice_no,
                ]
            ]
        ];

        if ($this->env === 'production') {
            $docsSubmitApi = 'https://preprod-api.myinvois.hasil.gov.my/api/v1.0/documentsubmissions';
        } else {
            $docsSubmitApi = 'https://preprod-api.myinvois.hasil.gov.my/api/v1.0/documentsubmissions';
        }

        $submiturl = Http::withToken($token)->post($docsSubmitApi, $document);

        if ($submiturl->successful()) {
            Log::debug('submission ', ['submission' => $submiturl]);

            // Check if the response contains 'acceptedDocuments'
            if (!empty($submiturl['acceptedDocuments'])) {
                $submission_uuid = $submiturl['submissionUid'] ?? null;
                $uuid = $submiturl['acceptedDocuments'][0]['uuid'] ?? null;
                $status = 'submitted';
                $remark = null;
            }

            // Check if the response contains 'rejectedDocuments'
            if (!empty($submiturl['rejectedDocuments'])) {
                $submission_uuid = $submiturl['submissionUid'] ?? null;
                $uuid = $submiturl['rejectedDocuments'][0]['uuid'] ?? null;
                $status = 'rejected';
                $remark = $submiturl['rejectedDocuments'][0]['reason'] ?? null;
            }
            
            $invoice->submitted_uuid = $submission_uuid;
            $invoice->uuid = $uuid;
            $invoice->status = $status;
            $invoice->remark = $remark;
            $invoice->save();
        }

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

    public function getAllSaleInvoice(Request $request)
    {

        // Get all payments excluding 'pending'
        // $payments = Payment::where('invoice_status', '!=', 'pending')->get();

        // Prepare grouped structure
        // $grouped = [];

        // foreach ($payments as $payment) {
        //     if ($payment->consolidated_parent_id) {
                
        //         $grouped[$payment->consolidated_parent_id]['consolidated_parent_id'] = ConsolidatedInvoice::where('id', $payment->consolidated_parent_id)
        //                 ->with(['invoice_child'])
        //                 ->first();
                
        //     } else {
        //         $grouped['standalone'] = $payment;
        //     }
        // }

        // Reindex the array to return a clean structure
        // $transactions = array_values($grouped);



        $query = ConsolidatedInvoice::query();

        if ($request->dateFilter) {
            $startDate = Carbon::parse($request->dateFilter[0])->timezone('Asia/Kuala_Lumpur')->startOfDay();
            $endDate = Carbon::parse($request->dateFilter[1] ?? $request->dateFilter[0])->timezone('Asia/Kuala_Lumpur')->endOfDay();
    
            $query->where(function($subQuery) use ($startDate, $endDate) {
                $subQuery->whereDate('c_datetime', '>=', $startDate)
                        ->whereDate('c_datetime', '<=', $endDate);
            });
        }
        
        $transactions = $query->with(['invoice_child', 'invoice_no'])->latest()->get();

        return response()->json($transactions);
    }

    public function cancelSubmission(Request $request)
    {

        $consolidateInvoice = ConsolidatedInvoice::find($request->invoice_id);
        $now = Carbon::now();
        
        if ($consolidateInvoice->docs_type === 'sales_transaction') {

            // check invoice is expired
            if ($now >= $consolidateInvoice->cancel_expired_at) {
                return redirect()->back()->withErrors('Invoice has expired and cannot be cancelled.');
            }

            $submitUrl = Http::put('https://preprod-api.myinvois.hasil.gov.my/api/v1.0/documents/state/' . $consolidateInvoice->uuid . '/state', [
                'status' => 'cancelled',
                'reason' => $request->reason,
            ]);

            if ($submitUrl->successful()) {
                $consolidateInvoice->status = 'cancelled';
                $consolidateInvoice->save();
            } else {
                Log::error('Failed to cancel submission', [
                    'status' => $submitUrl->status(),
                    'error' => $submitUrl->body()
                ]);
            }
        }

        return redirect()->back();
    }

    public function downloadInvoice($id)
    {
        $invoice = ConsolidatedInvoice::with(['invoice_child' => function ($query) {
            $query->where('invoice_status', 'consolidated')
                ->orderBy('receipt_no', 'asc');
        }])->find($id);
        $merchant = ConfigMerchant::with(['msicCode', 'classificationCode'])->find('1');

        $children = $invoice->invoice_child;

        $ranges = [];
        $start = null;
        $prev = null;
        $batchTotal = 0;
        $batchStartNo = null;

        foreach ($children as $child) {
            $current = $child->receipt_no;
            $grandTotal = $child->grand_total;

            // Extract the numeric part from receipt_no (e.g. RCPT00000001 → 1)
            preg_match('/\d+$/', $current, $matches);
            $number = (int)$matches[0];

            if ($start === null) {
                // First element
                $start = $current;
                $prev = $number;
                $batchStartNo = $number;
                $batchTotal = $grandTotal;
            } elseif ($number === $prev + 1) {
                // Still consecutive → extend range
                $prev = $number;
                $batchTotal += $grandTotal;
            } else {
                // Break → save the range
                $ranges[] = [
                    "receipt_batch" => ($start === "RCPT" . str_pad($prev, strlen($matches[0]), "0", STR_PAD_LEFT))
                        ? $start
                        : $start . ' - ' . "RCPT" . str_pad($prev, strlen($matches[0]), "0", STR_PAD_LEFT),
                    "sum_amount" => $batchTotal
                ];

                // Start new range
                $start = $current;
                $prev = $number;
                $batchStartNo = $number;
                $batchTotal = $grandTotal;
            }
        }

         if ($start !== null) {
            $ranges[] = [
                "receipt_batch" => ($start === "RCPT" . str_pad($prev, strlen($matches[0]), "0", STR_PAD_LEFT))
                    ? $start
                    : $start . ' - ' . "RCPT" . str_pad($prev, strlen($matches[0]), "0", STR_PAD_LEFT),
                "sum_amount" => $batchTotal
            ];
        }

        $prodUrl = $this->env === 'production'
            ? 'https://preprod.myinvois.hasil.gov.my/'
            : 'https://preprod.myinvois.hasil.gov.my/';

        $generateQr = $prodUrl . $invoice->invoice_uuid . '/share/' . $invoice->longId;

        return Pdf::loadView('invoices.pdf', compact('invoice', 'merchant', 'ranges', 'generateQr'))
            ->setPaper('a4')   // optional
            ->stream("invoice-{$invoice->invoice_no}.pdf");

    }

    protected function checkConsolidateRow($start, $end, $consolidateInvoice)
    {
        $totalInvoice = count($consolidateInvoice);

        // 1. Rule: 100 maximum e-Invoices per submission
        if ($totalInvoice > 100) {
            return redirect()->back()->withErrors('You can only submit 100 e-Invoices at a time.');
        }

        // 2. Rule: 300 KB per e-Invoice
        foreach ($consolidateInvoice as $invoice) {
            $encodedInvoice = base64_encode(json_encode($invoice, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (strlen($encodedInvoice) > 300 * 1024) {
                return redirect()->back()->withErrors('One of the invoices exceeds 300 KB limit.');
            }
        }

        // 3. Rule: 5 MB total submission size
        $submissionPayload = [
            'documents' => $consolidateInvoice
        ];
        $submissionJson  = json_encode($submissionPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($submissionJson) > 5 * 1024 * 1024) {
            return redirect()->back()->withErrors('The submission exceeds 5 MB limit.');
        }

        // NOTE: strlen($submissionJson) in KB

        return true; // all good

    }
}
