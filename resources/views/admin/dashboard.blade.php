@extends('layouts.admin')
@section('title','總覽')
@section('content')
<div class="admin-heading"><div><p class="admin-kicker">DASHBOARD</p><h1>{{ $brand->name }} 管理總覽</h1></div></div>
<div class="stats">@foreach($counts as $label=>$count)<article><span>{{ $label }}</span><strong>{{ $count }}</strong></article>@endforeach</div>
<div class="admin-grid">
 <section class="admin-panel"><h2>最近動態</h2>@forelse($recent as $log)<div class="activity"><strong>{{ $log->action }}</strong><span>{{ $log->created_at->diffForHumans() }}</span></div>@empty<p class="admin-empty">尚無操作紀錄。</p>@endforelse</section>
 <section class="admin-panel"><h2>內容提醒</h2>@php $shown = false; @endphp @foreach($missing as $message=>$condition) @if($condition) @php $shown = true; @endphp <p class="warning">{{ $message }}</p>@endif @endforeach @if(!$shown)<p class="success">目前沒有待處理提醒。</p>@endif</section>
</div>
@endsection
