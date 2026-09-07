<!doctype html>
<html lang="zh-Hant">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="{{ $brand->seo_description }}">
    <title>@yield('title', $brand->seo_title ?: $brand->name)</title>@vite(['resources/css/app.css', 'resources/js/app.js'])@livewireStyles<style>
        :root {
            --primary: {{ $brand->primary_color }};
            --secondary: {{ $brand->secondary_color }};
            --accent: {{ $brand->accent_color }};
            --bg: {{ $brand->background_color }};
            --text: {{ $brand->text_color }}
        }
    </style>
</head>

<body>
    @if ($preview ?? false)
        <div class="preview-banner">草稿預覽｜此內容尚未正式發布</div>
    @endif
    <a class="skip" href="#content">
        跳至主要內容</a>
    <header x-data="{ open: false }" class="header">
        <a href="{{ route('home') }}" class="brand-mark" aria-label="{{ $brand->name }} 首頁">
            <span class="mark">✦</span>{{ $brand->name }}</a>
        <button class="menu" @click="open=!open" :aria-expanded="open" aria-controls="nav">選單</button>
        <nav id="nav" :class="open ? 'open' : ''" aria-label="主要導覽">
            <a href="{{ route('home') }}">{{ $brand->home_menu_label }}</a>
            <a href="{{ route('courses.index') }}">{{ $brand->courses_menu_label }}</a>
            <a href="{{ route('shop.index') }}">{{ $brand->shop_menu_label }}</a>
        </nav>
    </header>
    <main id="content">@yield('content')</main>
    <footer>
        <div>
            <strong>{{ $brand->name }}</strong>
            <p>讓植物自然地，成為日常的一部分。</p>
        </div>
        <nav aria-label="社群連結">
            @if ($brand->facebook_url)
                <a href="{{ $brand->facebook_url }}" target="_blank" rel="noopener noreferrer">Facebook ↗</a>
            @endif
            @if ($brand->instagram_url)
                <a href="{{ $brand->instagram_url }}" target="_blank" rel="noopener noreferrer">Instagram ↗</a>
            @endif
        </nav>
        <small>© {{ date('Y') }} {{ $brand->name }}</small>
    </footer>@livewireScripts
</body>

</html>
