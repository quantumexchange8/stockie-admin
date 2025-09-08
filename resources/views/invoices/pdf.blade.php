<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; display: flex; }
        .invoice-box { width: 100%; padding: 10px; }
        .header-table, .content-table, .info-table, .items-table { width: 100%; border-collapse: collapse; }
        .header-table td, .content-table td, .info-table td { vertical-align: top; }
        .merchant-name { font-size: 14px; font-weight: bold; }
        .submission-uid { font-size: 12px; }
        .items-table th, .items-table td { border: 1px solid #d0d0d0; padding: 4px; font-size: 10px; }
        .items-table th { background-color: #000000; color: #ffffff; text-align: center; }
        .items-table td { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="invoice-box">
        {{-- Header --}}
        <table class="header-table">
            <tr>
                <td>
                    <img src="{{ public_path('images/icons/logo.png') }}" alt="Logo" style="width:150px; height:auto;">
                </td>
                <td style="font-size:20px; font-weight:bold; text-align:right">Invoice {{ $invoice->c_invoice_no }}</td>
            </tr>
            <tr>
                <td style="height: 4px"></td>
            </tr>
            <tr>
                <td>
                    <div class="merchant-name">{{ $merchant->merchant_name }}</div>
                    <div style="max-width:250px">Address: {{ $merchant->merchant_address_line1 }}, {{ $merchant->merchant_address_line2 ? $merchant->merchant_address_line2 . ',' : '' }} {{ $merchant->postal_code }} {{ $merchant->area}}, Malaysia</div>
                    <div>Reg.No: {{ $merchant->registration_no }}</div>
                    <div>Contact: {{ $merchant->merchant_contact }}</div>
                    <div>Email: {{ $merchant->email_address }}</div>
                </td>
                <td class="right">
                    <div class="submission-uid">E-Invoice Version: 1.0</div>
                    <div class="submission-uid">Submission UID: {{ $invoice->submitted_uuid }}</div>
                    <div class="submission-uid">UUID: {{ $invoice->uuid }}</div>
                    <div class="submission-uid">Issue Date&Time: {{ $invoice->updated_at }}</div>
                </td>
            </tr>
        </table>

        {{-- Invoice Status --}}
        <table class="content-table" style="margin:20px 0;">
            <tr>
                <td class="right" style="font-size:30px; font-weight:bold; color: {{ $invoice->status === 'Valid' ? 'green' : 'red' }};">
                    {{ ucfirst($invoice->status) }}
                </td>
            </tr>
        </table>

        {{-- Supplier & Buyer --}}
        <table class="info-table" style="margin-bottom:20px;">
            <tr>
                <td>
                    <div style="font-size:16px; font-weight:bold;">Supplier</div>
                    <div>Supplier TIN: {{ $merchant->tin_no }}</div>
                    <div>Supplier Name: {{ $merchant->merchant_name }}</div>
                    <div>Supplier Registration Number: {{ $merchant->registration_no }}</div>
                    <div>Supplier SST ID: {{ $merchant->sst_registration_no ?? 'NA' }}</div>
                    <div style="max-width: 300px">Supplier Business activity description: {{ $merchant->description ?? 'NA' }}</div>
                    <div>Supplier Contact: {{ $merchant->merchant_contact }}</div>
                    <div>Supplier MSIC: {{ $merchant->msicCode->Code }}</div>
                </td>
                <td class="right">
                    <div style="font-size:16px; font-weight:bold;">Buyer</div>
                    <div>Buyer TIN: <span style="color: #052bff">EI00000000010</span></div>
                    <div>Buyer Name: <span style="color: #052bff">General Public</span></div>
                    <div>Buyer Registration Number: NA</div>
                    <div style="">Buyer Address: NA</div>
                    <div style="">Buyer Contact Number: NA</div>
                    <div>Buyer Email: NA</div>
                    <div>Buyer SST Registration: NA</div>
                </td>
            </tr>
        </table>

        {{-- Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>Classification</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                    <th>Disc</th>
                    <th>Tax Amount</th>
                    <th>Total Product / Service</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ranges as $item)
                    <tr>
                        <td>004</td>
                        <td class="center">{{ $item['receipt_batch'] }}</td>
                        <td>1</td>
                        <td class="right">RM {{ number_format($item['sum_amount'], 2) }}</td>
                        <td class="right">RM {{ number_format($item['sum_amount'], 2) }}</td>
                        <td>-</td>
                        <td>-</td>
                        <td class="right">RM {{ number_format($item['sum_amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="right bold">Subtotal:</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="right bold">RM {{ number_format($item['sum_amount'], 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="right bold">Total excluding tax:</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="right bold">RM {{ number_format($item['sum_amount'], 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="right bold">Tax Amount:</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="right bold">-</td>
                </tr>
                <tr>
                    <td colspan="4" class="right bold">Total Including tax:</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="right bold">RM {{ number_format($item['sum_amount'], 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <table class="info-table" style="margin: 20px 0; width: 100%;">
            <tr>
                <td style="vertical-align: middle;">
                    <div>Date and Time of Validation: {{ $invoice->invoice_datetime ?? 'N/A' }}</div>
                    <div>This document is a visual presentation of the e-invoice.</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <img src="data:image/png;base64, {!! base64_encode(QrCode::size(100)->generate($generateQr)) !!} " style="width: 100px; height: 100px;">
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
