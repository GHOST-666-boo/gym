@props(['items'])

<nav class="bg-gray-50 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center space-x-2 text-sm">
            @foreach($items as $index => $item)
                <li class="flex items-center">
                    @if($index > 0)
                        <svg class="h-4 w-4 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                    
                    @if(isset($item['url']) && !$loop->last)
                        <a href="{{ $item['url'] }}" class="text-gray-500 hover:text-blue-600 transition-colors duration-200">
                            {{ $item['title'] }}
                        </a>
                    @else
                        <span class="{{ $loop->last ? 'text-gray-900 font-medium' : 'text-gray-500' }}">
                            {{ $item['title'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</nav>