<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Event;

class ProductObserver
{
    public function saved(Product $product): void
    {
        // Only trigger price update if price-related fields changed
        if ($product->wasChanged(['cost_price', 'retail_price', 'supplier_id'])) {
            $event = new \stdClass();
            $event->model = $product;
            Event::dispatch($event);
        }
    }
}