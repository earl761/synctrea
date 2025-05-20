<x-filament-panels::page>
    @php
        $user = auth()->user();
        $company = $user->company;
        $currentPackage = $company?->subscriptionPackage;
        $isSuperAdmin = $user->isSuperAdmin();
        $allPackages = \App\Models\SubscriptionPackage::all();
    @endphp

    <x-filament::section>
        <div class="space-y-6">
            @if($isSuperAdmin && !$company)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                As a super admin, you can view all available subscription packages but cannot manage subscriptions directly.
                                Company admins can manage their own subscriptions.
                            </p>
                        </div>
                    </div>
                </div>

                <h2 class="text-lg font-medium">Available Packages</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($allPackages as $package)
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium">{{ $package->name }}</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                ${{ number_format($package->price, 2) }} / {{ $package->billing_cycle }}
                            </p>
                            <ul class="mt-4 space-y-2">
                                <li class="flex items-center text-sm">
                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $package->max_users }} Users
                                </li>
                                <li class="flex items-center text-sm">
                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    {{ $package->max_connections }} Connections
                                </li>
                            </ul>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium">Current Subscription</h2>
                        @if($company && $company->isSubscriptionActive())
                            <p class="mt-1 text-sm text-gray-500">
                                Your subscription is active until {{ $company->subscription_ends_at?->format('F j, Y') }}
                            </p>
                        @else
                            <p class="mt-1 text-sm text-gray-500">
                                You don't have an active subscription
                            </p>
                        @endif
                    </div>
                    <div>
                        @if($company && $company->isSubscriptionActive())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>

                @if($currentPackage)
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium">Current Package: {{ $currentPackage->name }}</h3>
                        <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Price</dt>
                                <dd class="mt-1 text-sm text-gray-900">${{ number_format($currentPackage->price, 2) }} / {{ $currentPackage->billing_cycle }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Max Users</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $currentPackage->max_users }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Max Connections</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $currentPackage->max_connections }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($company->subscription_status) }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif

                @if($company && !$company->isSubscriptionActive())
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Your subscription is not active. Subscribe now to access all features.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h2 class="text-lg font-medium mb-4">Available Packages</h2>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($allPackages as $package)
                                <div class="bg-white shadow rounded-lg p-6">
                                    <h3 class="text-lg font-medium">{{ $package->name }}</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        ${{ number_format($package->price, 2) }} / {{ $package->billing_cycle }}
                                    </p>
                                    <ul class="mt-4 space-y-2">
                                        <li class="flex items-center text-sm">
                                            <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $package->max_users }} Users
                                        </li>
                                        <li class="flex items-center text-sm">
                                            <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $package->max_connections }} Connections
                                        </li>
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($company && $company->isSubscriptionActive() && $currentPackage)
                    <x-filament::section class="mt-8">
                        <h2 class="text-lg font-medium">Available Upgrades</h2>
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @php
                                $upgrades = \App\Models\SubscriptionPackage::where('price', '>', $currentPackage->price)->get();
                            @endphp

                            @foreach($upgrades as $package)
                                <div class="bg-white shadow rounded-lg p-6">
                                    <h3 class="text-lg font-medium">{{ $package->name }}</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        ${{ number_format($package->price, 2) }} / {{ $package->billing_cycle }}
                                    </p>
                                    <ul class="mt-4 space-y-2">
                                        <li class="flex items-center text-sm">
                                            <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $package->max_users }} Users
                                        </li>
                                        <li class="flex items-center text-sm">
                                            <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $package->max_connections }} Connections
                                        </li>
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page> 