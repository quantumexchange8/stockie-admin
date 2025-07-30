<?php

namespace App\Http\Controllers;

use App\Models\PayoutConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Session;

class GlobalController extends Controller
{
    //

    public function getPayoutDetails()
    {

        $payouts = PayoutConfig::first();

        return response()->json($payouts);
    }

    public function setLocale(string $locale)
    {
        App::setLocale($locale);
        Session::put("locale", $locale);

        return redirect()->back();
    }
}
