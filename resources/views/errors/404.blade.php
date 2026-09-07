@extends('layouts.site') @section('title', '找不到頁面｜' . $brand->name) @section('content')<section class="not-found">
    <p class="eyebrow">404</p>
    <h1>這片葉子暫時找不到了</h1>
    <p>你造訪的內容可能已移動或尚未公開。</p>
    <a class="button" href="{{ route('home') }}">回到首頁</a>
</section>@endsection
