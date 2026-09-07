@props(['name', 'code'])
<div class="share-product" x-data="{ copied: false, name: {{ Js::from($name) }}, code: {{ Js::from($code) }} }">
    <button type="button" class="button"
        @click="navigator.clipboard.writeText(name+'\n商品編號：'+code+'\n'+window.location.href).then(()=>{copied=true;setTimeout(()=>copied=false,1800)})">
        <span x-text="copied?'已複製商品資訊':'聯絡購買／複製商品資訊'">
        </span>
    </button>
    <p>複製後可傳給品牌粉專：</p>
    @if ($brand->facebook_url)
        <a class="text-link" href="{{ $brand->facebook_url }}" target="_blank" rel="noopener noreferrer">Facebook ↗</a>
    @endif
    @if ($brand->instagram_url)
        <a class="text-link" href="{{ $brand->instagram_url }}" target="_blank" rel="noopener noreferrer">Instagram
            ↗</a>
    @endif
</div>
