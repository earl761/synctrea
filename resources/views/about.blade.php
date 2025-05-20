@extends('components.layouts.app')

@section('content')
<div class="bg-white">
    <!-- Hero section -->
    <div class="relative bg-gradient-to-r from-indigo-50 to-indigo-100">
        <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Our Mission</h1>
                <p class="mt-4 text-xl text-gray-500">Transforming global trade through intelligent automation</p>
            </div>
        </div>
    </div>

    <!-- Story section -->
    <div class="relative bg-white py-16">
        <div class="mx-auto max-w-md px-6 sm:max-w-3xl lg:max-w-7xl lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-8">
                <div class="space-y-5">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Our Story</h2>
                    <p class="text-xl text-gray-500">Founded in 2023, SyncTrae emerged from a simple observation: product sourcing shouldn't be complicated.</p>
                    <p class="text-gray-500">Our platform bridges the gap between suppliers and businesses, making global trade accessible to everyone. By leveraging cutting-edge technology and deep industry expertise, we're revolutionizing how businesses connect with manufacturers and manage their supply chains.</p>
                </div>
                <div class="aspect-w-3 aspect-h-2">
                    <img class="rounded-lg object-cover shadow-lg" src="{{ asset('images/about/office.jpg') }}" alt="Our office">
                </div>
            </div>
        </div>
    </div>

    <!-- Values section -->
    <div class="bg-indigo-700">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Our Values</h2>
                <p class="mt-4 text-lg leading-6 text-indigo-200">The principles that guide everything we do</p>
            </div>
            <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <div class="pt-6">
                    <div class="flow-root bg-indigo-800 rounded-lg px-6 pb-8">
                        <div class="-mt-6">
                            <div class="inline-flex items-center justify-center p-3 bg-indigo-500 rounded-md shadow-lg">
                                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="mt-8 text-lg font-medium text-white tracking-tight">Innovation</h3>
                            <p class="mt-5 text-base text-indigo-200">We constantly push boundaries to create cutting-edge solutions that simplify complex processes.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6">
                    <div class="flow-root bg-indigo-800 rounded-lg px-6 pb-8">
                        <div class="-mt-6">
                            <div class="inline-flex items-center justify-center p-3 bg-indigo-500 rounded-md shadow-lg">
                                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="mt-8 text-lg font-medium text-white tracking-tight">Trust</h3>
                            <p class="mt-5 text-base text-indigo-200">We build lasting relationships through transparency, reliability, and unwavering integrity.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6">
                    <div class="flow-root bg-indigo-800 rounded-lg px-6 pb-8">
                        <div class="-mt-6">
                            <div class="inline-flex items-center justify-center p-3 bg-indigo-500 rounded-md shadow-lg">
                                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <h3 class="mt-8 text-lg font-medium text-white tracking-tight">Global Impact</h3>
                            <p class="mt-5 text-base text-indigo-200">We're committed to making international trade accessible and sustainable for businesses worldwide.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team section -->
    <div class="bg-white">
        <div class="mx-auto max-w-7xl py-12 px-6 lg:px-8 lg:py-24">
            <div class="space-y-12">
                <div class="space-y-5 sm:space-y-4 md:max-w-xl lg:max-w-3xl xl:max-w-none">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Our Leadership Team</h2>
                    <p class="text-xl text-gray-500">Meet the experts behind SyncTrae's innovation and success.</p>
                </div>
                <ul role="list" class="space-y-12 sm:grid sm:grid-cols-2 sm:gap-x-6 sm:gap-y-12 sm:space-y-0 lg:grid-cols-3 lg:gap-x-8">
                    @foreach($teamMembers as $member)
                    <li>
                        <div class="space-y-4">
                            <div class="aspect-w-3 aspect-h-2">
                                <img class="rounded-lg object-cover shadow-lg" src="{{ $member->photo_url }}" alt="{{ $member->name }}">
                            </div>
                            <div class="space-y-2">
                                <div class="space-y-1 text-lg font-medium leading-6">
                                    <h3>{{ $member->name }}</h3>
                                    <p class="text-indigo-600">{{ $member->position }}</p>
                                </div>
                                <ul role="list" class="flex space-x-5">
                                    @if($member->twitter)
                                    <li>
                                        <a href="{{ $member->twitter }}" class="text-gray-400 hover:text-gray-500">
                                            <span class="sr-only">Twitter</span>
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6.29 18.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0020 3.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.073 4.073 0 01.8 7.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84" />
                                            </svg>
                                        </a>
                                    </li>
                                    @endif
                                    @if($member->linkedin)
                                    <li>
                                        <a href="{{ $member->linkedin }}" class="text-gray-400 hover:text-gray-500">
                                            <span class="sr-only">LinkedIn</span>
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.338 16.338H13.67V12.16c0-.995-.017-2.277-1.387-2.277-1.39 0-1.601 1.086-1.601 2.207v4.248H8.014v-8.59h2.559v1.174h.037c.356-.675 1.227-1.387 2.526-1.387 2.703 0 3.203 1.778 3.203 4.092v4.711zM5.005 6.575a1.548 1.548 0 11-.003-3.096 1.548 1.548 0 01.003 3.096zm-1.337 9.763H6.34v-8.59H3.667v8.59zM17.668 1H2.328C1.595 1 1 1.581 1 2.298v15.403C1 18.418 1.595 19 2.328 19h15.34c.734 0 1.332-.582 1.332-1.299V2.298C19 1.581 18.402 1 17.668 1z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="bg-indigo-800">
        <div class="mx-auto max-w-7xl py-12 px-6 sm:py-16 lg:px-8 lg:py-20">
            <div class="mx-auto max-w-4xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Trusted by businesses worldwide</h2>
                <p class="mt-3 text-xl text-indigo-200 sm:mt-4">Our platform connects thousands of businesses with verified suppliers daily.</p>
            </div>
            <dl class="mt-10 text-center sm:mx-auto sm:grid sm:max-w-3xl sm:grid-cols-3 sm:gap-8">
                <div class="flex flex-col">
                    <dt class="order-2 mt-2 text-lg font-medium leading-6 text-indigo-200">Suppliers</dt>
                    <dd class="order-1 text-5xl font-bold tracking-tight text-white">2,000+</dd>
                </div>
                <div class="mt-10 flex flex-col sm:mt-0">
                    <dt class="order-2 mt-2 text-lg font-medium leading-6 text-indigo-200">Countries</dt>
                    <dd class="order-1 text-5xl font-bold tracking-tight text-white">50+</dd>
                </div>
                <div class="mt-10 flex flex-col sm:mt-0">
                    <dt class="order-2 mt-2 text-lg font-medium leading-6 text-indigo-200">Success Rate</dt>
                    <dd class="order-1 text-5xl font-bold tracking-tight text-white">98%</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection