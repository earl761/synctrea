<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Illuminate\Support\Facades\Mail;

class ContactPage extends Component
{
    public $title = 'Contact Us';
    public $metaDescription = 'Get in touch with our team. We\'re here to help with any questions about our platform.';
    
    public $name;
    public $email;
    public $subject;
    public $message;
    
    protected $rules = [
        'name' => 'required|string|min:2|max:100',
        'email' => 'required|email|max:100',
        'subject' => 'required|string|min:5|max:200',
        'message' => 'required|string|min:10|max:2000',
    ];
    
    public function submitForm()
    {
        $validatedData = $this->validate();
        
        // Here you would typically send an email
        // Mail::to('contact@example.com')->send(new ContactFormMail($validatedData));
        
        // Clear the form
        $this->reset(['name', 'email', 'subject', 'message']);
        
        // Show success message
        session()->flash('success', 'Your message has been sent! We\'ll get back to you soon.');
    }
    
    public function render()
    {
        return view('livewire.pages.contact-page')
            ->layout('components.layouts.app', [
                'title' => $this->title,
                'metaDescription' => $this->metaDescription
            ]);
    }
}
