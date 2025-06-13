<div>
    <!-- Hero Section -->
    <section class="relative pt-16 pb-32 bg-gradient-to-b from-indigo-50 to-white overflow-hidden">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Left Column - Text -->
                <div data-aos="fade-right" data-aos-delay="100">
                    <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl md:text-6xl">
                        Smart <span class="text-indigo-600">Inventory Sourcing</span> & Management
                    </h1>
                    <p class="mt-6 text-xl text-gray-600">
                        Streamline your supply chain, reduce costs, and gain complete visibility with our all-in-one inventory management platform.
                    </p>
                    <div class="mt-10 flex flex-wrap gap-4">
                        <a href="{{ route('filament.admin.auth.register') }}" class="px-8 py-3 text-base font-medium rounded-full text-white bg-indigo-600 hover:bg-indigo-700 transition">
                            Get Started
                        </a>
                        <a href="#features" class="px-8 py-3 text-base font-medium rounded-full text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition">
                            Learn More
                        </a>
                    </div>
                    <div class="mt-8 flex items-center">
                        <div class="flex -space-x-1 overflow-hidden">
                            <img class="inline-block h-6 w-6 rounded-full ring-2 ring-white" src="https://randomuser.me/api/portraits/women/17.jpg" alt="">
                            <img class="inline-block h-6 w-6 rounded-full ring-2 ring-white" src="https://randomuser.me/api/portraits/men/4.jpg" alt="">
                            <img class="inline-block h-6 w-6 rounded-full ring-2 ring-white" src="https://randomuser.me/api/portraits/women/3.jpg" alt="">
                        </div>
                        <span class="ml-2 text-sm text-gray-500">Trusted by 1,000+ businesses</span>
                    </div>
                </div>
                
                <!-- Right Column - Image -->
                <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2" data-aos="fade-left" data-aos-delay="200">
                    <img class="h-56 w-full object-cover sm:h-72 md:h-96 lg:w-full lg:h-full rounded-xl shadow-lg" src="https://images.unsplash.com/photo-1580674285054-bed31e145f59?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2940&q=80" alt="Inventory management dashboard">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center" data-aos="fade-up">
                <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">Features</h2>
                <p class="mt-2 text-3xl font-extrabold text-gray-900 sm:text-4xl">Everything you need to succeed</p>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                    Powerful tools designed to help businesses of all sizes manage their inventory and supply chain effectively.
                </p>
            </div>

            <div class="mt-16">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-gray-50 p-8 rounded-xl shadow-sm" data-aos="fade-up" data-aos-delay="100">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-gray-900">Real-time Analytics</h3>
                        <p class="mt-2 text-gray-600">Get actionable insights with real-time analytics and detailed reporting across all your inventory processes.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-gray-50 p-8 rounded-xl shadow-sm" data-aos="fade-up" data-aos-delay="200">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-gray-900">Inventory Tracking</h3>
                        <p class="mt-2 text-gray-600">Keep track of stock levels across multiple warehouses with powerful tracking tools and alerts.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-gray-50 p-8 rounded-xl shadow-sm" data-aos="fade-up" data-aos-delay="300">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-gray-900">Supplier Network</h3>
                        <p class="mt-2 text-gray-600">Access thousands of verified suppliers and compare prices to find the best deals for your business.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-gray-50 p-8 rounded-xl shadow-sm" data-aos="fade-up" data-aos-delay="400">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-gray-900">Automated Ordering</h3>
                        <p class="mt-2 text-gray-600">Set up automatic reordering based on minimum stock levels to ensure you never run out of essential items.</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="bg-gray-50 p-8 rounded-xl shadow-sm" data-aos="fade-up" data-aos-delay="500">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-gray-900">Order Management</h3>
                        <p class="mt-2 text-gray-600">Streamline your order processing with automated workflows from purchase order to fulfillment.</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="bg-gray-50 p-8 rounded-xl shadow-sm" data-aos="fade-up" data-aos-delay="600">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-gray-900">Customization</h3>
                        <p class="mt-2 text-gray-600">Tailor the platform to your specific business needs with customizable fields, reports, and workflows.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="bg-indigo-700 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center" data-aos="fade-up">
                <h2 class="text-base text-indigo-200 font-semibold tracking-wide uppercase">Testimonials</h2>
                <p class="mt-2 text-3xl font-extrabold text-white sm:text-4xl">Trusted by businesses worldwide</p>
            </div>
            
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-white rounded-lg p-6 shadow-lg" data-aos="fade-up" data-aos-delay="100">
                    <div class="h-12">
                        <svg class="h-12 text-indigo-400" fill="currentColor" viewBox="0 0 32 32">
                            <path d="M9.352 4C4.456 7.456 1 13.12 1 19.36c0 5.088 3.072 8.064 6.624 8.064 3.36 0 5.856-2.688 5.856-5.856 0-3.168-2.208-5.472-5.088-5.472-.576 0-1.344.096-1.536.192.48-3.264 3.552-7.104 6.624-9.024L9.352 4zm16.512 0c-4.8 3.456-8.256 9.12-8.256 15.36 0 5.088 3.072 8.064 6.624 8.064 3.264 0 5.856-2.688 5.856-5.856 0-3.168-2.304-5.472-5.184-5.472-.576 0-1.248.096-1.44.192.48-3.264 3.456-7.104 6.528-9.024L25.864 4z" />
                        </svg>
                    </div>
                    <p class="mt-5 text-lg text-gray-600">SyncTrae has transformed our inventory management process. We've reduced stockouts by 75% and improved our order accuracy to 99.8%.</p>
                    <div class="mt-6">
                        <p class="font-medium text-gray-900">Sarah Johnson</p>
                        <p class="text-sm text-gray-500">Operations Director, Retail Co.</p>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-white rounded-lg p-6 shadow-lg" data-aos="fade-up" data-aos-delay="200">
                    <div class="h-12">
                        <svg class="h-12 text-indigo-400" fill="currentColor" viewBox="0 0 32 32">
                            <path d="M9.352 4C4.456 7.456 1 13.12 1 19.36c0 5.088 3.072 8.064 6.624 8.064 3.36 0 5.856-2.688 5.856-5.856 0-3.168-2.208-5.472-5.088-5.472-.576 0-1.344.096-1.536.192.48-3.264 3.552-7.104 6.624-9.024L9.352 4zm16.512 0c-4.8 3.456-8.256 9.12-8.256 15.36 0 5.088 3.072 8.064 6.624 8.064 3.264 0 5.856-2.688 5.856-5.856 0-3.168-2.304-5.472-5.184-5.472-.576 0-1.248.096-1.44.192.48-3.264 3.456-7.104 6.528-9.024L25.864 4z" />
                        </svg>
                    </div>
                    <p class="mt-5 text-lg text-gray-600">The supplier network feature saved us thousands in procurement costs. Being able to compare suppliers in real-time is a game-changer.</p>
                    <div class="mt-6">
                        <p class="font-medium text-gray-900">Michael Chen</p>
                        <p class="text-sm text-gray-500">Procurement Manager, Tech Industries</p>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-white rounded-lg p-6 shadow-lg" data-aos="fade-up" data-aos-delay="300">
                    <div class="h-12">
                        <svg class="h-12 text-indigo-400" fill="currentColor" viewBox="0 0 32 32">
                            <path d="M9.352 4C4.456 7.456 1 13.12 1 19.36c0 5.088 3.072 8.064 6.624 8.064 3.36 0 5.856-2.688 5.856-5.856 0-3.168-2.208-5.472-5.088-5.472-.576 0-1.344.096-1.536.192.48-3.264 3.552-7.104 6.624-9.024L9.352 4zm16.512 0c-4.8 3.456-8.256 9.12-8.256 15.36 0 5.088 3.072 8.064 6.624 8.064 3.264 0 5.856-2.688 5.856-5.856 0-3.168-2.304-5.472-5.184-5.472-.576 0-1.248.096-1.44.192.48-3.264 3.456-7.104 6.528-9.024L25.864 4z" />
                        </svg>
                    </div>
                    <p class="mt-5 text-lg text-gray-600">Implementation was quick and the customer support team was there every step of the way. We saw ROI within the first month.</p>
                    <div class="mt-6">
                        <p class="font-medium text-gray-900">Emily Rodriguez</p>
                        <p class="text-sm text-gray-500">CEO, Growth Startups</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-indigo-600 rounded-2xl shadow-xl overflow-hidden" data-aos="fade-up">
                <div class="pt-10 pb-12 px-6 sm:pt-16 sm:px-16 lg:py-16 lg:pr-0 xl:py-20 xl:px-20">
                    <div class="lg:self-center">
                        <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                            <span class="block">Ready to streamline your inventory?</span>
                        </h2>
                        <p class="mt-4 text-lg leading-6 text-indigo-100">
                            Start your free 14-day trial today. No credit card required.
                        </p>
                        <a href="{{ route('filament.admin.auth.register') }}" class="mt-8 bg-white border border-transparent rounded-full shadow px-8 py-3 inline-flex items-center text-base font-medium text-indigo-600 hover:bg-indigo-50">
                            Get Started Free
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

