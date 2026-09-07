<section class="admin-panel media-manager">
    @php($collectionLabels = ['logo' => '品牌 Logo', 'favicon' => '瀏覽器圖示', 'hero_desktop' => '桌機主視覺', 'hero_mobile' => '手機主視覺', 'intro' => '品牌介紹圖', 'mother' => '母本照片', 'specimen' => '實株照片', 'cover' => '封面圖', 'gallery' => '內容輪播圖', 'og' => '社群分享圖（OG）'])
    @php($collectionDescriptions = ['logo' => '顯示於品牌識別區域。', 'favicon' => '顯示於瀏覽器分頁。', 'hero_desktop' => '首頁桌機版的主視覺圖片。', 'hero_mobile' => '首頁手機版的主視覺圖片。', 'intro' => '首頁品牌介紹區塊的圖片。', 'mother' => '品種的母本或代表照片。', 'specimen' => '實際販售植株的照片。', 'cover' => '顯示於列表卡片與內容頁主圖。', 'gallery' => '顯示於內容詳細頁的多圖輪播。', 'og' => '社群分享預覽專用；目前尚未接到前台分享資訊。'])
    <div class="panel-head">
        <div>
            <h2>圖片管理</h2>
            <p>JPEG、PNG、WebP，單檔最多 10MB。</p>
        </div>
        <label>分類<select wire:model.live="collection">
                @foreach ($collections as $option)
                    <option value="{{ $option }}">{{ $collectionLabels[$option] ?? $option }}</option>
                @endforeach
            </select>
            <small>{{ $collectionDescriptions[$collection] ?? '' }}</small>
        </label>
    </div>
    <form wire:submit="saveUploads" class="upload-form" x-data>
        <div class="upload-picker">
            <input class="visually-hidden" type="file" wire:model="uploads" accept="image/jpeg,image/png,image/webp"
                multiple x-ref="uploadInput" aria-label="選擇上傳圖片">
            <button class="admin-button secondary" type="button" @click="$refs.uploadInput.click()"
                wire:loading.attr="disabled" wire:target="uploads,saveUploads">
                選擇圖片
            </button>
            <div class="upload-selection" aria-live="polite">
                <span wire:loading wire:target="uploads">正在讀取圖片…</span>
                <div wire:loading.remove wire:target="uploads">
                    @if (count($uploads) > 0)
                        <strong>已選擇 {{ count($uploads) }} 張圖片</strong>
                        <ul>
                            @foreach ($uploads as $upload)
                                <li>{{ $upload->getClientOriginalName() }}</li>
                            @endforeach
                        </ul>
                    @else
                        <span>尚未選擇圖片</span>
                    @endif
                </div>
            </div>
        </div>
        <label>替代文字<input type="text" wire:model="alt" maxlength="255" placeholder="例如：窗邊的龜背芋與陶盆">
        </label>
        <label class="check">
            <input type="checkbox" wire:model="primary"> 將第一張設為主圖</label>
        @error('uploads')
            <p class="field-error">{{ $message }}</p>
        @enderror
        @error('uploads.*')
            <p class="field-error">{{ $message }}</p>
        @enderror
        <button class="admin-button" type="submit" @disabled(count($uploads) === 0) wire:loading.attr="disabled"
            wire:target="uploads,saveUploads">
            <span wire:loading.remove wire:target="saveUploads">確定上傳</span>
            <span wire:loading wire:target="saveUploads">上傳處理中…</span>
        </button>
    </form>
    <div class="media-list">
        @forelse($media as $image)
            <article>
                <img src="{{ $image->url() }}" alt="{{ $image->alt_text }}">
                <div>
                    <strong>{{ $image->original_filename }}</strong>
                    <small>{{ $image->width }}×{{ $image->height }} ・
                        {{ number_format($image->file_size / 1024) }}
                        KB</small>
                    <span>{{ $image->is_primary ? '主圖' : '排序 ' . $image->sort_order }}</span>
                </div>
                <div class="row-actions">
                    <button type="button" wire:click="move({{ $image->id }},'up')" aria-label="上移">↑</button>
                    <button type="button" wire:click="move({{ $image->id }},'down')" aria-label="下移">↓</button>
                    @unless ($image->is_primary)
                        <button type="button" wire:click="setPrimary({{ $image->id }})">設為主圖</button>
                    @endunless
                    <button class="danger" type="button" wire:click="delete({{ $image->id }})"
                        wire:confirm="確定刪除這張圖片？">
                        刪除</button>
                </div>
            </article>
        @empty
            <p class="admin-empty">此分類尚未上傳圖片。</p>
        @endforelse
    </div>
</section>
