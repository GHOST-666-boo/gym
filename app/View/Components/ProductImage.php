<?php

namespace App\View\Components;

use App\Helpers\ImageHelper;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProductImage extends Component
{
    public string $src;
    public string $alt;
    public string $class;
    public int $width;
    public int $height;
    public bool $lazy;
    public array $imageAttributes;

    /**
     * Create a new component instance.
     */
    public function __construct(
        ?string $imagePath = null,
        string $alt = '',
        string $class = '',
        int $width = 300,
        int $height = 200,
        bool $lazy = true
    ) {
        $this->alt = $alt;
        $this->class = $class;
        $this->width = $width;
        $this->height = $height;
        $this->lazy = $lazy;

        if ($imagePath && $lazy) {
            $this->imageAttributes = ImageHelper::lazyImageAttributes($imagePath, $alt, [
                'width' => $width,
                'height' => $height,
                'class' => $class
            ]);
            $this->src = $this->imageAttributes['data-src'];
        } else {
            $this->src = $imagePath ? ImageHelper::getImageSrc($imagePath, ['width' => $width, 'height' => $height]) : ImageHelper::getPlaceholderImage($width, $height);
            $this->imageAttributes = [
                'src' => $this->src,
                'alt' => $alt,
                'class' => $class,
                'width' => $width,
                'height' => $height,
                'loading' => $lazy ? 'lazy' : 'eager',
                'decoding' => 'async'
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.product-image');
    }
}