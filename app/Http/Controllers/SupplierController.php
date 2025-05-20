<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        // Sample data - replace with actual supplier model and logic
        $suppliers = collect([
            (object)[
                'id' => 1,
                'name' => 'Global Manufacturing Co.',
                'location' => 'Shanghai, China',
                'description' => 'Leading manufacturer of electronic components with over 15 years of experience.',
                'verified' => true,
                'specialties' => ['Electronics', 'Consumer Goods', 'Automotive Parts']
            ],
            // Add more sample suppliers
        ]);

        $categories = collect(['Electronics', 'Textiles', 'Automotive', 'Consumer Goods', 'Industrial Equipment']);
        $regions = collect(['Asia', 'Europe', 'North America', 'South America', 'Africa']);

        return view('suppliers.index', compact('suppliers', 'categories', 'regions'));
    }

    public function register()
    {
        return view('suppliers.register');
    }

    public function show($id)
    {
        // Sample data - replace with actual supplier model and logic
        $supplier = (object)[
            'id' => $id,
            'name' => 'Global Manufacturing Co.',
            'location' => 'Shanghai, China',
            'description' => 'Leading manufacturer of electronic components with over 15 years of experience.',
            'verified' => true,
            'specialties' => ['Electronics', 'Consumer Goods', 'Automotive Parts'],
            'year_established' => '2008',
            'certification' => ['ISO 9001', 'ISO 14001'],
            'min_order_value' => '$5,000',
            'main_markets' => ['North America', 'Europe', 'Asia'],
            'factory_size' => '50,000 sqm',
            'employees' => '500+'
        ];

        return view('suppliers.show', compact('supplier'));
    }
}