<?php

namespace App\Http\Controllers;

use App\Models\ConfigPrinter;
use App\Models\ShiftPayHistory;
use App\Models\ShiftTransaction;
use App\Services\RunningNumberService;
use App\Services\ShiftTransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function viewShiftControl()
    {
        $shiftTransactions = $this->getShiftTransactions();

        return Inertia::render('ShiftManagement/ShiftControl', [
            'shiftTransactions' => $shiftTransactions
        ]);
    }

    public function viewShiftRecord() 
    {
        $shiftTransactions = ShiftTransaction::with([
                                                    'openedBy:id,full_name', 
                                                    'closedBy:id,full_name', 
                                                    'shiftPayHistories.handledBy:id,full_name',
                                                ])
                                                ->where('status', 'closed')
                                                ->orderByDesc('id')
                                                ->get();

        $shiftTransactions->each(function ($shift) {
            if ($shift->openedBy) {
                $shift->openedBy->image = $shift->openedBy->getFirstMediaUrl('user');
            }
            
            if ($shift->closedBy) {
                $shift->closedBy->image = $shift->closedBy->getFirstMediaUrl('user');
            }
            
            $shift->shiftPayHistories->each(function ($history) {
                if ($history->handledBy) {
                    $history->handledBy->image = $history->handledBy->getFirstMediaUrl('user');
                }
            });
        });

        return Inertia::render('ShiftManagement/ShiftRecord', [
            'shiftTransactions' => $shiftTransactions
        ]);
    }

    private function getShiftTransactions() 
    {
        $shiftTransactions = ShiftTransaction::with([
                                                    'openedBy:id,full_name', 
                                                    'closedBy:id,full_name', 
                                                    'shiftPayHistories.handledBy:id,full_name',
                                                ])
                                                ->orderByDesc('id')
                                                ->get();

        $shiftTransactions->each(function ($shift) {
            if ($shift->openedBy) {
                $shift->openedBy->image = $shift->openedBy->getFirstMediaUrl('user');
            }
            
            if ($shift->closedBy) {
                $shift->closedBy->image = $shift->closedBy->getFirstMediaUrl('user');
            }
            
            $shift->shiftPayHistories->each(function ($history) {
                if ($history->handledBy) {
                    $history->handledBy->image = $history->handledBy->getFirstMediaUrl('user');
                }
            });
        });

        return $shiftTransactions;
    }

    public function openShift(Request $request) 
    {
        $validatedData = $request->validate(
            ['starting_cash' => 'required|decimal:0,2'], 
            ['required' => 'This field is required.']
        );

        ShiftTransaction::create([
            'opened_by' => auth()->user()->id,
            'shift_no' => RunningNumberService::getID('shift'),
            'starting_cash' => $validatedData['starting_cash'],
            'paid_no' => 0.00,
            'paid_out' => 0.00,
            'cash_refund' => 0.00,
            'expected_cash' => 0.00,
            'cash_sales' => 0.00,
            'card_sales' => 0.00,
            'ewallet_sales' => 0.00,
            'gross_sales' => 0.00,
            'sst_amount' => 0.00,
            'service_tax_amount' => 0.00,
            'total_refund' => 0.00,
            'total_void' => 0.00,
            'total_discount' => 0.00,
            'net_sales' => 0.00,
            'shift_opened' => now(),
            'status' => 'opened',
        ]);

        $shiftTransactions = $this->getShiftTransactions();

        return response()->json($shiftTransactions);
    }

    public function closeShift(Request $request, string $id) 
    {
        $validatedData = $request->validate(
            ['closing_cash' => 'required|decimal:0,2'], 
            ['required' => 'This field is required.']
        );

        $currentShift = ShiftTransaction::find($id);

        $currentShift->update([
            'closed_by' => auth()->user()->id,
            'closing_cash' => $validatedData['closing_cash'],
            'shift_closed' => now(),
            'status' => 'closed',
        ]);

        ShiftTransactionService::updateDetails($id);

        $shiftTransactions = $this->getShiftTransactions();
        
        return response()->json($shiftTransactions);
    }

    public function shiftPayTransaction(Request $request, string $id) 
    {
        $validatedData = $request->validate(
            [
                'amount' => 'required|decimal:0,2',
                'reason' => 'required|string',
                'type' => 'required|string',
            ], 
            ['required' => 'This field is required.']
        );

        ShiftPayHistory::create([
            'shift_transaction_id' => $id,
            'user_id' => auth()->user()->id,
            'type' => $validatedData['type'],
            'amount' => $validatedData['amount'],
            'reason' => $validatedData['reason'],
        ]);

        $currentShift = ShiftTransaction::find($id);

        switch ($validatedData['type']) {
            case 'in':
                $currentShift->increment('paid_in', $validatedData['amount']);
                break;
                
                case 'out':
                $currentShift->increment('paid_out', $validatedData['amount']);
                break;
        }

        ShiftTransactionService::updateDetails($id);

        $shiftTransactions = $this->getShiftTransactions();

        return response()->json($shiftTransactions);
    }

    public function getFilteredShiftTransactions(Request $request) 
    {
        $dateFilter = $request->input('date_filter');

        $dateFilter = array_map(function ($date){
            return (new \DateTime($date))->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d');
        }, $dateFilter);

        $shiftTransactions = ShiftTransaction::where(function ($query) use ($dateFilter) {
                                                    $startDate = Carbon::parse($dateFilter[0])->startOfDay()->format('Y-m-d H:i:s');
                                                    $secondaryDate = count($dateFilter) > 1 ? $dateFilter[1] : $dateFilter[0];
                                                    $endDate = Carbon::parse($secondaryDate)->endOfDay()->format('Y-m-d H:i:s');

                                                    $query->where(fn ($q) =>
                                                                $q->whereDate('shift_opened', '>=', $startDate)
                                                                    ->whereDate('shift_opened', '<=', $endDate)
                                                            );
                                                })
                                                ->with([
                                                    'openedBy:id,full_name', 
                                                    'closedBy:id,full_name', 
                                                    'shiftPayHistories.handledBy:id,full_name',
                                                ])
                                                ->where('status', 'closed')
                                                ->orderByDesc('id')
                                                ->get();

        $shiftTransactions->each(function ($shift) {
            if ($shift->openedBy) {
                $shift->openedBy->image = $shift->openedBy->getFirstMediaUrl('user');
            }
            
            if ($shift->closedBy) {
                $shift->closedBy->image = $shift->closedBy->getFirstMediaUrl('user');
            }
            
            $shift->shiftPayHistories->each(function ($history) {
                if ($history->handledBy) {
                    $history->handledBy->image = $history->handledBy->getFirstMediaUrl('user');
                }
            });
        });
        
        return response()->json($shiftTransactions);
    }

    // ===== RAW HELPER METHODS =====
    // For 2 column valued row
    private function printTwoColumnsRightRaw($addText, $left, $right, $boldSide = 'right')
    {
        $leftWidth = 24;
        $rightWidth = 24;

        // Process left text with wrapping
        $leftLines = explode("\n", wordwrap($left, $leftWidth, "\n", true));
        $rightLines = explode("\n", wordwrap($right, $rightWidth, "\n", true));
        
        $maxLines = max(count($leftLines), count($rightLines));
        
        for ($i = 0; $i < $maxLines; $i++) {
            $currentLeft = $leftLines[$i] ?? '';
            $currentRight = $rightLines[$i] ?? '';
            
            // Format left side (left-aligned)
            $leftFormatted = substr($currentLeft, 0, $leftWidth);
            $leftPadding = str_repeat(' ', max(0, $leftWidth - strlen($leftFormatted)));
            
            // Format right side (right-aligned)
            $rightFormatted = substr($currentRight, 0, $rightWidth);
            $rightPadding = str_repeat(' ', max(0, $rightWidth - strlen($rightFormatted)));
            
            // Build the line with optional bold
            $line = "";
            
            // Left side
            if ($boldSide === 'left') {
                $line .= "\x1B\x21\x08"; // Bold on
            }
            $line .= $leftFormatted;
            if ($boldSide === 'left') {
                $line .= "\x1B\x21\x00"; // Bold off
            }
            $line .= $leftPadding;
            
            // Right side
            if ($boldSide === 'right') {
                $line .= "\x1B\x21\x08"; // Bold on
            }
            $line .= $rightPadding . $rightFormatted;
            if ($boldSide === 'right') {
                $line .= "\x1B\x21\x00"; // Bold off
            }
            
            $addText($line);
        }
    }

    // For 1.25x headers
    private function printHeader($addText, $addCommand, $text) 
    {
        $addCommand("\x1B\x61\x01"); // Center alignment
        $addCommand("\x1B\x21\x00"); // Normal size
        $addCommand("\x1B\x45\x01"); // Bold on
        $addText($text);

        $addCommand("\x1B\x45\x00"); // Bold off
        $addCommand("\x1B\x61\x00"); // Left alignment
        $addText(""); // Empty line
    }

    public function getReceipt(Request $request)
    {
        $shift_transaction = $request->shift_transaction;
        $openingCashier = $shift_transaction['opened_by'];
        $closingCashier = $shift_transaction['closed_by'];

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
        
        // ===== HEADER SECTION =====
        $addCommand("\x1B\x61\x01"); // Center alignment
        $addCommand("\x1B\x21\x30"); // Double height + width
        $addCommand("\x1B\x45\x01"); // Bold
        $addText("Shift Report");
        $addCommand("\x1B\x21\x00"); // Normal text
        $addCommand("\x1B\x45\x00"); // Bold off
        $addText("Shift #" . $shift_transaction['shift_no']);

        $addText(""); // Empty line
        $addText(str_repeat("-", 48));
        $addText(""); // Empty line
        
        // ===== SHIFT BASIC INFO =====
        $addCommand("\x1B\x61\x00"); // Left alignment
        $this->printTwoColumnsRightRaw($addText, "Shift opened", '');
        $this->printTwoColumnsRightRaw($addText, Carbon::parse($shift_transaction['shift_opened'])->format('d/m/Y H:i A'), "by Admin Adminimus Adminosus " . $openingCashier['full_name'], 'left');
        $this->printTwoColumnsRightRaw($addText, "Shift closed", '');
        $this->printTwoColumnsRightRaw($addText, Carbon::parse($shift_transaction['shift_closed'])->format('d/m/Y H:i A'), "by " . $closingCashier['full_name'], 'left');
        
        $addText(""); // Empty line
        $addText(""); // Empty line
        
        // ===== SALES SUMMARY SECTION =====
        $this->printHeader($addText, $addCommand, "Sales Summary");
        $this->printTwoColumnsRightRaw($addText, "Cash Sales", "RM " . number_format($shift_transaction['cash_sales'], 2));
        $this->printTwoColumnsRightRaw($addText, "Card Sales", "RM " . number_format($shift_transaction['card_sales'], 2));
        $this->printTwoColumnsRightRaw($addText, "E-Wallet Sales", "RM " . number_format($shift_transaction['ewallet_sales'], 2));
        
        $addText(str_repeat("-", 48));

        $this->printTwoColumnsRightRaw($addText, "Gross Sales", "RM " . number_format($shift_transaction['gross_sales'], 2));
        $this->printTwoColumnsRightRaw($addText, "SST", "RM " . number_format($shift_transaction['sst_amount'], 2));
        $this->printTwoColumnsRightRaw($addText, "Service tax", "RM " . number_format($shift_transaction['service_tax_amount'], 2));
        $this->printTwoColumnsRightRaw($addText, "Refunds", "- RM " . number_format($shift_transaction['total_refund'], 2));
        $this->printTwoColumnsRightRaw($addText, "Voids", "- RM " . number_format($shift_transaction['total_void'], 2));
        $this->printTwoColumnsRightRaw($addText, "Discounts", "- RM " . number_format($shift_transaction['total_discount'], 2));

        $addText(str_repeat("-", 48));

        $this->printTwoColumnsRightRaw($addText, "Net Sales (excl. taxes)", "RM " . number_format($shift_transaction['net_sales'], 2));
        
        $addText(""); // Empty line
        $addText(""); // Empty line

        // ===== SHIFT & CASH DRAWER DETAIL SECTION =====
        $this->printHeader($addText, $addCommand, "Shift & cash drawer detail");
        $this->printTwoColumnsRightRaw($addText, "Starting cash", "RM " . number_format($shift_transaction['starting_cash'], 2));
        $this->printTwoColumnsRightRaw($addText, "Paid in", "RM " . number_format($shift_transaction['paid_in'], 2));
        $this->printTwoColumnsRightRaw($addText, "Paid out", "- RM " . number_format($shift_transaction['paid_out'], 2));
        $this->printTwoColumnsRightRaw($addText, "Cash refunds", "- RM " . number_format($shift_transaction['cash_refund'], 2));
        $this->printTwoColumnsRightRaw($addText, "Expected cash", "RM " . number_format($shift_transaction['expected_cash'], 2));
        $this->printTwoColumnsRightRaw($addText, "Closing cash", "RM " . number_format($shift_transaction['closing_cash'], 2));
        $this->printTwoColumnsRightRaw($addText, "Cash difference", ($shift_transaction['difference'] < 0 ? '- ' : '') . "RM " . number_format(abs($shift_transaction['difference']), 2));

        $addText(""); // Empty line
        $addText(""); // Empty line
        
        // ===== FOOTER SECTION =====
        $addCommand("\x1B\x61\x01"); // Center alignment
        $addText("Generated on: " . now()->format('Y-m-d H:i'));
        
        // Add some empty lines and cut
        $addText("\n\n\n\n\n");
        $addCommand("\x1D\x56\x01"); // Partial cut

        return $buffer;
    }

    public function getShiftReportReceipt(Request $request)
    {
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
                                    ['name', 'Cashier'],
                                    ['status', 'active']
                                ])
                                ->first();


        // Get the complete ESC/POS commands
        $buffer = $this->getReceipt($request);
        
        try {
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
