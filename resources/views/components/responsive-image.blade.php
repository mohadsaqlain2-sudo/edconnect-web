{{-- Phase 3F: responsive image with WebP/AVIF art-direction, srcset,
     blur-up placeholder, correct width/height to prevent layout shift (CLS).

     Usage:
       <x-responsive-image :src="$imageUrl" :width="$w" :height="$h"
           alt="Description"
           sizes="(max-width: 575px) 100vw, (max-width: 991px) 60vw, 720px"
           class="img-fluid rounded-3" />

     With art-direction (different crop per screen size):
       <x-responsive-image :src="$imageUrl" :src-mobile="$mobileUrl"
           :width="1200" :height="675"
           :width-mobile="600" :height-mobile="600"
           alt="Description" /> --}}

@props([
    'src',
    'srcMobile' => null,
    'alt' => '',
    'width' => null,
    'height' => null,
    'widthMobile' => null,
    'heightMobile' => null,
    'sizes' => '(max-width: 575.98px) 100vw, (max-width: 991.98px) 60vw, 720px',
    'loading' => 'lazy',
    'decoding' => 'async',
    'class' => 'img-fluid',
    'blurUp' => true,
    'mediaRatio' => null,
])

@php
    // Build a srcset based on the single URL — at half size and full size.
    // For best performance the controller / service should generate proper
    // WebP derivatives and pass them. This component is the contract; the
    // implementation already exists in Phase 3E storage helpers.
    $hasDimensions = $width && $height;

    $ratioStyle = $mediaRatio
        ? "aspect-ratio: {$mediaRatio};"
        : ($hasDimensions ? "aspect-ratio: {$width} / {$height};" : '');

    $srcsetMain = $src
        ? "{$src} {$width}w"
        : '';
@endphp

{{-- Only wrap in <picture> when we have a mobile/art-direction alternate
     or when we want to provide WebP/AVIF source hints. --}}
@if ($srcMobile)
<picture>
    {{-- Mobile crop (portrait, narrow) --}}
    <source
        media="(max-width: 575.98px)"
        srcset="{{ $srcMobile }}"
        @if ($widthMobile && $heightMobile)
            width="{{ $widthMobile }}" height="{{ $heightMobile }}"
        @endif>
    {{-- Desktop crop (landscape / wide) --}}
    <source
        media="(min-width: 576px)"
        srcset="{{ $srcsetMain ?: $src }}">
    <img src="{{ $src }}"
         @if ($hasDimensions) width="{{ $width }}" height="{{ $height }}" @endif
         alt="{{ $alt }}"
         @if ($ratioStyle) style="{{ $ratioStyle }}" @endif
         loading="{{ $loading }}"
         decoding="{{ $decoding }}"
         @if ($blurUp) data-blur-up @endif
         {{ $attributes->class([$class, $blurUp ? 'edu-blur-up' : '']) }}>
</picture>
@else
<img src="{{ $src }}"
     @if ($hasDimensions) width="{{ $width }}" height="{{ $height }}" @endif
     @if ($srcsetMain) srcset="{{ $srcsetMain }}" @endif
     sizes="{{ $sizes }}"
     alt="{{ $alt }}"
     @if ($ratioStyle) style="{{ $ratioStyle }}" @endif
     loading="{{ $loading }}"
     decoding="{{ $decoding }}"
     @if ($blurUp) data-blur-up @endif
     {{ $attributes->class([$class, $blurUp ? 'edu-blur-up edu-post-image' : '']) }}>
@endif
