@extends('components.layouts.app')

@section('content')
<div class="bg-white">
    <!-- Hero section -->
    <div class="relative bg-gradient-to-r from-indigo-50 to-indigo-100">
        <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Find Verified Suppliers</h1>
                <p class="mt-4 text-xl text-gray-500">Connect with trusted manufacturers and wholesalers worldwide</p>
                
                <!-- Search Bar -->
                <div class="mt-8 max-w-xl mx-auto">
                    <form class="sm:flex">
                        <div class="flex-1">
                            <label for="search" class="sr-only">Search suppliers</label>
                            <input type="text" name="search" id="search" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Search by name, category, or location">
                        </div>
                        <button type="submit" 
                            class="mt-3 sm:mt-0 sm:ml-3 inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Search
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter and Results -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            <!-- Filters -->
            <div class="hidden lg:block lg:col-span-3">
                <div class="space-y-6">
                    <!-- Categories -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Categories</h3>
                        <div class="mt-4 space-y-4">
                            @foreach($categories as $category)
                            <div class="flex items-center">
                                <input type="checkbox" name="category[]" value="{{ $category->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label class="ml-3 text-sm text-gray-600">{{ $category->name }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Regions -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Region</h3>
                        <div class="mt-4 space-y-4">
                            @foreach($regions as $region)
                            <div class="flex items-center">
                                <input type="checkbox" name="region[]" value="{{ $region }}"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label class="ml-3 text-sm text-gray-600">{{ $region }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Verification Level -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Verification</h3>
                        <div class="mt-4 space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" name="verified" value="1"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label class="ml-3 text-sm text-gray-600">Verified Suppliers</label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="premium" value="1"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label class="ml-3 text-sm text-gray-600">Premium Partners</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="mt-6 lg:col-span-9 lg:mt-0">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($suppliers as $supplier)
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                @if($supplier->verified)
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                @endif
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $supplier->name }}</h3>
                                    <p class="text-sm text-gray-500">{{ $supplier->location }}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500">{{ $supplier->description }}</p>
                            </div>
                            <div class="mt-4">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($supplier->specialties as $specialty)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        {{ $specialty }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-6">
                                <a href="{{ route('suppliers.show', $supplier->id) }}" 
                                   class="block w-full text-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                    View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $suppliers->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-indigo-700">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                <span class="block">Ready to grow your business?</span>
                <span class="block text-indigo-200">Join our supplier network today.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="{{ route('suppliers.register') }}"
                        class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50">
                        Register as Supplier
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection