<section class="admin-panel section-editor">
 <div class="panel-head"><div><h2>前台畫面區塊</h2><p>區塊只屬於目前品牌與此頁面，不會影響其他品牌。</p></div></div>
 <form wire:submit="save" class="admin-form section-form">
  <div class="form-grid">
   <label>區塊類型<select wire:model.live="type">@foreach(\App\Models\PageSection::TYPES as $option)<option value="{{ $option }}">{{ ['hero'=>'主視覺','text'=>'純文字','image_text'=>'圖文','services'=>'服務列表','courses'=>'課程列表','gallery'=>'照片輪播','cta'=>'行動按鈕'][$option] }}</option>@endforeach</select></label>
   <label>版型<select wire:model="variant">@foreach(\App\Models\PageSection::VARIANTS as $option)<option value="{{ $option }}">{{ ['default'=>'預設','split'=>'左右分欄','centered'=>'置中','full_width'=>'滿版'][$option] }}</option>@endforeach</select></label>
   <label>標題<input wire:model="heading" maxlength="255"></label>
   <label>狀態<select wire:model="status"><option value="active">顯示</option><option value="inactive">隱藏</option></select></label>
  </div>
  <label>文字內容<textarea wire:model="body" rows="5"></textarea></label>
  @if(in_array($type,['hero','cta']))<div class="form-grid"><label>按鈕文字<input wire:model="buttonLabel" maxlength="80"></label><label>按鈕網址<input wire:model="buttonUrl" placeholder="/services 或 https://example.com"></label></div>@endif
  @if(in_array($type,['services','courses']))<label>顯示筆數<input type="number" wire:model="limit" min="1" max="12"></label>@endif
  @if($type==='gallery')<label class="check"><input type="checkbox" wire:model="autoplay"> 自動播放</label>@endif
  <div class="form-actions"><button class="admin-button" wire:loading.attr="disabled">{{ $editingId?'更新區塊':'新增區塊' }}</button>@if($editingId)<button type="button" class="admin-button secondary" wire:click="cancel">取消</button>@endif</div>
 </form>
 <div class="section-list">
  @forelse($sections as $section)
   <article wire:key="section-{{ $section->id }}" class="section-item">
    <div><span class="status {{ $section->status }}">{{ $section->status==='active'?'顯示':'隱藏' }}</span><strong>{{ ['hero'=>'主視覺','text'=>'純文字','image_text'=>'圖文','services'=>'服務列表','courses'=>'課程列表','gallery'=>'照片輪播','cta'=>'行動按鈕'][$section->type] ?? $section->type }}</strong><small>{{ $section->heading ?: '未設定標題' }} ・ {{ $section->variant }}</small></div>
    <div class="row-actions"><button type="button" wire:click="move({{ $section->id }},'up')" aria-label="上移">↑</button><button type="button" wire:click="move({{ $section->id }},'down')" aria-label="下移">↓</button><button type="button" wire:click="edit({{ $section->id }})">編輯</button><button type="button" class="danger" wire:click="delete({{ $section->id }})" wire:confirm="確定刪除此區塊？">刪除</button></div>
    @if(in_array($section->type,['hero','image_text','gallery']))<div class="section-media"><livewire:admin.media-manager owner-type="page_section" :owner-id="$section->id" :key="'section-media-'.$section->id" /></div>@endif
   </article>
  @empty<p class="admin-empty">尚未建立區塊；前台會繼續使用既有版型。</p>@endforelse
 </div>
</section>
