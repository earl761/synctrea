@props([
    'title' => '',
    'breadcrumbs' => [],
])

<section class="relative pt-20 pb-10 bg-gradient-to-b from-indigo-50/70 to-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto" data-aos="fade-up">
            <h1 class="text-4xl font-bold text-gray-900 sm:text-5xl md:text-6xl">
                {{ $title }}
            </h1>
        </div>
        
        <!-- Breadcrumbs -->
        <nav class="mt-8 flex justify-center" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                @foreach($breadcrumbs as $index => $breadcrumb)
                    <li class="flex items-center">
                        @if($index > 0)
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        @endif
                        @if(isset($breadcrumb['url']))
                            <a href="{{ $breadcrumb['url'] }}" class="ml-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                {{ $breadcrumb['label'] }}
                            </a>
                        @else
                            <span class="ml-2 text-sm font-medium text-gray-700">
                                {{ $breadcrumb['label'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
</section>