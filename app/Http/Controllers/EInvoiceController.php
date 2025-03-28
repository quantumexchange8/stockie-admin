<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class EInvoiceController extends Controller
{
    //
    public function einvoice()
    {


        return Inertia::render('Einvoice/Einvoice');
    }
}
