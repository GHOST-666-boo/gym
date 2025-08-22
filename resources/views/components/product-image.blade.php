@if($lazy)
    <img 
        src="{{ $imageAttributes['src'] }}" 
        data-src="{{ $imageAttributes['data-src'] }}" 
        alt="{{ $imageAttributes['alt'] }}" 
        class="{{ $imageAttributes['class'] }} lazy-image" 
        loading="{{ $imageAttributes['loading'] }}" 
        decoding="{{ $imageAttributes['decoding'] }}"
        onerror="{{ $imageAttributes['onerror'] }}"
        {{ $attributes }}
    >
@else
    <img 
        src="{{ $imageAttributes['src'] }}" 
        alt="{{ $imageAttributes['alt'] }}" 
        class="{{ $imageAttributes['class'] }}" 
        loading="{{ $imageAttributes['loading'] }}" 
        decoding="{{ $imageAttributes['decoding'] }}"
        {{ $attributes }}
    >
@endif