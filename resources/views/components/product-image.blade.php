{{-- Include protection styles if enabled --}}
@if($protectionEnabled && $protectionStyles)
    {!! $protectionStyles !!}
@endif

{{-- Include CSS watermark fallback styles if needed --}}
@if($hasWatermarkError() && $getWatermarkError() === 'css_fallback')
    {!! $getCssWatermarkStyles() !!}
@endif

{{-- Image container with protection wrapper and error handling --}}
<div class="{{ $protectionEnabled ? 'product-image h-full' : '' }} {{ $hasWatermarkError() && $getWatermarkError() === 'css_fallback' ? 'product-image-' . $getImageId() : '' }}" 
     {{ $attributes->except(['class']) }}
     @if($hasWatermarkError())
         data-watermark-error="{{ $getWatermarkError() }}"
         title="{{ $getErrorMessage() }}"
     @endif
     style="position: relative;">
     
    @if($lazy)
        <img 
            src="{{ $imageAttributes['src'] }}" 
            data-src="{{ $imageAttributes['data-src'] }}" 
            alt="{{ $imageAttributes['alt'] }}" 
            class="{{ $imageAttributes['class'] }} lazy-image" 
            loading="{{ $imageAttributes['loading'] }}" 
            decoding="{{ $imageAttributes['decoding'] }}"
            onerror="this.onerror=null; this.src='{{ asset('images/placeholder.svg') }}'; this.alt='Image not available';"
            @if($protectionEnabled)
                draggable="false"
                oncontextmenu="return false;"
                onselectstart="return false;"
                ondragstart="return false;"
            @endif
        >
    @else
        <img 
            src="{{ $imageAttributes['src'] }}" 
            alt="{{ $imageAttributes['alt'] }}" 
            class="{{ $imageAttributes['class'] }}" 
            loading="{{ $imageAttributes['loading'] }}" 
            decoding="{{ $imageAttributes['decoding'] }}"
            onerror="this.onerror=null; this.src='{{ asset('images/placeholder.svg') }}'; this.alt='Image not available';"
            @if($protectionEnabled)
                draggable="false"
                oncontextmenu="return false;"
                onselectstart="return false;"
                ondragstart="return false;"
            @endif
        >
    @endif
    
    {{-- Error indicator for admin users (optional) --}}
    @if($hasWatermarkError() && auth()->check() && auth()->user()->isAdmin())
        <div class="watermark-error-indicator" 
             style="position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.8); color: white; padding: 2px 6px; font-size: 10px; border-radius: 3px; z-index: 20;"
             title="{{ $getErrorMessage() }}">
            ⚠
        </div>
    @endif
</div>

{{-- Include protection script if enabled with error handling --}}
@if($protectionEnabled && $protectionScript)
    @once
        <script>
            try {
                {!! $protectionScript !!}
            } catch (error) {
                console.warn('Image protection script failed:', error);
                // Fallback to basic CSS protection
                document.body.classList.add('js-protection-failed');
            }
        </script>
    @endonce
@endif

{{-- Fallback styles for when JavaScript fails --}}
@if($protectionEnabled)
    @once
        <style>
        /* Fallback protection when JavaScript fails */
        .js-protection-failed .product-image img,
        .js-protection-failed .product-gallery img {
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            user-select: none !important;
            -webkit-user-drag: none !important;
            user-drag: none !important;
            -webkit-touch-callout: none !important;
            pointer-events: none !important;
        }
        
        .js-protection-failed .product-image::before,
        .js-protection-failed .product-gallery::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 10;
            background: transparent;
            pointer-events: auto;
        }
        </style>
    @endonce
@endif