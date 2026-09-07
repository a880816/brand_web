@extends('layouts.site')
@section('content')
    <x-homepage-content :data="$data" :media="$media" :courses="$courses" :products="$products" :gallery="$gallery" />
@endsection
