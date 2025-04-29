<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Supplier Integration & Product Management</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-900">
    <!-- Navigation -->
    <nav class="fixed w-full bg-gray-900/95 backdrop-blur-sm z-50 border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="#" class="text-red-500 font-bold text-xl">SyncTrae</a>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-300 hover:text-white transition">Features</a>
                    <a href="#integrations" class="text-gray-300 hover:text-white transition">Integrations</a>
                    <a href="#contact" class="text-gray-300 hover:text-white transition">Contact</a>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/admin') }}" class="inline-flex items-center px-4 py-2 bg-red-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-red-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition">Log in</a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden pt-32 pb-24 bg-gradient-to-br from-gray-900 to-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                <div class="sm:text-center md:max-w-2xl md:mx-auto lg:col-span-6 lg:text-left">
                    <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl">
                        <span class="block">Streamline Your</span>
                        <span class="block text-red-500">Product Management</span>
                    </h1>
                    <p class="mt-3 text-base text-gray-300 sm:mt-5 sm:text-xl lg:text-lg xl:text-xl">
                        Automate your supplier integrations, manage products efficiently, and scale your business with our comprehensive platform.
                    </p>
                    <div class="mt-8 sm:max-w-lg sm:mx-auto sm:text-center lg:text-left">
                        <a href="#contact" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition">
                            Get Started
                        </a>
                    </div>
                </div>
                <div class="mt-12 relative sm:max-w-lg sm:mx-auto lg:mt-0 lg:max-w-none lg:mx-0 lg:col-span-6 lg:flex lg:items-center">
                    <img src="/images/hero-illustration.svg" alt="Hero Illustration" class="w-full lg:absolute lg:right-0 lg:h-full lg:w-auto lg:max-w-none">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-white sm:text-4xl">Powerful Features</h2>
                <p class="mt-4 text-xl text-gray-400">Everything you need to manage your products and suppliers</p>
            </div>

            <div class="mt-16">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div class="relative group bg-gray-800 p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-red-500 rounded-lg overflow-hidden hover:scale-105 transition-transform duration-300">
                        <div class="h-16 w-16 bg-red-800/20 flex items-center justify-center rounded-full mb-8">
                            <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-white">Supplier Integration</h3>
                        <p class="mt-2 text-sm text-gray-400">Connect with major suppliers seamlessly. Automate product imports and inventory syncs.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="relative group bg-gray-800 p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-red-500 rounded-lg overflow-hidden hover:scale-105 transition-transform duration-300">
                        <div class="h-16 w-16 bg-red-800/20 flex items-center justify-center rounded-full mb-8">
                            <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-8-6h16" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-white">Product Management</h3>
                        <p class="mt-2 text-sm text-gray-400">Centralize your product catalog with advanced features for pricing and inventory tracking.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="relative group bg-gray-800 p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-red-500 rounded-lg overflow-hidden hover:scale-105 transition-transform duration-300">
                        <div class="h-16 w-16 bg-red-800/20 flex items-center justify-center rounded-full mb-8">
                            <svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-white">Sync Logging</h3>
                        <p class="mt-2 text-sm text-gray-400">Monitor all synchronization activities with detailed logs and tracking.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Integrations Section -->
    <section id="integrations" class="py-20 bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-white sm:text-4xl">Integrated Suppliers</h2>
                <p class="mt-4 text-xl text-gray-400">Connect with leading suppliers in the industry</p>
            </div>

            <div class="mt-16">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                    <!-- Amazon SP-API Integration Card -->
                    <div class="group relative bg-gray-900 p-8 rounded-lg overflow-hidden transition-all duration-300 hover:bg-gray-700 hover:scale-105">
                        <div class="flex flex-col items-center">
                            <svg class="h-16 w-16 text-white mb-4" viewBox="0 0 448 512">
                                <path fill="currentColor" d="M257.2 162.7c-48.7 1.8-169.5 15.5-169.5 117.5 0 109.5 138.3 114 183.5 43.2 6.5 10.2 35.4 37.5 45.3 46.8l56.8-56S341 288.9 341 261.4V114.3C341 89 316.5 32 228.7 32 140.7 32 94 87 94 136.3l73.5 6.8c16.3-49.5 54.2-49.5 54.2-49.5 40.7-.1 35.5 29.8 35.5 69.1zm0 86.8c0 80-84.2 68-84.2 17.2 0-47.2 50.5-56.7 84.2-57.8v40.6zm136 163.5c-7.7 10-70 67-174.5 67S34.2 408.5 9.7 379c-6.8-7.7 1-11.3 5.5-8.3C88.5 415.2 203 488.5 387.7 401c7.5-3.7 13.3 2 5.5 12zm39.8 2.2c-6.5 15.8-16 26.8-21.2 31-5.5 4.5-9.5 2.7-6.5-3.8s19.3-46.5 12.7-55c-6.5-8.3-37-4.3-48-3.2-10.8 1-13 2-14-.3-2.3-5.7 21.7-15.5 37.5-17.5 15.7-1.8 41-.8 46 5.7 3.7 5.1 0 27.1-6.5 43.1"/>
                            </svg>
                            <h3 class="text-xl font-bold text-white mb-2">Amazon SP-API</h3>
                            <p class="text-gray-400 text-center">Full integration with Amazon's Selling Partner API for seamless marketplace management</p>
                        </div>
                    </div>

                    <!-- D&H Integration Card -->
                    <div class="group relative bg-gray-900 p-8 rounded-lg overflow-hidden transition-all duration-300 hover:bg-gray-700 hover:scale-105">
                        <div class="flex flex-col items-center">
                            <svg class="h-16 w-16 text-white mb-4" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.88-11.71L10 14.17l-1.88-1.88a.996.996 0 1 0-1.41 1.41l2.59 2.59c.39.39 1.02.39 1.41 0L17.3 9.7a.996.996 0 0 0 0-1.41c-.39-.39-1.03-.39-1.42 0z"/>
                            </svg>
                            <h3 class="text-xl font-bold text-white mb-2">D&H Distribution</h3>
                            <p class="text-gray-400 text-center">Direct integration with D&H's distribution network for streamlined product sourcing</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-lg mx-auto">
                <h2 class="text-3xl font-extrabold text-white text-center">Contact Us</h2>
                <p class="mt-4 text-xl text-gray-400 text-center">Get in touch with our team</p>
                <div class="mt-8 bg-gray-800 p-8 rounded-lg">
                    <form action="#" method="POST" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-400">Name</label>
                            <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-red-500 focus:ring-red-500">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-400">Email</label>
                            <input type="email" name="email" id="email" class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-red-500 focus:ring-red-500">
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-400">Message</label>
                            <textarea name="message" id="message" rows="4" class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-white focus:border-red-500 focus:ring-red-500"></textarea>
                        </div>
                        <div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Solutions</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Supplier Integration</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Product Management</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white">Pricing Rules</a></li>
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
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
                <p class="mt-8 text-base text-gray-400 md:mt-0 md:order-1">&copy; 2024 SyncTrae. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
