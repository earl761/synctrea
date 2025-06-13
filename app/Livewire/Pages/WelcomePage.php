<?php

namespace App\Livewire\Pages;

use Livewire\Component;

class WelcomePage extends Component
{
    public $title = 'Welcome';
    public $metaDescription = 'SyncTrae - Smart Inventory Sourcing & Management Platform';
    
    public function render()
    {
        return view('livewire.pages.welcome-page')
            ->layout('components.layouts.app', [
                'title' => $this->title,
                'metaDescription' => $this->metaDescription
            ]);
    }
}
