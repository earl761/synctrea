<div>
    <!-- Breadcrumb Hero Section -->
    <x-breadcrumb.hero
        title="Simple, Transparent Pricing"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Pricing']
        ]"
    />

<!-- Pricing Toggle -->
<div class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto" data-aos="fade-up">
            <p class="text-xl text-gray-600">
                Choose the perfect plan for your business needs. All plans include a 14-day free trial.
            </p>
        </div>

        <!-- Pricing Toggle -->
        <div class="mt-8 flex justify-center items-center space-x-3" x-data="{ annual: false }">
            <span class="text-gray-500 font-medium" :class="{ 'text-indigo-600 font-semibold': !annual }">Monthly</span>
            <button 
                @click="annual = !annual"
                type="button" 
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600"
                :class="{ 'bg-indigo-600': annual }"
                role="switch" 
                aria-checked="false">
                <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                      :class="{ 'translate-x-5': annual, 'translate-x-0': !annual }">
                </span>
            </button>
            <span class="text-gray-500 font-medium" :class="{ 'text-indigo-600 font-semibold': annual }">
                Annual <span class="text-indigo-600">(Save 20%)</span>
            </span>
        </div>

        <!-- Pricing Cards -->
        <div class="mt-16 grid grid-cols-1 gap-8 lg:grid-cols-3">
            @foreach($packages as $package)
                <div data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}" class="relative flex flex-col bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    <!-- Header -->
                    <div class="p-6 bg-gradient-to-r {{ $package->name === 'Basic' ? 'from-blue-600 to-blue-800' : ($package->name === 'Professional' ? 'from-indigo-600 to-purple-600' : 'from-purple-600 to-pink-600') }} text-white">
                        <h3 class="text-2xl font-bold">{{ $package->name }}</h3>
                        <div class="mt-4 flex items-baseline">
                            <span x-show="!annual" class="text-4xl font-extrabold tracking-tight">${{ number_format($package->price, 0) }}</span>
                            <span x-show="annual" class="text-4xl font-extrabold tracking-tight">${{ number_format($package->price * 0.8 * 12, 0) }}</span>
                            <span class="ml-1 text-xl font-medium" x-text="annual ? '/year' : '/month'"></span>
                        </div>
                        <p class="mt-2 text-white/80">{{ $package->description }}</p>
                    </div>
                    
                    <!-- Features -->
                    <div class="flex-1 p-6">
                        <ul role="list" class="space-y-4">
                            @php
                                // Handle both string JSON and already decoded array formats
                                $features = is_string($package->features) ? json_decode($package->features, true) : $package->features;
                                $features = $features ?? [];
                            @endphp
                            
                            @foreach($features as $feature => $description)
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 flex-shrink-0 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="ml-2 text-gray-700">
                                        {{ $description }}
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
                                <form class="mt-6 space-y-6" wire:submit.prevent="submitEnterpriseForm">
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
</div>

<!-- CTA Section -->
<section class="bg-indigo-600 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center" data-aos="fade-up">
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
</div>
