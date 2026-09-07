<section class="admin-panel media-manager">
 <div class="panel-head"><div><h2>圖片管理</h2><p>JPEG、PNG、WebP，單檔最多 10MB。</p></div><label>分類<select wire:model.live="collection">@foreach($collections as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></label></div>
 <form wire:submit="saveUploads" class="upload-form">
  <label>選擇圖片<input type="file" wire:model="uploads" accept="image/jpeg,image/png,image/webp" multiple></label>
  <label>替代文字<input type="text" wire:model="alt" maxlength="255" placeholder="例如：窗邊的龜背芋與陶盆"></label>
  <label class="check"><input type="checkbox" wire:model="primary"> 將第一張設為主圖</label>
  @error('uploads')<p class="field-error">{{ $message }}</p>@enderror @error('uploads.*')<p class="field-error">{{ $message }}</p>@enderror
  <button class="admin-button" type="submit" wire:loading.attr="disabled"><span wire:loading.remove>上傳圖片</span><span wire:loading>處理中…</span></button>
 </form>
 <div class="media-list">@forelse($media as $image)<article><img src="{{ $image->url() }}" alt="{{ $image->alt_text }}"><div><strong>{{ $image->original_filename }}</strong><small>{{ $image->width }}×{{ $image->height }} ・ {{ number_format($image->file_size/1024) }} KB</small><span>{{ $image->is_primary?'主圖':'排序 '.$image->sort_order }}</span></div><div class="row-actions"><button type="button" wire:click="move({{ $image->id }},'up')" aria-label="上移">↑</button><button type="button" wire:click="move({{ $image->id }},'down')" aria-label="下移">↓</button>@unless($image->is_primary)<button type="button" wire:click="setPrimary({{ $image->id }})">設為主圖</button>@endunless<button class="danger" type="button" wire:click="delete({{ $image->id }})" wire:confirm="確定刪除這張圖片？">刪除</button></div></article>@empty<p class="admin-empty">此分類尚未上傳圖片。</p>@endforelse</div>
</section>
