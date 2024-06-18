<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function dashboard(){
        return Inertia::render('Dashboard');
        }
    
        public function component(){
            return Inertia::render('ComponentDisplay/ComponentShowcase');
            }
}
