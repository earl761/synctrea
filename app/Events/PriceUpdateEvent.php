<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class PriceUpdateEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $model;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}