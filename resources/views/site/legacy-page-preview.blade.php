@extends('layouts.site')
@section('content')
<section class="page-hero"><p class="eyebrow">PAGE PREVIEW</p><h1>{{ $page->title }}</h1><p>{{ $page->excerpt }}</p></section>
<section class="section prose">{!! nl2br(e($page->body)) !!}</section>
@endsection
