<?php

namespace App\Http\Controllers;

use App\Models\ConfigPrinter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfigPrinterController extends Controller
{
    private function getPrinters() 
    {
        $allPrinters = ConfigPrinter::where('status', 'active')
                                    ->orderBy('id')
                                    ->get(['id', 'name', 'ip_address', 'port_number', 'kick_cash_drawer', 'status']);
        
        return $allPrinters;
    }

    public function getAllPrinters() 
    {
        $allPrinters = $this->getPrinters();
        
        return response()->json($allPrinters);
    }

    public function createPrinter(Request $request) 
    {
        // Validate the form data
        $validator = Validator::make(
            $request->all(), 
            [
                'name' => 'required|string',
                'ip_address' => 'required|string|ip',
                'port_number' => 'required|integer|min:1|max:65535',
                'kick_cash_drawer' => 'required|boolean',
            ], 
            [
                'required' => 'This field is required.',
                'string' => 'This field must be an string.',
                'integer' => 'This field must be an integer.',
                'ip' => 'This field must be a valid IP address.',
            ]
        );

        if ($validator->fails()) {
            // Collect the errors for the entire form and return them
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If validation passes, you can proceed with processing the validated form data
        $validatedData = $validator->validated();

        $newPrinter = ConfigPrinter::create([
            'name' => $validatedData['name'],
            'ip_address' => $validatedData['ip_address'],
            'port_number' => $validatedData['port_number'],
            'kick_cash_drawer' => $validatedData['kick_cash_drawer'],
            'status' => 'active',
        ]);

        activity()->useLog('create-printer')
                    ->performedOn($newPrinter)
                    ->event('added')
                    ->withProperties([
                        'created_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'printer_name' => $newPrinter->name,
                    ])
                    ->log("Printer '$newPrinter->name' is added.");

        $allPrinters = $this->getPrinters();
        
        return response()->json($allPrinters);
    }

    public function editPrinter(Request $request, string $id) 
    {
        // Validate the form data
        $validator = Validator::make(
            $request->all(), 
            [
                'name' => 'required|string',
                'ip_address' => 'required|string|ip',
                'port_number' => 'required|integer|min:1|max:65535',
                'kick_cash_drawer' => 'required|boolean',
            ], 
            [
                'required' => 'This field is required.',
                'string' => 'This field must be an string.',
                'integer' => 'This field must be an integer.',
                'ip' => 'This field must be a valid IP address.',
            ]
        );

        if ($validator->fails()) {
            // Collect the errors for the entire form and return them
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If validation passes, you can proceed with processing the validated form data
        $validatedData = $validator->validated();

        $printer = ConfigPrinter::find($id);
        
        $printer->update([
            'name' => $validatedData['name'],
            'ip_address' => $validatedData['ip_address'],
            'port_number' => $validatedData['port_number'],
            'kick_cash_drawer' => $validatedData['kick_cash_drawer'],
        ]);

        activity()->useLog('edit-printer')
                    ->performedOn($printer)
                    ->event('updated')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'printer_name' => $printer->name,
                    ])
                    ->log("$printer->name's printer detail is updated.");   
        
        $allPrinters = $this->getPrinters();
        
        return response()->json($allPrinters);
    }

    public function deletePrinter(string $id) 
    {
        $printer = ConfigPrinter::find($id);

        $printer->update(['status' => 'inactive']);

        activity()->useLog('delete-printer')
                    ->performedOn($printer)
                    ->event('deleted')
                    ->withProperties([
                        'edited_by' => auth()->user()->full_name,
                        'image' => auth()->user()->getFirstMediaUrl('user'),
                        'printer_name' => $printer->name,
                    ])
                    ->log("$printer->name is deleted.");
        
        $allPrinters = $this->getPrinters();
        
        return response()->json($allPrinters);
    }

    public function getPrinterTest(Request $request)
    {
        $printerName = $request->printer_name;

        // Create a buffer to capture ESC/POS commands
        $buffer = '';
        
        // Helper function to add text with line breaks
        $addText = function($text) use (&$buffer) {
            $buffer .= $text . "\n";
        };
        
        // Helper function to add raw ESC/POS commands
        $addCommand = function($command) use (&$buffer) {
            $buffer .= $command;
        };
        
        // ===== ESC/POS INITIALIZATION =====
        $addCommand("\x1B\x40"); // Initialize printer
        $addCommand("\x1B\x21\x00"); // Normal text (clear all formatting)
        $addCommand("\x1B\x4D\x00"); // Select Font B (default)
        $addCommand("\x1B\x61\x01"); // Center alignment

        $addText("THIS IS JUST A TEST!");
        $addText("by $printerName");
        $addText(""); // Empty line
        $addText("Test generated by STOXPOS");
        
        // Add some empty lines and cut
        $addText("\n\n\n\n\n");
        $addCommand("\x1D\x56\x01"); // Partial cut

        // $printerIp = '192.168.0.77';
        // $printerPort = '9100';

        // $socket = fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
        // if (!$socket) {
        //     return "Error: $errstr ($errno)";
        // }

        // fwrite($socket, $buffer);
        // fclose($socket);

        // Get printer
        $printer = ConfigPrinter::where([
                                    ['name', $printerName],
                                    ['status', 'active']
                                ])
                                ->first();

        try {
            // Return base64 encoded version for JSON safety
            return response()->json([
                'success' => true,
                'data' => base64_encode($buffer), // Encode binary as base64
                'printer' => $printer,
                'message' => 'Print job sent'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Print receipt error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
