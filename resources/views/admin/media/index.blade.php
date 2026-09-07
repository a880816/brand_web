@extends('layouts.admin')
@section('title', '圖片管理')
@section('content')
    <div class="admin-heading">
        <div>
            <p class="admin-kicker">MEDIA</p>
            <h1>圖片管理</h1>
        </div>
    </div>
    <div class="media-library">
        @forelse($media as $image)
            <article class="admin-panel">
                <img src="{{ $image->url() }}" alt="{{ $image->alt_text }}">
                <small>{{ $image->collection }} ・
                    {{ class_basename($image->mediable_type) }} #{{ $image->mediable_id }}</small>
                <form class="admin-form" method="post" action="{{ route('admin.media.update', $image) }}">
                    @csrf
                    @method('PUT')
                    <label>Alt<input name="alt_text" value="{{ $image->alt_text }}">
                    </label>
                    <label>圖片標題<input name="title" value="{{ $image->title }}">
                    </label>
                    <button class="admin-button">儲存</button>
                </form>
                <form method="post" action="{{ route('admin.media.destroy', $image) }}"
                    onsubmit="return confirm('確定刪除此圖片與所有 variants？')">
                    @csrf
                    @method('DELETE')
                    <button class="admin-button danger">刪除</button>
                </form>
            </article>
        @empty
            <p class="admin-empty">目前品牌尚無圖片。</p>
        @endforelse
    </div>{{ $media->links() }}
@endsection
