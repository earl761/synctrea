<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SyncTrae - Smart Inventory Sourcing & Management Platform</title>
    
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
                solutionsOpen: false,
                resourcesOpen: false,
                toggleMobileMenu() {
                    this.mobileMenuOpen = !this.mobileMenuOpen;
                    this.solutionsOpen = false;
                    this.resourcesOpen = false;
                },
                toggleSolutions() {
                    this.solutionsOpen = !this.solutionsOpen;
                    this.resourcesOpen = false;
                },
                toggleResources() {
                    this.resourcesOpen = !this.resourcesOpen;
                    this.solutionsOpen = false;
                },
                closeAll() {
                    this.mobileMenuOpen = false;
                    this.solutionsOpen = false;
                    this.resourcesOpen = false;
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
    <meta name="description" content="Connect with verified suppliers, access millions of products, and automate your inventory sourcing with SyncTrae">
    <meta property="og:title" content="SyncTrae - Your Ultimate Source for Product Inventory">
    <meta property="og:description" content="Perfect for retailers, dropshippers, and e-commerce businesses">
    <meta property="og:type" content="website">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "SyncTrae",
        "applicationCategory": "BusinessApplication",
        "description": "Connect with verified suppliers, access millions of products, and automate your inventory sourcing",
        "offers": {
            "@type": "Offer",
            "availability": "https://schema.org/OnlineOnly"
        }
    }
    </script>
