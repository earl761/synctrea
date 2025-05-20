@extends('components.layouts.app')

@section('content')
<div class="bg-white">
    <!-- Header -->
    <div class="relative bg-gradient-to-r from-indigo-50 to-indigo-100">
        <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Get in Touch</h1>
                <p class="mt-4 text-xl text-gray-500">Have questions? We're here to help.</p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="relative bg-white shadow-xl">
            <div class="grid grid-cols-1 lg:grid-cols-3">
                <!-- Contact information -->
                <div class="relative overflow-hidden bg-indigo-700 py-10 px-6 sm:px-10 xl:p-12">
                    <div class="pointer-events-none absolute inset-0 sm:hidden" aria-hidden="true">
                        <svg class="absolute inset-0 h-full w-full" width="343" height="388" viewBox="0 0 343 388" fill="none" preserveAspectRatio="xMidYMid slice">
                            <path d="M-99 461.107L608.107-246l707.103 707.107-707.103 707.103L-99 461.107z" fill="url(#linear1)" fill-opacity=".1" />
                            <defs>
                                <linearGradient id="linear1" x1="254.553" y1="107.554" x2="961.66" y2="814.66" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#fff"></stop>
                                    <stop offset="1" stop-color="#fff" stop-opacity="0"></stop>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <div class="pointer-events-none absolute top-0 right-0 bottom-0 hidden w-1/2 sm:block lg:hidden" aria-hidden="true">
                        <svg class="absolute inset-0 h-full w-full" width="359" height="339" viewBox="0 0 359 339" fill="none" preserveAspectRatio="xMidYMid slice">
                            <path d="M-161 382.107L546.107-325l707.103 707.107-707.103 707.103L-161 382.107z" fill="url(#linear2)" fill-opacity=".1" />
                            <defs>
                                <linearGradient id="linear2" x1="192.553" y1="28.553" x2="899.66" y2="735.66" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#fff"></stop>
                                    <stop offset="1" stop-color="#fff" stop-opacity="0"></stop>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <div class="relative">
                        <h3 class="text-lg font-medium text-white">Contact Information</h3>
                        <p class="mt-6 max-w-3xl text-base text-indigo-50">Ready to transform your product sourcing? Our team is here to assist you.</p>
                        <dl class="mt-8 space-y-6">
                            <dt><span class="sr-only">Phone number</span></dt>
                            <dd class="flex text-base text-indigo-50">
                                <svg class="h-6 w-6 flex-shrink-0 text-indigo-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span class="ml-3">{{ $settings->company_phone ?? '+1 (555) 123-4567' }}</span>
                            </dd>
                            <dt><span class="sr-only">Email</span></dt>
                            <dd class="flex text-base text-indigo-50">
                                <svg class="h-6 w-6 flex-shrink-0 text-indigo-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="ml-3">{{ $settings->company_email ?? 'support@synctrae.com' }}</span>
                            </dd>
                            <dt><span class="sr-only">Address</span></dt>
                            <dd class="flex text-base text-indigo-50">
                                <svg class="h-6 w-6 flex-shrink-0 text-indigo-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="ml-3">{{ $settings->company_address ?? '123 Market Street, Suite 456, San Francisco, CA 94105' }}</span>
                            </dd>
                        </dl>
                        <ul role="list" class="mt-8 flex space-x-12">
                            <li>
                                <a class="text-indigo-200 hover:text-indigo-100" href="#">
                                    <span class="sr-only">Twitter</span>
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                                    </svg>
                                </a>
                            </li>
                            <li>
                                <a class="text-indigo-200 hover:text-indigo-100" href="#">
                                    <span class="sr-only">LinkedIn</span>
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contact form -->
                <div class="py-10 px-6 sm:px-10 lg:col-span-2 xl:p-12">
                    <h3 class="text-lg font-medium text-gray-900">Send us a message</h3>
                    <form action="{{ route('contact.submit') }}" method="POST" class="mt-6 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                        @csrf
                        <div class="sm:col-span-1">
                            <label for="firstname" class="block text-sm font-medium text-gray-900">First name</label>
                            <div class="mt-1">
                                <input type="text" name="firstname" id="firstname" autocomplete="given-name" required
                                    class="block w-full rounded-md border-gray-300 py-3 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="sm:col-span-1">
                            <label for="lastname" class="block text-sm font-medium text-gray-900">Last name</label>
                            <div class="mt-1">
                                <input type="text" name="lastname" id="lastname" autocomplete="family-name" required
                                    class="block w-full rounded-md border-gray-300 py-3 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="sm:col-span-1">
                            <label for="email" class="block text-sm font-medium text-gray-900">Email</label>
                            <div class="mt-1">
                                <input type="email" name="email" id="email" autocomplete="email" required
                                    class="block w-full rounded-md border-gray-300 py-3 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="sm:col-span-1">
                            <label for="phone" class="block text-sm font-medium text-gray-900">Phone</label>
                            <div class="mt-1">
                                <input type="tel" name="phone" id="phone" autocomplete="tel"
                                    class="block w-full rounded-md border-gray-300 py-3 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="company" class="block text-sm font-medium text-gray-900">Company</label>
                            <div class="mt-1">
                                <input type="text" name="company" id="company" autocomplete="organization"
                                    class="block w-full rounded-md border-gray-300 py-3 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="subject" class="block text-sm font-medium text-gray-900">Subject</label>
                            <div class="mt-1">
                                <input type="text" name="subject" id="subject" required
                                    class="block w-full rounded-md border-gray-300 py-3 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <div class="flex justify-between">
                                <label for="message" class="block text-sm font-medium text-gray-900">Message</label>
                                <span id="message-max" class="text-sm text-gray-500">Max. 500 characters</span>
                            </div>
                            <div class="mt-1">
                                <textarea id="message" name="message" rows="4" required maxlength="500"
                                    class="block w-full rounded-md border-gray-300 py-3 px-4 text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="bg-gray-50">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Frequently Asked Questions</h2>
            <div class="max-w-3xl mx-auto divide-y-2 divide-gray-200">
                <dl class="space-y-6 divide-y divide-gray-200">
                    <div class="pt-6">
                        <dt class="text-lg">
                            <button type="button" class="flex w-full items-start justify-between text-left text-gray-400" aria-controls="faq-0" aria-expanded="false">
                                <span class="font-medium text-gray-900">How quickly can I connect with suppliers?</span>
                                <span class="ml-6 flex items-center">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </span>
                            </button>
                        </dt>
                        <dd class="mt-2 pr-12">
                            <p class="text-base text-gray-500">Once you create an account, you can start connecting with verified suppliers immediately. Most suppliers respond within 24-48 hours.</p>
                        </dd>
                    </div>

                    <div class="pt-6">
                        <dt class="text-lg">
                            <button type="button" class="flex w-full items-start justify-between text-left text-gray-400" aria-controls="faq-1" aria-expanded="false">
                                <span class="font-medium text-gray-900">What kind of support do you offer?</span>
                                <span class="ml-6 flex items-center">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </span>
                            </button>
                        </dt>
                        <dd class="mt-2 pr-12">
                            <p class="text-base text-gray-500">We offer 24/7 customer support via email and chat. Premium members get access to dedicated account managers and priority support.</p>
                        </dd>
                    </div>

                    <div class="pt-6">
                        <dt class="text-lg">
                            <button type="button" class="flex w-full items-start justify-between text-left text-gray-400" aria-controls="faq-2" aria-expanded="false">
                                <span class="font-medium text-gray-900">How do you verify suppliers?</span>
                                <span class="ml-6 flex items-center">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </span>
                            </button>
                        </dt>
                        <dd class="mt-2 pr-12">
                            <p class="text-base text-gray-500">We have a rigorous verification process that includes business license verification, on-site inspections, and quality control assessments.</p>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection