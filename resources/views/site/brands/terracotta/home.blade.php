@extends('layouts.site')
@section('content')<x-homepage-content variant="terracotta" :data="$data" :media="$media" :courses="$courses" :gallery="$gallery" />@endsection
