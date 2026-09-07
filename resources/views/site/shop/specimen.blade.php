@extends('layouts.site')
@section('title', $specimen->full_tag_name . '｜' . $brand->name)
@section('content')
    <section class="page-hero compact">
        <p class="eyebrow">SELECTED PLANT</p>
        <h1>{{ $variety->name }}・{{ $specimen->custom_name ?: '實株 ' . $specimen->sequence }}</h1>
        <p>{{ $specimen->full_tag_name }}</p>
    </section>
    <section class="section detail-grid">
        <div class="swiper js-swiper product-gallery">
            <div class="swiper-wrapper">
                @foreach ($variety->mediaFor('mother')->get() as $image)
                    <div class="swiper-slide">
                        <x-media-image :media="$image" />
                        <p>母本照片</p>
                    </div>
                @endforeach
                @foreach ($specimen->mediaFor('specimen')->get()->concat($specimen->mediaFor('gallery')->get()) as $image)
                    <div class="swiper-slide">
                        <x-media-image :media="$image" />
                        <p>實株照片</p>
                    </div>
                @endforeach
            </div>
            <div class="swiper-button-prev">
            </div>
            <div class="swiper-button-next">
            </div>
            <div class="swiper-pagination">
            </div>
        </div>
        <aside class="facts">
            <h2>商品資訊</h2>
            <p>{{ $specimen->description }}</p>
            <strong class="product-price">NT$
                {{ number_format((float) $specimen->price) }}</strong>
            <p>在庫 {{ $specimen->availableQuantity() }}{{ $specimen->stock_on_hand > 1 ? '・此商品為不挑株' : '' }}</p>
            @if ($specimen->specifications)
                <dl>
                    @foreach ($specimen->specifications as $spec)
                        <dt>{{ $spec['name'] }}</dt>
                        <dd>{{ $spec['value'] }}</dd>
                    @endforeach
                </dl>
            @endif
            <x-share-product :name="$variety->name . '・' . ($specimen->custom_name ?: '實株 ' . $specimen->sequence)" :code="$specimen->full_tag_name" />
        </aside>
    </section>
@endsection
