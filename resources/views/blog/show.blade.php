@extends('components.layouts.app')

@section('content')
<article class="bg-white">
    <!-- Featured Image -->
    @if($post->getFirstMedia('featured_image'))
    <div class="relative h-96 w-full">
        <img class="absolute inset-0 h-full w-full object-cover" 
             src="{{ $post->getFirstMedia('featured_image')->getUrl() }}" 
             alt="{{ $post->title }}">
        <div class="absolute inset-0 bg-gradient-to-t from-black opacity-50"></div>
    </div>
    @endif

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Article Header -->
        <div class="mx-auto max-w-3xl pt-10 pb-16">
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
            <h1 class="mt-6 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">{{ $post->title }}</h1>
            <p class="mt-4 text-xl text-gray-500">{{ $post->content_overview }}</p>
        </div>

        <!-- Article Content -->
        <div class="mx-auto max-w-3xl">
            <div class="prose prose-lg prose-indigo mx-auto">
                {!! $post->content !!}
            </div>

            <!-- Tags and Category -->
            <div class="mt-16 flex flex-wrap items-center gap-4">
                <a href="{{ route('blog.category', $post->category->slug) }}" 
                   class="inline-flex items-center rounded-full bg-indigo-100 px-4 py-1 text-sm font-medium text-indigo-700">
                    {{ $post->category->name }}
                </a>
                @foreach($post->tags as $tag)
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Related Posts -->
    @if($relatedPosts->count() > 0)
    <div class="bg-gray-50 py-16 sm:py-24">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Related Articles</h2>
                <p class="mt-2 text-lg leading-8 text-gray-600">Continue reading articles in {{ $post->category->name }}</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-x-8 gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                @foreach($relatedPosts as $relatedPost)
                <article class="flex flex-col items-start">
                    @if($relatedPost->getFirstMedia('featured_image'))
                        <div class="relative w-full">
                            <img src="{{ $relatedPost->getFirstMedia('featured_image')->getUrl('thumb') }}" 
                                 alt="{{ $relatedPost->title }}"
                                 class="aspect-[16/9] w-full rounded-2xl bg-gray-100 object-cover sm:aspect-[2/1] lg:aspect-[3/2]">
                        </div>
                    @endif
                    <div class="max-w-xl">
                        <div class="mt-8 flex items-center gap-x-4 text-xs">
                            <time datetime="{{ $relatedPost->published_at->format('Y-m-d') }}" class="text-gray-500">
                                {{ $relatedPost->published_at->format('M j, Y') }}
                            </time>
                            <a href="{{ route('blog.category', $relatedPost->category->slug) }}" 
                               class="relative z-10 rounded-full bg-gray-50 px-3 py-1.5 font-medium text-gray-600 hover:bg-gray-100">
                                {{ $relatedPost->category->name }}
                            </a>
                        </div>
                        <div class="group relative">
                            <h3 class="mt-3 text-lg font-semibold leading-6 text-gray-900 group-hover:text-gray-600">
                                <a href="{{ route('blog.show', $relatedPost->slug) }}">
                                    {{ $relatedPost->title }}
                                </a>
                            </h3>
                            <p class="mt-5 line-clamp-3 text-sm leading-6 text-gray-600">{{ $relatedPost->content_overview }}</p>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</article>
@endsection