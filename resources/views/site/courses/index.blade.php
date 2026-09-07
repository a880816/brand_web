@extends('layouts.site')
@section('title', '手作課程｜' . $brand->name)
@section('content')
    <section class="page-hero">
        <p class="eyebrow">WORKSHOPS</p>
        <h1>手作課程</h1>
        <p>選擇喜歡的課程，再挑選適合的場次與方案。</p>
    </section>
    <section class="section">
        <div class="cards listing">
            @forelse($courses as $course)
                @php($available = $course->sessions->contains(fn($s) => $s->availabilityStatus() === 'open'))
                <article class="card {{ $available ? '' : 'muted-card' }}">
                    <a href="{{ route('courses.show', $course->slug) }}">
                        <x-media-image :media="$course->primaryMedia('cover')" />
                    </a>
                    <div>
                        <p class="meta">{{ $available ? '開放報名' : '目前無可報名時段' }}</p>
                        <h2>
                            <a href="{{ route('courses.show', $course->slug) }}">{{ $course->name }}</a>
                        </h2>
                        <p>{{ $course->summary }}</p>
                        @if ($course->plans->isNotEmpty())
                            <strong>NT$ {{ number_format((float) $course->plans->min('price')) }} 起</strong>
                        @endif
                        <a class="text-link" href="{{ route('courses.show', $course->slug) }}">
                            查看課程與場次 →</a>
                    </div>
                </article>
            @empty
                <p class="empty">目前尚無已發布課程。</p>
            @endforelse
        </div>
    </section>
@endsection
