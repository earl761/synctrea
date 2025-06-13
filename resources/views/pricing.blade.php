<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pricing - SyncTrae</title>
    
    <!-- Optimized Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/images/favicon.svg">
    <link rel="icon" type="image/png" href="/images/favicon.png">
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- AOS Animations -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('navigation', {
                mobileMenuOpen: false,
                toggleMobileMenu() {
                    this.mobileMenuOpen = !this.mobileMenuOpen;
                },
                closeAll() {
                    this.mobileMenuOpen = false;
                }
            });
            
            Alpine.store('pricing', {
                annual: false,
                toggleBilling() {
                    this.annual = !this.annual;
                }
            });
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-out',
                once: true
            });
        });
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- SEO Meta Tags -->
    <meta name="description" content="Flexible pricing plans for every business size. Get started with SyncTrae today!">
    <meta property="og:title" content="Pricing - SyncTrae">
    <meta property="og:description" content="Choose the perfect plan for your business needs">
</head>
<body class="antialiased bg-white" x-data>
    <!-- Navigation -->
    <nav x-data class="w-full bg-white z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Left side -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center" @click="$store.navigation.closeAll()">
                        <span class="text-indigo-600 font-bold text-xl">SyncTrae</span>
                    </a>
                    <!-- Desktop Navigation -->
                    <div class="hidden lg:ml-10 lg:flex lg:items-center lg:space-x-8">
                        <a href="/" class="text-gray-600 hover:text-gray-900 transition" @click="$store.navigation.closeAll()">Home</a>
                        <a href="#" class="text-gray-600 hover:text-gray-900 transition" @click="$store.navigation.closeAll()">Solutions</a>
                        <a href="/about" class="text-gray-600 hover:text-gray-900 transition" @click="$store.navigation.closeAll()">About</a>
                        <a href="/contact" class="text-gray-600 hover:text-gray-900 transition" @click="$store.navigation.closeAll()">Contact</a>
                    </div>
                </div>

                <!-- Right side -->
                <div class="flex items-center">
                    <div class="hidden lg:flex lg:items-center lg:space-x-6">
                    @if (Route::has('login'))
                        @auth
                                <a href="{{ url('/admin') }}" class="text-base font-medium text-gray-600 hover:text-gray-900 transition" @click="$store.navigation.closeAll()">
                                    Dashboard
                                </a>
                        @else
                                <a href="{{ route('filament.admin.auth.login') }}" class="text-base font-medium text-gray-600 hover:text-gray-900 transition" @click="$store.navigation.closeAll()">
                                    Sign in
                                </a>
                                <a href="{{ route('filament.admin.auth.register') }}" class="inline-flex items-center justify-center px-6 py-2 text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition rounded-full shadow-sm" @click="$store.navigation.closeAll()">
                                    Get Started
                                </a>
                        @endauth
                    @endif
                    </div>

                    <!-- Mobile menu button -->
                    <div class="flex items-center lg:hidden">
                        <button 
                            @click="$store.navigation.toggleMobileMenu()"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition"
                            aria-expanded="false">
                            <span class="sr-only">Open main menu</span>
                            <svg 
                                x-show="!$store.navigation.mobileMenuOpen"
                                class="h-6 w-6" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg 
                                x-show="$store.navigation.mobileMenuOpen"
                                class="h-6 w-6" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor"
                                x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div 
            x-show="$store.navigation.mobileMenuOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="lg:hidden"
            x-cloak>
            <div class="pt-2 pb-3 space-y-1">
                <a href="/" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Home</a>
                <a href="#" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Solutions</a>
                <a href="/about" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">About</a>
                <a href="/contact" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Contact</a>
            </div>
            <div class="pt-4 pb-3 border-t border-gray-200">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/admin') }}" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Dashboard</a>
                    @else
                        <div class="space-y-1">
                            <a href="{{ route('filament.admin.auth.login') }}" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Sign in</a>
                            <a href="{{ route('filament.admin.auth.register') }}" class="block pl-3 pr-4 py-2 text-base font-medium text-indigo-600 hover:text-indigo-700 hover:bg-gray-50 transition">Get Started</a>
                        </div>
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <!-- Hero Section -->
    <section class="relative pt-20 pb-24 bg-gradient-to-b from-indigo-50/70 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto">
                <h1 class="text-4xl font-bold text-gray-900 sm:text-5xl md:text-6xl">
                    Simple, Transparent Pricing
                </h1>
                <p class="mt-6 text-xl text-gray-600">
                    Choose the perfect plan for your business needs. All plans include a 14-day free trial.
                </p>
            </div>

            <!-- Pricing Toggle -->
            <div class="mt-12 flex justify-center items-center space-x-3" x-data>
                <span class="text-gray-500 font-medium" :class="{ 'text-indigo-600 font-semibold': !$store.pricing.annual }">Monthly</span>
                <button 
                    @click="$store.pricing.toggleBilling()"
                    type="button" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600"
                    :class="{ 'bg-indigo-600': $store.pricing.annual }"
                    role="switch" 
                    aria-checked="false">
                    <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                          :class="{ 'translate-x-5': $store.pricing.annual, 'translate-x-0': !$store.pricing.annual }">
                    </span>
                </button>
                <span class="text-gray-500 font-medium" :class="{ 'text-indigo-600 font-semibold': $store.pricing.annual }">
                    Annual <span class="text-indigo-600">(Save 20%)</span>
                </span>
            </div>

            <!-- Pricing Cards -->
            <div class="mt-16 grid grid-cols-1 gap-8 lg:grid-cols-3">
                @php
                    $packages = App\Models\SubscriptionPackage::where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();
                @endphp

                @foreach($packages as $package)
                    <div data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}" class="relative flex flex-col bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                        <!-- Header -->
                        <div class="p-6 bg-gradient-to-r {{ $package->name === 'Basic' ? 'from-blue-600 to-blue-800' : ($package->name === 'Professional' ? 'from-indigo-600 to-purple-600' : 'from-purple-600 to-pink-600') }} text-white">
                            <h3 class="text-2xl font-bold">{{ $package->name }}</h3>
                            <div class="mt-4 flex items-baseline">
                                <span x-show="!$store.pricing.annual" class="text-4xl font-extrabold tracking-tight">${{ number_format($package->price, 0) }}</span>
                                <span x-show="$store.pricing.annual" class="text-4xl font-extrabold tracking-tight">${{ number_format($package->price * 0.8 * 12, 0) }}</span>
                                <span class="ml-1 text-xl font-medium">/{{ x-show="!$store.pricing.annual" ? 'month' : 'year' }}</span>
                            </div>
                            <p class="mt-2 text-white/80">{{ $package->description }}</p>
                        </div>
                        
                        <!-- Features -->
                        <div class="flex-1 p-6">
                            <ul role="list" class="space-y-4">
                                @foreach($package->features as $feature => $description)
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 flex-shrink-0 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="ml-2 text-gray-700">
                                            <span class="font-medium">{{ $feature }}:</span> {{ $description }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        
                        <!-- CTA -->
                        <div class="p-6 bg-gray-50">
                            <a href="{{ route('filament.admin.auth.register', ['package' => $package->id]) }}" 
                               class="block w-full text-center px-6 py-3 rounded-xl text-white {{ $package->name === 'Basic' ? 'bg-blue-600 hover:bg-blue-700' : ($package->name === 'Professional' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-purple-600 hover:bg-purple-700') }} font-medium transition">
                                Start Free Trial
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Enterprise Plan -->
            <div class="mt-16" data-aos="fade-up">
                <div class="relative bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                            <div class="lg:col-span-7">
                                <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                                    Enterprise Plan
                                </h2>
                                <p class="mt-4 text-lg text-gray-300">
                                    Custom solutions for large businesses with complex needs. Includes dedicated support, custom integrations, and enterprise-grade security.
                                </p>
                                <div class="mt-8">
                                    <div class="space-y-4">
                                        <div class="flex items-center">
                                            <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="ml-2 text-lg text-gray-300">Unlimited products and integrations</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="ml-2 text-lg text-gray-300">Dedicated account manager</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="ml-2 text-lg text-gray-300">24/7 premium support</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="ml-2 text-lg text-gray-300">Custom API development</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-10 lg:mt-0 lg:col-span-5">
                                <div class="bg-white rounded-lg shadow-md p-8">
                                    <h3 class="text-2xl font-bold text-gray-900">Contact our sales team</h3>
                                    <p class="mt-4 text-gray-600">Let's discuss how we can help your business scale with our enterprise solutions.</p>
                                    <form class="mt-6 space-y-6">
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
                                            <input type="text" name="name" id="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                                            <input type="email" name="email" id="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label for="company" class="block text-sm font-medium text-gray-700">Company name</label>
                                            <input type="text" name="company" id="company" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                Contact Sales
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-16 max-w-4xl mx-auto">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Frequently Asked Questions</h2>
                <dl class="space-y-6">
                    <div class="bg-white rounded-xl p-6 shadow-sm" x-data="{ open: false }">
                        <dt>
                            <button @click="open = !open" class="text-left w-full flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">What happens after my trial ends?</span>
                                <span class="ml-6 h-7 flex items-center">
                                    <svg class="h-6 w-6 transform transition-transform duration-300" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>
                        </dt>
                        <dd class="mt-2" x-show="open" x-transition>
                            <p class="text-gray-600">
                                After your 14-day trial period ends, your account will be automatically converted to the plan you selected. You can change or cancel your plan at any time from your account dashboard.
                            </p>
                        </dd>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-sm" x-data="{ open: false }">
                        <dt>
                            <button @click="open = !open" class="text-left w-full flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Can I change plans later?</span>
                                <span class="ml-6 h-7 flex items-center">
                                    <svg class="h-6 w-6 transform transition-transform duration-300" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>
                        </dt>
                        <dd class="mt-2" x-show="open" x-transition>
                            <p class="text-gray-600">
                                Yes, you can upgrade or downgrade your plan at any time. When you upgrade, the changes take effect immediately and you'll be charged the prorated amount for the remainder of your billing cycle. When you downgrade, the changes take effect at the end of your current billing cycle.
                            </p>
                        </dd>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-sm" x-data="{ open: false }">
                        <dt>
                            <button @click="open = !open" class="text-left w-full flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Do you offer refunds?</span>
                                <span class="ml-6 h-7 flex items-center">
                                    <svg class="h-6 w-6 transform transition-transform duration-300" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>
                        </dt>
                        <dd class="mt-2" x-show="open" x-transition>
                            <p class="text-gray-600">
                                We offer a 30-day money-back guarantee. If you're not satisfied with our service within the first 30 days of your paid subscription, contact our support team and we'll process a full refund.
                            </p>
                        </dd>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-sm" x-data="{ open: false }">
                        <dt>
                            <button @click="open = !open" class="text-left w-full flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">What payment methods do you accept?</span>
                                <span class="ml-6 h-7 flex items-center">
                                    <svg class="h-6 w-6 transform transition-transform duration-300" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>
                        </dt>
                        <dd class="mt-2" x-show="open" x-transition>
                            <p class="text-gray-600">
                                We accept all major credit cards (Visa, MasterCard, American Express, Discover) and PayPal. For Enterprise plans, we also offer invoice-based payments.
                            </p>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-indigo-600 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-white">Ready to Get Started?</h2>
                <p class="mt-4 text-xl text-indigo-100">Start your free trial today. No credit card required.</p>
                <div class="mt-8">
                    <a href="{{ route('filament.admin.auth.register') }}" class="inline-block px-8 py-3 text-indigo-600 bg-white hover:bg-indigo-50 rounded-full text-lg font-semibold transition">
                        Start Your Free Trial
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Product</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="/#features" class="text-base text-gray-300 hover:text-white">Features</a></li>
                        <li><a href="/#suppliers" class="text-base text-gray-300 hover:text-white">Suppliers</a></li>
                        <li><a href="{{ route('pricing') }}" class="text-base text-gray-300 hover:text-white">Pricing</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Support</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Documentation</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">API Reference</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Help Center</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Company</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="/about" class="text-base text-gray-300 hover:text-white">About</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Blog</a></li>
                        <li><a href="/contact" class="text-base text-gray-300 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Legal</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Privacy</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Terms</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-700 pt-8 md:flex md:items-center md:justify-between">
                <div class="flex space-x-6 md:order-2">
                    <a href="#" class="text-gray-400 hover:text-gray-300">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-300">
                        <span class="sr-only">GitHub</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.42.267-1.042.732-2.332z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
                <p class="mt-8 text-base text-gray-400 md:mt-0 md:order-1">&copy; {{ date('Y') }} SyncTrae. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>