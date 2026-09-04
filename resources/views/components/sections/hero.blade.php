@props(['section', 'page'])
@php($image=$section->primaryMedia('image') ?: $page->primaryMedia('hero_desktop'))
<section class="hero section-variant-{{ $section->variant }}">
 @if($image)<picture><img src="{{ $image->url() }}" alt="{{ $image->alt_text ?: $section->heading }}" width="{{ $image->width }}" height="{{ $image->height }}" fetchpriority="high"></picture>@endif
 <div class="hero-overlay"><p class="eyebrow">{{ data_get($section->settings,'eyebrow','WELCOME') }}</p><h1>{{ $section->heading ?: $page->title }}</h1>@if($section->body)<p>{{ $section->body }}</p>@endif @if(data_get($section->settings,'button_label') && data_get($section->settings,'button_url'))<a class="button light" href="{{ data_get($section->settings,'button_url') }}">{{ data_get($section->settings,'button_label') }}</a>@endif</div>
</section>
