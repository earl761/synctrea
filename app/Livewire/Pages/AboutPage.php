<?php

namespace App\Livewire\Pages;

use Livewire\Component;

class AboutPage extends Component
{
    public $title = 'About Us';
    public $metaDescription = 'Learn more about SyncTrae, our mission, and the team behind our inventory management platform.';
    
    public function render()
    {
        return view('livewire.pages.about-page')
            ->layout('components.layouts.app', [
                'title' => $this->title,
                'metaDescription' => $this->metaDescription
            ]);
    }
}
