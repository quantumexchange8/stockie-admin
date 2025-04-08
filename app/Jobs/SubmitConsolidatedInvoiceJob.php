<?php

namespace App\Jobs;

use App\Models\ConfigMerchant;
use App\Models\ConsolidatedInvoice;
use App\Models\MSICCodes;
use App\Models\State;
use App\Models\Token;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubmitConsolidatedInvoiceJob implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue, Queueable, SerializesModels;

    protected $invoiceId;
    protected $token;
    protected $env;

    /**
     * Create a new job instance.
     */
    public function __construct($invoiceId, $token)
    {
        $this->invoiceId = $invoiceId;
        $this->token = $token;
        $this->env = env('APP_ENV');
        $this->queue = 'submit-consolidate-invoice';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoice = ConsolidatedInvoice::find($this->invoiceId)->with(['invoice_child']);
        $merchantDetail = ConfigMerchant::first();
        $msic = MSICCodes::find($merchantDetail->msic_id);
        $state = State::where('State', $merchantDetail->state)->first();
        $payments = $invoice->invoice_child;
        $checkToken = Token::first();

        if (!$invoice) {
            Log::error("Invoice ID {$this->invoiceId} not found.");
            return;
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
                    'codeNumber' => $invoice->c_invoice_no,
                ]
            ]
        ];

        if ($this->env === 'production') {
            $docsSubmitApi = 'https://preapi.myinvois.hasil.gov.my/api/v1.0/documentsubmissions';
        } else {
            $docsSubmitApi = 'https://preprod-api.myinvois.hasil.gov.my/api/v1.0/documentsubmissions';
        }

        $submiturl = Http::withToken($this->token)->post($docsSubmitApi, $document);
        if ($submiturl->successful()) {
            Log::debug('submission ', $submiturl);

            $uuid = $submiturl['acceptedDocuments']['uuid'];
            $invoice->submitted_uuid = $uuid;
            $invoice->status = 'submitted';
            $invoice->save();
        }
    }
}
