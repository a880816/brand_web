@extends('layouts.site')
@section('title','行前通知｜'.$course->name)
@section('content')
<section class="page-hero compact"><p class="eyebrow">BEFORE CLASS</p><h1>{{ $course->name }}行前通知</h1><p>若下方 Notion 內容無法顯示，請使用外部開啟按鈕。</p></section><section class="section notion-wrap"><iframe src="{{ $course->notion_url }}" title="{{ $course->name }}行前通知" loading="lazy" referrerpolicy="no-referrer"></iframe><a class="button" href="{{ $course->notion_url }}" target="_blank" rel="noopener noreferrer">在 Notion 開啟 ↗</a></section>
@endsection
