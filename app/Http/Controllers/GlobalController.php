<?php

namespace App\Http\Controllers;

use App\Models\PayoutConfig;
use Illuminate\Http\Request;

class GlobalController extends Controller
{
    //

    public function getPayoutDetails()
    {

        $payouts = PayoutConfig::first();

        return response()->json($payouts);
    }
}
