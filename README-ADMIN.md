# 多品牌維護後台

## 展示帳號

```bash
grep DEMO_ADMIN_PASSWORD .env
```

所有帳號共用上述本機展示密碼：

- `super_admin@example.test`
- `brand_admin_a@example.test`
- `brand_admin_b@example.test`
- `member@example.test`

Seeder 只在 `DEMO_ADMIN_PASSWORD` 有值時建立帳號；密碼不寫入版本控制。

## 權限

| 功能 | member | brand_admin | super_admin |
|---|---:|---:|---:|
| 登入、個人資料、密碼 | ✓ | ✓ | ✓ |
| 品牌內容、圖片、連結、頁面區塊 | — | 指派品牌 | 全部品牌 |
| 名稱、色票、SEO、允許的主題文字 | — | 指派品牌 | 全部品牌 |
| slug、domain、品牌狀態 | — | — | ✓ |
| 使用者、角色、品牌指派 | — | — | ✓ |

每個 Controller 與 Livewire action 都會從 `BrandContext` 重查資料並 authorize。前端送來的 `brand_id` 不會被採用。

## 頁面與發布

1. 後台「頁面／服務／課程」建立或編輯內容。
2. 草稿可使用「草稿預覽」查看前台模板。
3. 發布後，前台只顯示 `published_at` 不晚於目前時間的內容。
4. 下架保留資料；刪除使用 soft delete。

頁面編輯頁可新增固定 allowlist 區塊：主視覺、純文字、圖文、服務列表、課程列表、照片輪播、CTA。每個區塊隸屬特定品牌與頁面，可獨立排序、隱藏及選擇版型，不接受任意 Blade 路徑、CSS 或 script。

## 圖片與 Swiper

- 接受 JPEG、PNG、WebP，單檔最多 10MB。
- 產生 480、960、1600px WebP variants，不放大原圖。
- 區塊選擇「照片輪播」後，在該區塊上傳 `gallery` 圖片。
- 可排序、設主圖、編輯 alt/title、刪除原圖與 variants。
- Swiper 支援觸控、按鈕、分頁、鍵盤與 RWD；使用者偏好 reduced motion 時不自動播放。
- JavaScript 未載入時改為可水平捲動的圖片列。

目前 `QUEUE_CONNECTION=sync`，圖片轉檔同步執行，因此不需要 worker。若改成 queue，需另建 worker service 與 healthcheck。

## 常用指令

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app vendor/bin/phpunit
docker compose exec app php artisan storage:link
docker compose logs -f nginx app node db
```

## 正式上線清單

- 設定正式 `APP_KEY`、DB、MAIL、HTTPS、secure cookies 與可信 proxy。
- 移除或停用展示帳號；不要設定正式共用密碼。
- `APP_DEBUG=false`，限制資料庫與後台網路存取。
- 確認 mail reset link、備份、檔案儲存、稽核保存期限。
- 執行 migrations、Vite build、完整測試與權限驗收。
- 若圖片改用非同步處理，部署獨立 queue worker 與監控。

## 已知限制

- 課程只有介紹內容，沒有場次、名額、報名或付款。
- 頁面文字目前以純文字輸出；未開放任意 HTML。
- 區塊類型與版型由程式 allowlist 提供；新增全新版型仍需開發 Blade/CSS 元件。
