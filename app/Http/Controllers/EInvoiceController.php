<?php

namespace App\Http\Controllers;

use App\Jobs\SubmitConsolidatedInvoiceJob;
use App\Models\ConfigMerchant;
use App\Models\ConsidateInvoice;
use App\Models\ConsolidatedInvoice;
use App\Models\MSICCodes;
use App\Models\Payment;
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
        }

        $consoParent->c_amount = $payment_amount;
        $consoParent->c_total_amount = $payment_total_amount;
        $consoParent->save();

        // queue job here
        // SubmitConsolidatedInvoiceJob::dispatch($consoParent->id)->onQueue('consolidate_invoice');

        $invoice = ConsolidatedInvoice::where('id', $consoParent->id)->with(['invoice_child'])->first();
        $payments = $invoice->invoice_child;
        $merchantDetail = ConfigMerchant::first();
        $msic = MSICCodes::find($merchantDetail->msic_id);
        $state = State::where('State', $merchantDetail->state)->first();
        $checkToken = Token::first();
        $now = Carbon::now();

        if (!$invoice) {
            Log::error("Invoice ID {$consoParent->id} not found.");
            return;
        }

        if ($checkToken && $now->lessThan($checkToken->expired_at)) {
            return $checkToken->token;
        } else {
            return $this->fetchToken($merchantDetail);
        }

        $totalPayments = $payments->count();
        $chunkSize = 50;
        $chunks = ceil($totalPayments / $chunkSize); // Calculate how many groups

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
                        "listVersionID" => "1.1"
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
                                    "_" => 'C29802509040',
                                    "schemeID" => "TIN"
                                ]]
                            ], [
                                "ID" => [[
                                    "_" => '202201017212',
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
                            "CityName" => [["_" => "Kuala Lumpur"]],
                            "CountrySubentityCode" => [["_" => "="]],
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
                "UBLExtensions" => [[
                    "UBLExtension" => [[
                        "ExtensionURI" => [[
                            "_" => "urn:oasis:names:specification:ubl:dsig:enveloped:xades"
                        ]],
                        "ExtensionContent" => [[
                            "UBLDocumentSignatures" => [[
                                "SignatureInformation" => [[
                                    "ID" => [[
                                        "_" => "urn:oasis:names:specification:ubl:signature:1",
                                    ]],
                                    "ReferencedSignatureID" => [[
                                        "_" => "urn:oasis:names:specification:ubl:signature:Invoice"
                                    ]],
                                    "Signature" => [[
                                        "Id" => "signature",
                                        "Object" => [[
                                            "QualifyingProperties" => [[
                                                "Target" => "signature",
                                                "SignedProperties" => [[
                                                    "Id" => "id-xades-signed-props",
                                                    "SignedSignatureProperties" => [[
                                                        "SigningTime" => [[
                                                            "_" => "2024-07-23T15:14:54Z"
                                                        ]],
                                                        "SigningCertificate" => [[
                                                            "Cert" => [[
                                                                "CertDigest" => [[
                                                                    "DigestMethod" => [[
                                                                        "_" => "",
                                                                        "Algorithm" => "http://www.w3.org/2001/04/xmlenc#sha256",
                                                                    ]],
                                                                    "DigestValue" => [[
                                                                        "_" => "KKBSTyiPKGkGl1AFqcPziKCEIDYGtnYUTQN4ukO7G40",
                                                                    ]],
                                                                ]],
                                                                "IssuerSerial" => [[
                                                                    "X509IssuerName" => [[
                                                                        "_" => "CN=Trial LHDNM Sub CA V1, OU=Terms of use at http://www.posdigicert.com.my, O=LHDNM, C=MY"
                                                                    ]],
                                                                    "X509SerialNumber" => [[
                                                                        "_" => "162880276254639189035871514749820882117"
                                                                    ]],
                                                                ]],
                                                            ]]
                                                        ]],
                                                    ]]
                                                ]]
                                            ]]
                                        ]],
                                        "KeyInfo" => [[
                                            "X509Certificate" => [[
                                                "_" => "MIIFlDCCA3ygAwIBAgIQeomZorO+0AwmW2BRdWJMxTANBgkqhkiG9w0BAQsFADB1MQswCQYDVQQGEwJNWTEOMAwGA1UEChMFTEhETk0xNjA0BgNVBAsTLVRlcm1zIG9mIHVzZSBhdCBodHRwOi8vd3d3LnBvc2RpZ2ljZXJ0LmNvbS5teTEeMBwGA1UEAxMVVHJpYWwgTEhETk0gU3ViIENBIFYxMB4XDTI0MDYwNjAyNTIzNloXDTI0MDkwNjAyNTIzNlowgZwxCzAJBgNVBAYTAk1ZMQ4wDAYDVQQKEwVEdW1teTEVMBMGA1UEYRMMQzI5NzAyNjM1MDYwMRswGQYDVQQLExJUZXN0IFVuaXQgZUludm9pY2UxDjAMBgNVBAMTBUR1bW15MRIwEAYDVQQFEwlEMTIzNDU2NzgxJTAjBgkqhkiG9w0BCQEWFmFuYXMuYUBmZ3Zob2xkaW5ncy5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQChvfOzAofnU60xFO7NcmF2WRi+dgor1D7ccISgRVfZC30Fdxnt1S6ZNf78Lbrz8TbWMicS8plh/pHy96OJvEBplsAgcZTd6WvaMUB2oInC86D3YShlthR6EzhwXgBmg/g9xprwlRqXMT2p4+K8zmyJZ9pIb8Y+tQNjm/uYNudtwGVm8A4hEhlRHbgfUXRzT19QZml6V2Ea0wQI8VyWWa8phCIkBD2w4F8jG4eP5/0XSQkTfBHHf+GV/YDJx5KiiYfmB1nGfwoPHix6Gey+wRjIq87on8Dm5+8ei8/bOhcuuSlpxgwphAP3rZrNbRN9LNVLSQ5md41asoBHfaDIVPVpAgMBAAGjgfcwgfQwHwYDVR0lBBgwFgYIKwYBBQUHAwQGCisGAQQBgjcKAwwwEQYDVR0OBAoECEDwms66hrpiMFMGA1UdIARMMEowSAYJKwYBBAGDikUBMDswOQYIKwYBBQUHAgEWLWh0dHBzOi8vd3d3LnBvc2RpZ2ljZXJ0LmNvbS5teS9yZXBvc2l0b3J5L2NwczATBgNVHSMEDDAKgAhNf9lrtsUI0DAOBgNVHQ8BAf8EBAMCBkAwRAYDVR0fBD0wOzA5oDegNYYzaHR0cDovL3RyaWFsY3JsLnBvc2RpZ2ljZXJ0LmNvbS5teS9UcmlhbExIRE5NVjEuY3JsMA0GCSqGSIb3DQEBCwUAA4ICAQBwptnIb1OA8NNVotgVIjOnpQtowew87Y0EBWAnVhOsMDlWXD/s+BL7vIEbX/BYa0TjakQ7qo4riSHyUkQ+X+pNsPEqolC4uFOp0pDsIdjsNB+WG15itnghkI99c6YZmbXcSFw9E160c7vG25gIL6zBPculHx5+laE59YkmDLdxx27e0TltUbFmuq3diYBOOf7NswFcDXCo+kXOwFfgmpbzYS0qfSoh3eZZtVHg0r6uga1UsMGb90+IRsk4st99EOVENvo0290lWhPBVK2G34+2TzbbYnVkoxnq6uDMw3cRpXX/oSfya+tyF51kT3iXvpmQ9OMF3wMlfKwCS7BZB2+iRja/1WHkAP7QW7/+0zRBcGQzY7AYsdZUllwYapsLEtbZBrTiH12X4XnZjny9rLfQLzJsFGT7Q+e02GiCsBrK7ZHNTindLRnJYAo4U2at5+SjqBiXSmz0DG+juOyFkwiWyD0xeheg4tMMO2pZ7clQzKflYnvFTEFnt+d+tvVwNjTboxfVxEv2qWF6qcMJeMvXwKTXuwVI2iUqmJSzJbUY+w3OeG7fvrhUfMJPM9XZBOp7CEI1QHfHrtyjlKNhYzG3IgHcfAZUURO16gFmWgzAZLkJSmCIxaIty/EmvG5N3ZePolBOa7lNEH/eSBMGAQteH+Twtiu0Y2xSwmmsxnfJyw=="
                                            ]],
                                            "X509SubjectName" => [[
                                                "_" => "CN=Trial LHDNM Sub CA V1, OU=Terms of use at http://www.posdigicert.com.my, O=LHDNM, C=MY",
                                            ]],
                                            "X509IssuerSerial" => [[
                                                "X509IssuerName" => [[
                                                    "_" => "CN=Trial LHDNM Sub CA V1, OU=Terms of use at http://www.posdigicert.com.my, O=LHDNM, C=MY",
                                                ]],
                                                "X509SerialNumber" => [[
                                                    "_" => "162880276254639189035871514749820882117",
                                                ]],
                                            ]]
                                        ]],
                                        "SignatureValue" => [[
                                            "_" => "QTvntg4opuS7ZYWmly/iAO2OnLVJcKylYuF+QJKZdx9BkFVglmVuFtEtwoqgNsbsKaaEDinTSUAVStRJs2tiU1Jdryd4hoZ/Hc5TAvFnThpauVOLsc3j07cUB1+zhNjENmFeI9yzTGjr8XfNi4mNPspnhFAT4QGbRpxkWiIsKj762p3dhCwUNAuNLjunVaosYQ5lvSzGt4B9TF/1xJ7Z6kdcJTmBeltTWErSRA2EOMzWsGWGZVvyPLnXfnlIBQItTvARXveafxFdS1iw91g7mSEEYeqEviI0b4FUmkwH8ed0boFc6EHl1VF+2uVxBtHeKf31FqTQl/6/pF4Qgpn6Hg=="
                                        ]],
                                        "SignedInfo" => [[
                                            "SignatureMethod" => [[
                                                "_" => "",
                                                "Algorithm" => "http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"
                                            ]],
                                            "Reference" => [
                                                [
                                                    "Type" => "http://uri.etsi.org/01903/v1.3.2#SignedProperties",
                                                    "URI" => "#id-xades-signed-props",
                                                    "DigestMethod" => [
                                                        [
                                                            "_" => "",
                                                            "Algorithm" => "http://www.w3.org/2001/04/xmlenc#sha256"
                                                        ]
                                                    ],
                                                    "DigestValue" => [
                                                        [
                                                            "_" => "Rzuzz+70GSnGBF1YxhHnjSzFpQ1MW4vyX/Q9bTHkE2c="
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    "Type" => "",
                                                    "URI" => "",
                                                    "DigestMethod" => [
                                                        [
                                                            "_" => "",
                                                            "Algorithm" => "http://www.w3.org/2001/04/xmlenc#sha256"
                                                        ]
                                                    ],
                                                    "DigestValue" => [
                                                        [
                                                            "_" => "vMs/IdnS7isftqrBDr4F1LK/CkvBkW5Gb3Wn6OVzAxo="
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]]
                                    ]]
                                ]]
                            ]]
                        ]],
                    ]]
                ]],
                "Signature" => [[
                    "ID" => [[
                        "_" => "urn:oasis:names:specification:ubl:signature:Invoice",
                    ]],
                    "SignatureMethod" => [[
                        "_" => "urn:oasis:names:specification:ubl:dsig:enveloped:xades"
                    ]]
                ]],
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
                    'codeNumber' => $consoParent->c_invoice_no,
                ]
            ]
        ];

        if ($this->env === 'production') {
            $docsSubmitApi = 'https://preprod-api.myinvois.hasil.gov.my/api/v1.0/documentsubmissions';
        } else {
            $docsSubmitApi = 'https://preprod-api.myinvois.hasil.gov.my/api/v1.0/documentsubmissions';
        }
        
        $submiturl = Http::withToken($checkToken->token)->post($docsSubmitApi, $document);
        if ($submiturl->successful()) {
            Log::debug('submission ', $submiturl);

            $uuid = $submiturl['acceptedDocuments']['uuid'];
            $invoice->submitted_uuid = $uuid;
            $invoice->status = 'submitted';
            $invoice->save();
        }

        return redirect()->back();
    }

    private function fetchToken($merchantDetail)
    {
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
            $token = $response['access_token'];
            $expiresAt = Carbon::now()->addHour();
    
            // Store or update token
            Token::updateOrCreate(
                ['merchant_id' => 1], // Unique constraint
                ['token' => $token, 'expired_at' => $expiresAt]
            );
    
            return $token;
        } else {
            Log::debug('Token Fetch Error', [
                'status' => $response->status(),
                'error' => $response->body()
            ]);
            
            return null;
        }
    }
}
