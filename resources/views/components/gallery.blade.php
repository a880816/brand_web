@props(['media'])
@if($media->isNotEmpty())<div class="gallery" aria-label="圖片集">@foreach($media as $image)<x-media-image :media="$image" ratio="4/3" />@endforeach</div>@endif
