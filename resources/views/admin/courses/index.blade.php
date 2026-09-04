@extends('layouts.admin')
@section('title','課程與場次')
@section('content')
<div class="admin-heading"><div><p class="admin-kicker">COURSES</p><h1>課程與場次</h1></div><a class="admin-button" href="{{ route('admin.courses.create') }}">新增課程</a></div>
<section class="admin-panel table-wrap"><table class="admin-table"><thead><tr><th>課程</th><th>狀態</th><th>場次</th><th>報名</th><th>操作</th></tr></thead><tbody>
@forelse($courses as $course)<tr><td data-label="課程"><strong>{{ $course->name }}</strong><small>/{{ $course->slug }}</small></td><td data-label="狀態"><span class="status {{ $course->status }}">{{ $course->status==='published'?'已發布':'草稿' }}</span></td><td data-label="場次">{{ $course->sessions_count }}</td><td data-label="報名">{{ $course->registrations_count }}</td><td data-label="操作" class="table-actions"><a href="{{ route('admin.courses.edit',$course) }}">編輯</a><a href="{{ route('courses.show',$course->slug) }}" target="_blank" rel="noopener noreferrer">前台</a></td></tr>
@empty<tr><td colspan="5" class="admin-empty">尚無課程。</td></tr>@endforelse
</tbody></table>{{ $courses->links() }}</section>
@endsection
