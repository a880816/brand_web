@extends('layouts.admin') @section('title', '品牌管理') @section('content')<div class="admin-heading">
    <div>
        <p class="admin-kicker">SYSTEM</p>
        <h1>品牌管理</h1>
    </div>
    <a class="admin-button" href="{{ route('admin.brands.create') }}">新增品牌</a>
</div>
<section class="admin-panel table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>品牌</th>
                <th>Domain</th>
                <th>狀態</th>
                <th>內容</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($brands as $item)
                <tr>
                    <td data-label="品牌">
                        <strong>{{ $item->name }}</strong>
                        <small>{{ $item->slug }}</small>
                    </td>
                    <td data-label="Domain">{{ $item->domain ?: '—' }}</td>
                    <td data-label="狀態">
                        <span class="status {{ $item->status }}">{{ $item->status }}</span>
                    </td>
                    <td data-label="內容">頁 {{ $item->pages_count }}／服務 {{ $item->services_count }}／課程
                        {{ $item->courses_count }}</td>
                    <td data-label="操作">
                        <a href="{{ route('admin.brands.edit', $item) }}">編輯</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>@endsection