</head>
<body class="antialiased bg-white" x-data>
    <!-- Navigation -->
    <nav x-data class="fixed w-full bg-white/95 backdrop-blur-sm z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Left side -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center" @click="$store.navigation.closeAll()">
                        <span class="text-indigo-600 font-bold text-xl">SyncTrae</span>
                    </a>
                    <!-- Desktop Navigation -->
                    <div class="hidden lg:ml-10 lg:flex lg:items-center lg:space-x-8">
                        <!-- Solutions Dropdown -->
                        <div class="relative">
                            <button 
                                @click="$store.navigation.toggleSolutions()" 
                                @click.away="$store.navigation.solutionsOpen = false"
                                class="group inline-flex items-center text-gray-600 hover:text-gray-900 transition">
                                <span>Solutions</span>
                                <svg 
                                    class="ml-2 h-5 w-5 text-gray-400 group-hover:text-gray-500 transition-transform duration-200" 
                                    :class="{ 'transform rotate-180': $store.navigation.solutionsOpen }"
                                    xmlns="http://www.w3.org/2000/svg" 
                                    viewBox="0 0 20 20" 
                                    fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div 
                                x-show="$store.navigation.solutionsOpen"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-1"
                                class="absolute z-10 -ml-4 mt-3 w-screen max-w-md transform lg:max-w-2xl lg:ml-0 lg:left-1/2 lg:-translate-x-1/2"
                                x-cloak>
                                <div class="rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden">
                                    <div class="relative grid gap-6 bg-white px-5 py-6 sm:gap-8 sm:p-8 lg:grid-cols-2">
                                        <a href="#" class="flex items-start p-3 hover:bg-gray-50 rounded-lg transition">
                                            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-md bg-indigo-600 text-white sm:h-12 sm:w-12">
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-base font-medium text-gray-900">Product Discovery</p>
                                                <p class="mt-1 text-sm text-gray-500">Find and source products from verified suppliers worldwide.</p>
                                            </div>
                                        </a>
                                        <a href="#" class="flex items-start p-3 hover:bg-gray-50 rounded-lg transition">
                                            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-md bg-indigo-600 text-white sm:h-12 sm:w-12">
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-base font-medium text-gray-900">Supplier Network</p>
                                                <p class="mt-1 text-sm text-gray-500">Connect with verified suppliers and manage relationships.</p>
                                            </div>
                                        </a>
                                        <a href="#" class="flex items-start p-3 hover:bg-gray-50 rounded-lg transition">
                                            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-md bg-indigo-600 text-white sm:h-12 sm:w-12">
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-base font-medium text-gray-900">Inventory Management</p>
                                                <p class="mt-1 text-sm text-gray-500">Track and manage inventory across multiple suppliers.</p>
                                            </div>
                                        </a>
                                        <a href="#" class="flex items-start p-3 hover:bg-gray-50 rounded-lg transition">
                                            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-md bg-indigo-600 text-white sm:h-12 sm:w-12">
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-base font-medium text-gray-900">Analytics & Insights</p>
                                                <p class="mt-1 text-sm text-gray-500">Data-driven decisions for your sourcing strategy.</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a href="#suppliers" class="text-gray-600 hover:text-gray-900 transition" @click="$store.navigation.closeAll()">Suppliers</a>
                        
                        <!-- Resources Dropdown -->
                        <div class="relative">
                            <button 
                                @click="$store.navigation.toggleResources()" 
                                @click.away="$store.navigation.resourcesOpen = false"
                                class="group inline-flex items-center text-gray-600 hover:text-gray-900 transition">
                                <span>Resources</span>
                                <svg 
                                    class="ml-2 h-5 w-5 text-gray-400 group-hover:text-gray-500 transition-transform duration-200" 
                                    :class="{ 'transform rotate-180': $store.navigation.resourcesOpen }"
                                    xmlns="http://www.w3.org/2000/svg" 
                                    viewBox="0 0 20 20" 
                                    fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div 
                                x-show="$store.navigation.resourcesOpen"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-1"
                                class="absolute z-10 left-1/2 transform -translate-x-1/2 mt-3 px-2 w-screen max-w-xs sm:px-0"
                                x-cloak>
                                <div class="rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden">
                                    <div class="relative grid gap-6 bg-white px-5 py-6 sm:gap-8 sm:p-8">
                                        <a href="#" class="block p-3 hover:bg-gray-50 transition rounded-lg">
                                            <p class="text-base font-medium text-gray-900">Help Center</p>
                                            <p class="mt-1 text-sm text-gray-500">Get all your questions answered.</p>
                                        </a>
                                        <a href="#" class="block p-3 hover:bg-gray-50 transition rounded-lg">
                                            <p class="text-base font-medium text-gray-900">Guides</p>
                                            <p class="mt-1 text-sm text-gray-500">Learn how to maximize our platform.</p>
                                        </a>
                                        <a href="#" class="block p-3 hover:bg-gray-50 transition rounded-lg">
                                            <p class="text-base font-medium text-gray-900">Blog</p>
                                            <p class="mt-1 text-sm text-gray-500">Read our latest news and articles.</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a href="#pricing" class="text-gray-600 hover:text-gray-900 transition" @click="$store.navigation.closeAll()">Pricing</a>
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
                                <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center px-6 py-2 text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition rounded-full shadow-sm" @click="$store.navigation.closeAll()">
                                    Start Free Trial
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
                <!-- Mobile Solutions Dropdown -->
                <div x-data="{ open: false }">
                    <button 
                        @click="open = !open"
                        class="w-full flex items-center justify-between pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">
                        <span>Solutions</span>
                        <svg 
                            class="ml-2 h-5 w-5 text-gray-400 transition-transform duration-200" 
                            :class="{ 'transform rotate-180': open }"
                            xmlns="http://www.w3.org/2000/svg" 
                            viewBox="0 0 20 20" 
                            fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" class="pl-4" x-cloak>
                        <a href="#" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Product Discovery</a>
                        <a href="#" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Supplier Network</a>
                        <a href="#" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Inventory Management</a>
                        <a href="#" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Analytics & Insights</a>
                    </div>
                </div>

                <a href="#suppliers" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Suppliers</a>

                <!-- Mobile Resources Dropdown -->
                <div x-data="{ open: false }">
                    <button 
                        @click="open = !open"
                        class="w-full flex items-center justify-between pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">
                        <span>Resources</span>
                        <svg 
                            class="ml-2 h-5 w-5 text-gray-400 transition-transform duration-200" 
                            :class="{ 'transform rotate-180': open }"
                            xmlns="http://www.w3.org/2000/svg" 
                            viewBox="0 0 20 20" 
                            fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" class="pl-4" x-cloak>
                        <a href="#" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Help Center</a>
                        <a href="#" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Guides</a>
                        <a href="#" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Blog</a>
                    </div>
                </div>

                <a href="#pricing" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Pricing</a>
            </div>
            <div class="pt-4 pb-3 border-t border-gray-200">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/admin') }}" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Dashboard</a>
                    @else
                        <div class="space-y-1">
                            <a href="{{ route('filament.admin.auth.login') }}" class="block pl-3 pr-4 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition">Sign in</a>
                            <a href="{{ route('pricing') }}" class="block pl-3 pr-4 py-2 text-base font-medium text-indigo-600 hover:text-indigo-700 hover:bg-gray-50 transition">Start Free Trial</a>
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
    <section class="relative pt-32 pb-24 overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-indigo-50/80 via-white to-white"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-4xl mx-auto">
                <div class="mb-8 inline-flex items-center justify-center space-x-2 rounded-full bg-indigo-100 px-4 py-1">
                    <svg class="h-2 w-2 text-indigo-400" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    <span class="text-sm font-medium text-indigo-700">Trusted by 10,000+ Businesses</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold text-gray-900 tracking-tight lg:leading-tight">
                    Transform Your 
                    <span class="relative inline-block">
                        <span class="relative z-10 text-indigo-600">Product Sourcing</span>
                        <svg aria-hidden="true" class="absolute -bottom-2 w-full" viewBox="0 0 182 17" xmlns="http://www.w3.org/2000/svg">
                            <path d="M181.033 14.977c-40.434-2.384-80.033-2.384-120.467 0-20.217 1.192-40.433 1.192-60.65 0" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="text-indigo-200"/>
                        </svg>
                    </span>
                    Game
                </h1>
                <p class="mt-6 text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                    Streamline your inventory management with AI-powered sourcing, real-time analytics, and automated supplier connections. Perfect for modern e-commerce businesses.
                </p>
                <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('pricing') }}" class="group inline-flex items-center justify-center px-8 py-3.5 text-white bg-indigo-600 hover:bg-indigo-700 rounded-full text-lg font-semibold transition duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                        Get Started Free
                        <svg class="ml-2 -mr-1 w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                    <a href="#demo" class="group inline-flex items-center justify-center px-8 py-3.5 text-gray-700 bg-white hover:bg-gray-50 rounded-full text-lg font-semibold transition duration-300 border-2 border-gray-200 hover:border-gray-300">
                        Watch Demo
                        <svg class="ml-2 -mr-1 w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </a>
                </div>

                <!-- Trust Indicators -->
                <div class="mt-12 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-3">
                    <div class="flex flex-col items-center px-5 py-3 bg-white/60 backdrop-blur-sm rounded-xl">
                        <div class="text-3xl sm:text-4xl font-bold text-indigo-600 tracking-tight">100M+</div>
                        <div class="mt-1 text-sm sm:text-base text-gray-600">Products Available</div>
                    </div>
                    <div class="flex flex-col items-center px-5 py-3 bg-white/60 backdrop-blur-sm rounded-xl">
                        <div class="text-3xl sm:text-4xl font-bold text-indigo-600 tracking-tight">10K+</div>
                        <div class="mt-1 text-sm sm:text-base text-gray-600">Active Users</div>
                    </div>
                    <div class="flex flex-col items-center px-5 py-3 bg-white/60 backdrop-blur-sm rounded-xl">
                        <div class="text-3xl sm:text-4xl font-bold text-indigo-600 tracking-tight">99.9%</div>
                        <div class="mt-1 text-sm sm:text-base text-gray-600">Uptime</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-white relative overflow-hidden">
        <div class="absolute inset-0 -z-10">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-r from-transparent to-indigo-50/30"></div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto">
                <span class="inline-flex items-center rounded-full px-4 py-1 text-sm font-medium bg-indigo-100 text-indigo-700">
                    <svg class="mr-1.5 h-2 w-2 text-indigo-400" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    Platform Features
                </span>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">Everything You Need for Smart Sourcing</h2>
                <p class="mt-4 text-xl text-gray-500">Powerful tools to help you find, manage, and source products efficiently.</p>
            </div>

            <div class="mt-20 grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Feature 1: Product Discovery -->
                <div class="relative group">
                    <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-600 to-pink-600 opacity-0 group-hover:opacity-100 blur transition duration-500"></div>
                    <div class="relative bg-white p-8 rounded-2xl shadow-sm border border-gray-100 transition duration-300 group-hover:border-transparent">
                        <div class="h-12 w-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-300">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-indigo-600 transition duration-300">Smart Product Discovery</h3>
                        <p class="mt-4 text-gray-500">Find the perfect products for your business with our AI-powered search and filtering.</p>
                        <ul class="mt-4 space-y-2">
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Advanced search filters
                            </li>
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Trending products
                            </li>
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Market analysis
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Feature 2: Supplier Management -->
                <div class="relative group">
                    <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-600 to-pink-600 opacity-0 group-hover:opacity-100 blur transition duration-500"></div>
                    <div class="relative bg-white p-8 rounded-2xl shadow-sm border border-gray-100 transition duration-300 group-hover:border-transparent">
                        <div class="h-12 w-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-300">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-indigo-600 transition duration-300">Supplier Management</h3>
                        <p class="mt-4 text-gray-500">Connect and manage relationships with verified suppliers worldwide.</p>
                        <ul class="mt-4 space-y-2">
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Verified suppliers
                            </li>
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Direct messaging
                            </li>
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Performance tracking
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Feature 3: Inventory Management -->
                <div class="relative group">
                    <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-indigo-600 to-pink-600 opacity-0 group-hover:opacity-100 blur transition duration-500"></div>
                    <div class="relative bg-white p-8 rounded-2xl shadow-sm border border-gray-100 transition duration-300 group-hover:border-transparent">
                        <div class="h-12 w-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-300">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-indigo-600 transition duration-300">Inventory Control</h3>
                        <p class="mt-4 text-gray-500">Track and manage your inventory across multiple suppliers and channels.</p>
                        <ul class="mt-4 space-y-2">
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Real-time tracking
                            </li>
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Auto-reordering
                            </li>
                            <li class="flex items-center text-sm text-gray-500">
                                <svg class="h-4 w-4 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Stock alerts
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-12 lg:gap-16 items-center">
                <div class="lg:col-span-6">
                    <h2 class="text-3xl font-bold text-gray-900">Transform Your Business with Smart Integration</h2>
                    <div class="mt-8 space-y-8">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900">Save Time and Resources</h3>
                                <p class="mt-2 text-gray-500">Automate manual tasks and reduce the time spent on product management by up to 80%.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900">Increase Revenue</h3>
                                <p class="mt-2 text-gray-500">Optimize pricing and inventory management to maximize your profit margins.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900">Reduce Errors</h3>
                                <p class="mt-2 text-gray-500">Eliminate manual data entry errors with automated synchronization and validation.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-12 lg:mt-0 lg:col-span-6">
                    <div class="bg-indigo-600 rounded-2xl p-8 lg:p-12">
                        <blockquote class="text-white">
                            <p class="text-xl font-medium">"SyncTrae has transformed how we manage our product catalog. What used to take hours now happens automatically in minutes."</p>
                            <footer class="mt-6">
                                <p class="font-medium">Sarah Thompson</p>
                                <p class="text-indigo-100">CEO at TechRetail</p>
                            </footer>
                        </blockquote>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Integrations Section -->
    <section class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto">
                <span class="inline-flex items-center rounded-full px-4 py-1 text-sm font-medium bg-indigo-100 text-indigo-700">
                    <svg class="mr-1.5 h-2 w-2 text-indigo-400" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    Powerful Integrations
                </span>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">Connect with Your Favorite Platforms</h2>
                <p class="mt-4 text-xl text-gray-500">Seamlessly sync your products across multiple suppliers and sales channels</p>
            </div>

            <!-- Destinations -->
            <div class="mt-20">
                <h3 class="text-xl font-semibold text-center text-gray-900 mb-12">Sales Channels & Marketplaces</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/48/EBay_logo.png" alt="eBay" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">eBay</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg" alt="Amazon" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Amazon</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://cdn.worldvectorlogo.com/logos/shopify.svg" alt="Shopify" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Shopify</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/0c/Walmart_logo.svg" alt="Walmart" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Walmart</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/40/Etsy_logo.svg" alt="Etsy" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Etsy</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://www.woocommerce.com/wp-content/uploads/2020/09/woocommerce_logo_darkblue.svg" alt="WooCommerce" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">WooCommerce</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/Magento_Logo.svg" alt="Magento" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Magento</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/BigCommerce_logo.svg" alt="BigCommerce" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">BigCommerce</span>
                    </div>
                </div>
            </div>

            <!-- Suppliers -->
            <div class="mt-20">
                <h3 class="text-xl font-semibold text-center text-gray-900 mb-12">Supplier Integrations</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/1/1b/Alibaba_Group_logo.svg" alt="Alibaba" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Alibaba</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/9/9e/AliExpress_logo.svg" alt="AliExpress" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">AliExpress</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://1000logos.net/wp-content/uploads/2020/07/Wayfair-Logo.png" alt="Wayfair" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Wayfair</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/76/Costco_Wholesale.svg" alt="Costco" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Costco</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/4a/Homedepot.svg" alt="Home Depot" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Home Depot</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/0e/1688.com_logo.svg" alt="1688" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">1688</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Target_Corporation_logo.svg" alt="Target" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Target</span>
                    </div>
                    <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/64/Lowe%27s_Logo.svg" alt="Lowes" class="h-12 object-contain">
                        <span class="mt-4 text-gray-600">Lowes</span>
                    </div>
                </div>
            </div>

            <!-- Integration Features -->
            <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="h-12 w-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">Quick Integration</h4>
                    <p class="mt-2 text-gray-500">Connect your accounts in minutes with our guided setup process</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="h-12 w-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">Real-time Sync</h4>
                    <p class="mt-2 text-gray-500">Keep your inventory and pricing synchronized across all platforms</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <div class="h-12 w-12 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900">Secure Connection</h4>
                    <p class="mt-2 text-gray-500">Enterprise-grade security for all your integration data</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto">
                <span class="inline-flex items-center rounded-full px-4 py-1 text-sm font-medium bg-indigo-100 text-indigo-700 mb-8">
                    <svg class="mr-1.5 h-2 w-2 text-indigo-400" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    Flexible Pricing
                </span>
                <h2 class="text-3xl font-bold text-gray-900">Choose the Perfect Plan for Your Business</h2>
                <p class="mt-4 text-xl text-gray-500">All plans include a 14-day free trial. No credit card required.</p>
            </div>

            <!-- Pricing Toggle -->
            <div class="mt-12 flex justify-center items-center space-x-3">
                <span class="text-gray-500 font-medium">Monthly</span>
                <button type="button" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600" role="switch" aria-checked="false">
                    <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                        <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true">
                            <svg class="h-3 w-3 text-indigo-600" fill="currentColor" viewBox="0 0 12 12">
                                <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                            </svg>
                        </span>
                    </span>
                </button>
                <span class="text-gray-500 font-medium">Annual <span class="text-indigo-600">(Save 20%)</span></span>
            </div>

            <!-- Pricing Cards -->
            <div class="mt-16 grid grid-cols-1 gap-8 lg:grid-cols-3">
                @php
                    $packages = App\Models\SubscriptionPackage::where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();
                @endphp

                @foreach($packages as $package)
                    <div class="relative flex flex-col bg-white rounded-2xl shadow-lg border {{ $package->name === 'Professional' ? 'border-indigo-600 ring-2 ring-indigo-600 scale-105' : 'border-gray-100' }}">
                        @if($package->name === 'Professional')
                            <div class="absolute -top-5 inset-x-0 flex justify-center">
                                <span class="inline-flex items-center px-4 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700">
                                    Most Popular
                                </span>
                            </div>
                        @endif
                        
                        <div class="p-8">
                            <h3 class="text-2xl font-bold text-gray-900">{{ $package->name }}</h3>
                            <p class="mt-4 text-gray-500 flex-grow">{{ $package->description }}</p>
                            
                            <div class="mt-6 flex items-baseline">
                                <span class="text-5xl font-bold tracking-tight text-gray-900">${{ number_format($package->price, 0) }}</span>
                                <span class="ml-1 text-2xl font-medium text-gray-500">/{{ $package->billing_cycle }}</span>
                            </div>

                            <ul role="list" class="mt-8 space-y-4">
                                @foreach($package->features as $feature => $description)
                                    <li class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-base font-medium text-gray-900">{{ $feature }}</p>
                                            <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-8">
                                <a href="{{ route('filament.admin.auth.register', ['package' => $package->id]) }}" 
                                   class="block w-full text-center px-6 py-4 rounded-xl text-white {{ $package->name === 'Professional' ? 'bg-indigo-600 hover:bg-indigo-700 shadow-lg hover:shadow-xl' : 'bg-gray-800 hover:bg-gray-900' }} transition font-semibold">
                                    Start {{ $package->name }} Trial
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Feature Comparison -->
            <div class="mt-20">
                <h3 class="text-2xl font-bold text-center text-gray-900 mb-12">Compare Plan Features</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-t border-gray-200">
                                <th class="py-5 px-4 text-left text-sm font-medium text-gray-500 w-1/3">Features</th>
                                @foreach($packages as $package)
                                    <th class="py-5 px-4 text-center text-sm font-medium text-gray-900">{{ $package->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="py-5 px-4 text-sm text-gray-500">Number of Products</td>
                                @foreach($packages as $package)
                                    <td class="py-5 px-4 text-center text-sm text-gray-900">
                                        {{ $package->features['Products'] ?? 'Unlimited' }}
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="py-5 px-4 text-sm text-gray-500">API Access</td>
                                @foreach($packages as $package)
                                    <td class="py-5 px-4 text-center">
                                        @if(isset($package->features['API Access']))
                                            <svg class="mx-auto h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            <svg class="mx-auto h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="py-5 px-4 text-sm text-gray-500">Support Level</td>
                                @foreach($packages as $package)
                                    <td class="py-5 px-4 text-center text-sm text-gray-900">
                                        {{ $package->features['Support'] ?? 'Email' }}
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="py-5 px-4 text-sm text-gray-500">Team Members</td>
                                @foreach($packages as $package)
                                    <td class="py-5 px-4 text-center text-sm text-gray-900">
                                        {{ $package->features['Team Members'] ?? '1' }}
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="py-5 px-4 text-sm text-gray-500">Analytics</td>
                                @foreach($packages as $package)
                                    <td class="py-5 px-4 text-center text-sm text-gray-900">
                                        {{ $package->features['Analytics'] ?? 'Basic' }}
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FAQ -->
            <div class="mt-20">
                <h3 class="text-2xl font-bold text-center text-gray-900 mb-12">Frequently Asked Questions</h3>
                <dl class="space-y-8">
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <dt class="text-lg font-semibold text-gray-900">Can I try before I buy?</dt>
                        <dd class="mt-2 text-gray-500">Yes! We offer a 14-day free trial on all plans. No credit card required. You'll have full access to all features during your trial period.</dd>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <dt class="text-lg font-semibold text-gray-900">What payment methods do you accept?</dt>
                        <dd class="mt-2 text-gray-500">We accept all major credit cards (Visa, MasterCard, American Express) and PayPal. For enterprise plans, we also support wire transfers.</dd>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <dt class="text-lg font-semibold text-gray-900">Can I change plans later?</dt>
                        <dd class="mt-2 text-gray-500">Yes, you can upgrade or downgrade your plan at any time. When you upgrade, you'll be prorated for the remainder of your billing period. When you downgrade, changes take effect at the start of your next billing cycle.</dd>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-sm">
                        <dt class="text-lg font-semibold text-gray-900">Do you offer custom enterprise plans?</dt>
                        <dd class="mt-2 text-gray-500">Yes, we offer custom enterprise solutions with dedicated support, custom integrations, and flexible pricing. Contact our sales team to learn more.</dd>
                    </div>
                </dl>
                    </div>

            <!-- Contact Sales -->
            <div class="mt-20 bg-indigo-50 rounded-2xl p-8 lg:p-12">
                <div class="text-center">
                    <h3 class="text-2xl font-bold text-gray-900">Need a Custom Solution?</h3>
                    <p class="mt-4 text-lg text-gray-500">Contact our sales team for enterprise pricing and custom solutions.</p>
                    <div class="mt-8">
                        <a href="#" class="inline-flex items-center justify-center px-8 py-3 text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-lg hover:shadow-xl">
                            Contact Sales
                            <svg class="ml-2 -mr-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-indigo-600 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-white">Ready to Transform Your Business?</h2>
                <p class="mt-4 text-xl text-indigo-100">Start your free trial today. No credit card required.</p>
                <div class="mt-8">
                    <a href="{{ route('pricing') }}" class="inline-block px-8 py-3 text-indigo-600 bg-white hover:bg-indigo-50 rounded-full text-lg font-semibold transition">
                        Get Started Now
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
                        <li><a href="#features" class="text-base text-gray-300 hover:text-white">Features</a></li>
                        <li><a href="#suppliers" class="text-base text-gray-300 hover:text-white">Suppliers</a></li>
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
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">About</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Blog</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Careers</a></li>
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
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.042-.133-2.052-.382-3.016z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
                <p class="mt-8 text-base text-gray-400 md:mt-0 md:order-1">&copy; {{ date('Y') }} SyncTrae. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
