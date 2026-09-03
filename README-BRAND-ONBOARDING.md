# 新品牌上線清單

## 1. 品牌與網域

在 `brands` 新增唯一 `slug` 與正式 `domain`（只填 hostname，不含 scheme/path/port），`status=active`。本機開發可在 `ResolveBrand` 增加明確的 `brand-x.localhost` 對應；正式環境直接比對 `domain`。

設定 `primary_color`、`secondary_color`、`accent_color`、`background_color`、`text_color` 為可用的 CSS 色碼。`theme_settings` 可放 `eyebrow`、`hero_title`、`hero_note`。不要在 Blade 寫死品牌色。

## 2. 內容

- `pages`：至少建立 `home` 與 `about`；slug 在品牌內唯一。
- `services`：摘要、完整說明、排序與發布時間。
- `courses`：只寫可重複使用的課程介紹、時長、地點說明、參考費用；不建立場次、名額、報名或付款。
- 內容要顯示需 `status=published` 且 `published_at` 為空或不晚於現在。
- `brand_links`：type 可用 instagram/facebook/line/website/other，網址只能是有效 HTTP(S)，啟用後依 `sort_order` 顯示。

## 3. Media collections

- Brand：`logo`、`favicon`、`hero_desktop`、`hero_mobile`、`gallery`、`og`
- Page：`hero_desktop`、`hero_mobile`、`gallery`、`og`
- Service/Course：`cover`、`gallery`、`og`

每個內容可有多張 Media。主圖設 `is_primary=true`，同 collection 以 `sort_order` 升冪。Media 的 `brand_id` 必須和內容相同；請走 `MediaService`，不要直接接受使用者提供的路徑。圖片不得新增到內容表欄位。

## 4. 圖片與無障礙建議

- Hero desktop：至少 1600×900，主體避開文字區。
- Hero mobile：至少 900×1200，直式裁切。
- Cover：至少 1200×900，4:3。
- Gallery：長邊至少 1600；單張原圖建議低於 10MB。
- 格式：JPEG、PNG、WebP。照片優先 WebP/JPEG，透明圖才用 PNG。
- 每張圖填具體 alt，例如「窗邊龜背芋與陶盆」，不要寫「圖片」或塞關鍵字；純裝飾圖可留空。
- 上線前補齊 `width`、`height`，避免版面跳動；確認桌機與手機 Hero 都有圖。

## 5. 驗收

以正式 hostname 檢查首頁、介紹、服務與課程詳情、404、社群 CTA，並以 390px、768px、1440px 檢查導覽、Hero、卡片、Gallery 和水平捲動。最後執行 `docker compose exec app php artisan test`。
