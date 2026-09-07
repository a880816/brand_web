@extends('layouts.site')
@section('title', ($course->seo_title ?: $course->name) . '｜' . $brand->name)
@section('content')
    <section class="detail-hero">
        <x-media-image :media="$course->primaryMedia('cover')" :eager="true" ratio="16/8" />
        <div>
            <p class="eyebrow">WORKSHOP</p>
            <h1>{{ $course->name }}</h1>
            <p>{{ $course->summary }}</p>
        </div>
    </section>
    <section class="detail-grid section">
        <div class="prose">
            @foreach (preg_split('/\n\n+/', (string) $course->description) as $paragraph)
                @if (filled($paragraph))
                    <p>{{ $paragraph }}</p>
                @endif
            @endforeach
        </div>
        <aside class="facts">
            <h2>課程資訊</h2>
            <dl>
                @if ($course->duration_minutes)
                    <dt>時間</dt>
                    <dd>{{ $course->duration_minutes }} 分鐘</dd>
                @endif
                <dt>
                    方案</dt>
                <dd>
                    @foreach ($course->plans->where('is_enabled', true) as $plan)
                        <span>{{ $plan->name }}・{{ $plan->participants }} 人・NT$
                            {{ number_format((float) $plan->price) }}</span>
                        <br>
                    @endforeach
                </dd>
            </dl>
        </aside>
    </section>
    @if ($course->mediaFor('gallery')->exists())
        <section class="section">
            <div class="swiper js-swiper">
                <div class="swiper-wrapper">
                    @foreach ($course->mediaFor('gallery')->get() as $image)
                        <div class="swiper-slide">
                            <x-media-image :media="$image" />
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
        </section>
    @endif
    <section class="section course-notes">
        <div>
            <p class="eyebrow">FOR YOU</p>
            <h2>適合對象</h2>
            <p>{{ $course->suitable_for ?: '歡迎對植物手作有興趣的你。' }}</p>
        </div>
        <div>
            <p class="eyebrow">BEFORE CLASS</p>
            <h2>注意事項與成品說明</h2>
            <p>{{ $course->precautions ?: '詳細說明請以各場次與行前通知為準。' }}</p>
        </div>
    </section>
    <section class="course-block">
        <div class="section">
            <div class="section-head">
                <div>
                    <p class="eyebrow">SESSIONS</p>
                    <h2>選擇課程場次</h2>
                </div>
            </div>
            <div class="swiper js-swiper session-swiper">
                <div class="swiper-wrapper">
                    @forelse($course->sessions as $session)
                        @php($state = $session->availabilityStatus())
                        <article class="swiper-slide session-card">
                            <p class="meta">{{ $session->starts_at->format('Y.m.d') }}</p>
                            <h3>{{ $session->starts_at->format('H:i') }}–{{ $session->ends_at->format('H:i') }}</h3>
                            <p>{{ $session->city }}・{{ $session->venue_name }}</p>
                            <p>{{ $session->address }}</p>
                            <a class="text-link" href="{{ $session->google_maps_url }}" target="_blank"
                                rel="noopener noreferrer">查看地圖 ↗</a>
                            <p class="availability {{ $state }}">
                                {{ ['open' => '尚可報名 ' . $session->remainingCapacity() . ' 人', 'full' => '已額滿', 'closed' => '報名截止', 'cancelled' => '場次取消'][$state] }}
                            </p>
                            @if ($state === 'open')
                                <a class="button"
                                    href="{{ route('registrations.create', [$course->slug, $session]) }}">選擇方案報名</a>
                            @endif
                        </article>
                    @empty
                        <div class="swiper-slide empty">目前無可報名時段。</div>
                    @endforelse
                </div>
                <div class="swiper-button-prev">
                </div>
                <div class="swiper-button-next">
                </div>
                <div class="swiper-pagination">
                </div>
            </div>
        </div>
    </section>
    <section class="section">
        <a class="text-link" href="{{ route('courses.index') }}">← 返回課程列表</a>
    </section>
@endsection
