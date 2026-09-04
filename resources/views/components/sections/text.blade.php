@props(['section'])
<section class="intro section section-variant-{{ $section->variant }}">@if($section->heading)<h2>{{ $section->heading }}</h2>@endif @if($section->body)<div class="prose">@foreach(preg_split('/\n\n+/', $section->body) as $paragraph)<p>{{ $paragraph }}</p>@endforeach</div>@endif</section>
