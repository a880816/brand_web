@props(['media' => null, 'class' => '', 'eager' => false, 'ratio' => '4/3'])
<div class="media-frame {{ $class }}" style="aspect-ratio: {{ $ratio }}">
    @if ($media)
        <img src="{{ $media->url('detail') }}"
            @if ($media->srcset()) srcset="{{ $media->srcset() }}" sizes="(max-width: 767px) 100vw, 50vw" @endif
            alt="{{ $media->alt_text ?: '圖片' }}" width="{{ $media->width }}" height="{{ $media->height }}"
            @if (!$eager) loading="lazy" @else fetchpriority="high" @endif decoding="async">
    @else
        <div class="image-placeholder" role="img" aria-label="圖片準備中">
            <span>圖片準備中</span>
        </div>
    @endif
</div>
