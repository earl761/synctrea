<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index()
    {
        $packages = SubscriptionPackage::all();
        return view('pricing', compact('packages'));
    }
} 