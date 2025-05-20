@extends('components.layouts.app')

@section('content')
<div class="bg-white">
    <!-- Hero section -->
    <div class="relative bg-gradient-to-r from-indigo-50 to-indigo-100">
        <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">Latest Updates & Insights</h1>
                <p class="mt-4 text-xl text-gray-500">Stay informed about product sourcing trends and strategies</p>
            </div>
        </div>
    </div>

    <!-- Featured posts -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach($featuredPosts as $post)
                <article class="flex flex-col overflow-hidden rounded-lg shadow-lg">
                    @if($post->getFirstMedia('featured_image'))
                        <div class="flex-shrink-0">
                            <img class="h-48 w-full object-cover" 
                                src="{{ $post->getFirstMedia('featured_image')->getUrl('medium') }}" 
                                alt="{{ $post->title }}">
                        </div>
                    @endif
                    <div class="flex flex-1 flex-col justify-between bg-white p-6">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-indigo-600">
                                <a href="{{ route('blog.category', $post->category->slug) }}" class="hover:underline">
                                    {{ $post->category->name }}
                                </a>
                            </p>
                            <a href="{{ route('blog.show', $post->slug) }}" class="mt-2 block">
                                <h3 class="text-xl font-semibold text-gray-900">{{ $post->title }}</h3>
                                <p class="mt-3 text-base text-gray-500">{{ $post->content_overview }}</p>
                            </a>
                        </div>
                        <div class="mt-6 flex items-center">
                            <div class="flex-shrink-0">
                                <span class="sr-only">{{ $post->author->firstname }} {{ $post->author->lastname }}</span>
                                <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center">
                                    <span class="text-white font-medium text-sm">
                                        {{ substr($post->author->firstname, 0, 1) }}{{ substr($post->author->lastname, 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $post->author->firstname }} {{ $post->author->lastname }}
                                </p>
                                <div class="flex space-x-1 text-sm text-gray-500">
                                    <time datetime="{{ $post->published_at->format('Y-m-d') }}">
                                        {{ $post->published_at->format('M j, Y') }}
                                    </time>
                                    <span aria-hidden="true">&middot;</span>
                                    <span>{{ $post->reading_time }} min read</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <!-- Categories -->
    <div class="bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Browse by Category</h2>
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach($categories as $category)
                    <a href="{{ route('blog.category', $category->slug) }}" 
                       class="relative flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm hover:border-indigo-600 transition">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-medium text-gray-900">{{ $category->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $category->posts_count }} articles</p>
                        </div>
                        <div class="flex-shrink-0 text-gray-400">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Newsletter -->
    <div class="bg-indigo-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Get the latest updates</h2>
                <p class="mt-4 text-lg leading-6 text-indigo-200">Sign up for our newsletter to stay informed about new articles and industry insights.</p>
                <form class="mt-8 sm:flex sm:justify-center">
                    <div class="sm:flex-1 max-w-md mx-auto">
                        <label for="email-address" class="sr-only">Email address</label>
                        <input id="email-address" type="email" required class="block w-full rounded-md border-0 px-4 py-3 text-base text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-300" placeholder="Enter your email">
                    </div>
                    <div class="mt-4 sm:mt-0 sm:ml-3">
                        <button type="submit" class="block w-full rounded-md border border-transparent bg-indigo-500 px-5 py-3 text-base font-medium text-white shadow hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2 sm:px-10">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection