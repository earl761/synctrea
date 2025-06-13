<div>
    <!-- Breadcrumb Hero Section -->
    <x-breadcrumb.hero
        title="About SyncTrae"
        :breadcrumbs="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'About Us']
        ]"
    />

<!-- Our Story Section -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-2 lg:gap-16 items-center">
            <!-- Image -->
            <div data-aos="fade-right">
                <img class="rounded-lg shadow-lg object-cover w-full h-auto" src="https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2940&q=80" alt="Team working together">
            </div>
            
            <!-- Content -->
            <div class="mt-10 lg:mt-0" data-aos="fade-left" data-aos-delay="200">
                <h2 class="text-3xl font-extrabold text-gray-900">Our Story</h2>
                <p class="mt-4 text-lg text-gray-600">
                    SyncTrae was founded in 2018 by a team of industry experts who saw a gap in the market for an accessible, all-in-one inventory management solution. 
                </p>
                <p class="mt-4 text-lg text-gray-600">
                    After years of working with small and medium-sized businesses, our founders realized that most inventory systems were either too complex, too expensive, or didn't integrate well with existing tools. They set out to build a platform that would solve these problems.
                </p>
                <p class="mt-4 text-lg text-gray-600">
                    Today, SyncTrae helps thousands of businesses across the globe streamline their inventory processes, reduce costs, and grow their operations with confidence.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center" data-aos="fade-up">
            <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">Our Values</h2>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">What drives us every day</p>
        </div>
        
        <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            <!-- Value 1 -->
            <div class="bg-white rounded-lg p-8 shadow-sm" data-aos="fade-up" data-aos-delay="100">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="mt-6 text-xl font-bold text-gray-900">Innovation</h3>
                <p class="mt-2 text-gray-600">We constantly push the boundaries of what's possible to deliver cutting-edge solutions that help our customers stay ahead.</p>
            </div>
            
            <!-- Value 2 -->
            <div class="bg-white rounded-lg p-8 shadow-sm" data-aos="fade-up" data-aos-delay="200">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="mt-6 text-xl font-bold text-gray-900">Collaboration</h3>
                <p class="mt-2 text-gray-600">We believe that the best solutions come from working closely with our customers and partners to solve real business challenges.</p>
            </div>
            
            <!-- Value 3 -->
            <div class="bg-white rounded-lg p-8 shadow-sm" data-aos="fade-up" data-aos-delay="300">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h3 class="mt-6 text-xl font-bold text-gray-900">Trust</h3>
                <p class="mt-2 text-gray-600">We build our relationships on a foundation of transparency, reliability, and unwavering commitment to our customers' success.</p>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center" data-aos="fade-up">
            <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">Our Team</h2>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">The people behind SyncTrae</p>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                Meet our leadership team who are driving our mission forward.
            </p>
        </div>
        
        <div class="mt-12 space-y-12 lg:grid lg:grid-cols-3 lg:gap-8 lg:space-y-0">
            <!-- Team Member 1 -->
            <div class="space-y-4" data-aos="fade-up" data-aos-delay="100">
                <div class="aspect-w-3 aspect-h-2">
                    <img class="object-cover shadow-lg rounded-lg" src="https://images.unsplash.com/photo-1568602471122-7832951cc4c5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=facearea&facepad=2&w=900&h=900&q=80" alt="Alex Morgan">
                </div>
                <div class="text-center">
                    <h3 class="text-xl font-bold text-gray-900">Alex Morgan</h3>
                    <p class="text-indigo-600 font-medium">Co-founder & CEO</p>
                    <p class="mt-1 text-gray-500">Former Supply Chain Director with 15+ years of experience in global logistics and operations.</p>
                </div>
            </div>
            
            <!-- Team Member 2 -->
            <div class="space-y-4" data-aos="fade-up" data-aos-delay="200">
                <div class="aspect-w-3 aspect-h-2">
                    <img class="object-cover shadow-lg rounded-lg" src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=facearea&facepad=2&w=900&h=900&q=80" alt="Sarah Chen">
                </div>
                <div class="text-center">
                    <h3 class="text-xl font-bold text-gray-900">Sarah Chen</h3>
                    <p class="text-indigo-600 font-medium">Co-founder & CTO</p>
                    <p class="mt-1 text-gray-500">Tech innovator with a background in AI and enterprise software development.</p>
                </div>
            </div>
            
            <!-- Team Member 3 -->
            <div class="space-y-4" data-aos="fade-up" data-aos-delay="300">
                <div class="aspect-w-3 aspect-h-2">
                    <img class="object-cover shadow-lg rounded-lg" src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=facearea&facepad=2&w=900&h=900&q=80" alt="David Wilson">
                </div>
                <div class="text-center">
                    <h3 class="text-xl font-bold text-gray-900">David Wilson</h3>
                    <p class="text-indigo-600 font-medium">Chief Product Officer</p>
                    <p class="mt-1 text-gray-500">Product visionary who previously led product teams at multiple successful SaaS companies.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Investors & Partners -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center" data-aos="fade-up">
            <h2 class="text-3xl font-extrabold text-gray-900">Trusted by leading companies</h2>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                Our partners and investors who support our journey.
            </p>
        </div>
        
        <div class="mt-10 grid grid-cols-2 gap-8 md:grid-cols-4">
            <div class="col-span-1 flex justify-center py-8 px-8 bg-white rounded-lg" data-aos="zoom-in">
                <img class="max-h-12 opacity-60 hover:opacity-100 transition" src="https://tailwindui.com/img/logos/tuple-logo-gray-400.svg" alt="Tuple">
            </div>
            <div class="col-span-1 flex justify-center py-8 px-8 bg-white rounded-lg" data-aos="zoom-in" data-aos-delay="100">
                <img class="max-h-12 opacity-60 hover:opacity-100 transition" src="https://tailwindui.com/img/logos/mirage-logo-gray-400.svg" alt="Mirage">
            </div>
            <div class="col-span-1 flex justify-center py-8 px-8 bg-white rounded-lg" data-aos="zoom-in" data-aos-delay="200">
                <img class="max-h-12 opacity-60 hover:opacity-100 transition" src="https://tailwindui.com/img/logos/statickit-logo-gray-400.svg" alt="StaticKit">
            </div>
            <div class="col-span-1 flex justify-center py-8 px-8 bg-white rounded-lg" data-aos="zoom-in" data-aos-delay="300">
                <img class="max-h-12 opacity-60 hover:opacity-100 transition" src="https://tailwindui.com/img/logos/transistor-logo-gray-400.svg" alt="Transistor">
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-indigo-600 rounded-2xl shadow-xl overflow-hidden" data-aos="fade-up">
            <div class="pt-10 pb-12 px-6 sm:pt-16 sm:px-16 lg:py-16 lg:pr-0 xl:py-20 xl:px-20 lg:flex lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                        <span class="block">Join our journey</span>
                    </h2>
                    <p class="mt-4 text-lg leading-6 text-indigo-100">
                        We're always looking for talented people to join our team.
                    </p>
                    <div class="mt-8 flex space-x-4">
                        <a href="#" class="bg-white border border-transparent rounded-full shadow px-8 py-3 inline-flex items-center text-base font-medium text-indigo-600 hover:bg-indigo-50">
                            View Open Positions
                        </a>
                        <a href="{{ route('contact') }}" class="border border-white rounded-full px-8 py-3 inline-flex items-center text-base font-medium text-white hover:bg-indigo-500">
                            Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</div>
