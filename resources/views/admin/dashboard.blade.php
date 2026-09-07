@extends('layouts.admin')
@section('title', '總覽')
@section('content')
    <div class="admin-heading">
        <div>
            <p class="admin-kicker">DASHBOARD</p>
            <h1>{{ $brand->name }} 管理總覽</h1>
        </div>
        <div class="heading-actions">
            <a class="admin-button" href="{{ route('admin.orders.create') }}">新增成交單</a>
            <a class="admin-button secondary" href="{{ route('admin.courses.create') }}">新增課程</a>
        </div>
    </div>
    <div class="stats">
        @foreach ($stats as $label => $value)
            <article>
                <span>{{ $label }}</span>
                <strong>{{ $value }}</strong>
            </article>
        @endforeach
    </div>
    <div class="admin-grid">
        <section class="admin-panel">
            <h2>即將開課</h2>
            @forelse($sessions as $session)
                <div class="activity">
                    <div>
                        <strong>{{ $session->course->name }}</strong>
                        <small>{{ $session->starts_at->format('Y/m/d H:i') }}・{{ $session->venue_name }}</small>
                    </div>
                    <span>剩餘 {{ $session->remainingCapacity() }}</span>
                </div>
            @empty
                <p class="admin-empty">近期沒有課程場次。</p>
            @endforelse
        </section>
        <section class="admin-panel">
            <h2>課程報名待辦</h2>
            @foreach ($registrationCounts as $label => $value)
                <div class="activity">
                    <strong>{{ $label }}</strong>
                    <span>{{ $value }}</span>
                </div>
            @endforeach
            <a class="text-link" href="{{ route('admin.registrations.index') }}">
                查看報名 →</a>
        </section>
        <section class="admin-panel">
            <h2>庫存提醒</h2>
            @forelse($materials as $material)
                <p class="warning">{{ $material->name }}：剩餘 {{ $material->availableQuantity() }}</p>
            @empty
                @if ($specimens->isEmpty())
                    <p class="success">目前沒有低庫存或售完提醒。</p>
                @endif
            @endforelse
            @foreach ($specimens as $specimen)
                <p class="warning">
                    {{ $specimen->variety->name }}・{{ $specimen->custom_name ?: '實株 ' . $specimen->sequence }}：售完</p>
            @endforeach
        </section>
        <section class="admin-panel">
            <h2>快速操作</h2>
            <div class="quick-links">
                <a href="{{ route('admin.homepage.edit') }}">維護首頁</a>
                <a href="{{ route('admin.plant-varieties.create') }}">新增品種</a>
                <a href="{{ route('admin.materials.create') }}">新增資材</a>
                <a href="{{ route('admin.settings') }}">品牌與匯款設定</a>
            </div>
        </section>
    </div>
@endsection
