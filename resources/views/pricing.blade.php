<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pricing - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="/" class="text-red-500 font-bold text-xl">SyncTrae</a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/" class="text-gray-600 hover:text-gray-900">Home</a>
                        @auth
                            <a href="{{ url('/admin') }}" class="text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-md transition">Dashboard</a>
                        @else
                            <a href="{{ route('filament.admin.auth.login') }}" class="text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-md transition">Log in</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">Choose Your Plan</h1>
            </div>
        </header>

        <!-- Pricing Section -->
        <main>
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($packages as $package)
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <div class="px-6 py-8">
                                <h3 class="text-2xl font-semibold text-gray-900">{{ $package->name }}</h3>
                                <p class="mt-4 text-gray-500">{{ $package->description }}</p>
                                <p class="mt-8">
                                    <span class="text-4xl font-extrabold text-gray-900">${{ number_format($package->price, 2) }}</span>
                                    <span class="text-base font-medium text-gray-500">/month</span>
                                </p>

                                <ul role="list" class="mt-8 space-y-4">
                                    @foreach($package->features as $feature => $description)
                                        <li class="flex items-center">
                                            <svg class="flex-shrink-0 h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="ml-3 text-gray-700">
                                                <strong>{{ $feature }}:</strong> {{ $description }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="mt-8">
                                    <a href="{{ route('filament.admin.auth.register', ['package' => $package->id]) }}" 
                                       class="block w-full bg-indigo-600 text-white text-center px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">
                                        Get Started
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </main>
    </div>
</body>
</html> 