# 多品牌維護後台

## 展示管理者

```bash
grep '^DEMO_ADMIN_PASSWORD=' .env
```

Seeder 僅在 `DEMO_ADMIN_PASSWORD` 有值時建立：

- `super_admin@example.test`
- `brand_admin_a@example.test`：只管理 Brand A
- `brand_admin_b@example.test`：只管理 Brand B

本階段沒有前台會員。密碼只來自本機環境變數，不寫入 Git。

## 權限

| 功能 | brand_admin | super_admin |
|---|---:|---:|
| 首頁、課程、商城、報名、成交單、品牌內容設定 | 指派品牌 | 全部品牌 |
| 切換品牌 | 僅指派品牌 | 全部品牌 |
| slug、domain、品牌狀態 | — | ✓ |
| 管理者、角色、品牌指派 | — | ✓ |

Controller、Policy、middleware 與 Livewire action 會重新檢查目前品牌；不採用前端送來的 `brand_id`。

## 首頁發布

1. 後台「首頁維護」分區修改主視覺、品牌介紹、精選課程、精選商品及照片輪播。
2. 儲存草稿後使用「草稿預覽」。
3. 發布才會覆蓋正式版本；未發布草稿不影響前台。
4. Brand A 與 Brand B 使用不同 Blade 版型；排版需由開發修改，後台不接受 HTML、CSS 或版型路徑。

## 課程與報名

1. 建立課程種類與預設人數方案；價格為方案固定價。
2. 建立場次，可沿用或覆寫方案，填寫名額、時間、地點及 HTTPS Google Maps 網址。
3. 場次預設於開課前 3 天截止；開放報名後不可修改總名額，可取消並保留報名紀錄。
4. 訪客免登入報名，成立後立即占用名額，付款期限 24 小時；逾期只標示，不自動釋放。
5. 管理者核對匯款末五碼、確認或人工取消；取消才釋放名額。
6. 行前通知只接受公開 Notion 網址，本站頁面會嘗試內嵌並提供外開按鈕與 QR Code。

## 植株與資材

- 品種先建立學名、管理者品種編號、母本照片與照護資訊，再新增實株。
- 實株流水號永久遞增；額外命名不可為純數字。完整花牌名稱由系統產生且全系統唯一。
- 庫存大於 1 視為不挑株；每售出一株建立 `流水號-售出順序` 的獨立紀錄。
- 資材可維護規格、庫存、低庫存門檻，以及草稿／上架／下架狀態；上架且庫存 0 時前台顯示售完。
- 圖片接受 JPEG、PNG、WebP，單檔最多 10MB，產生 480／960／1600px WebP variants；支援 alt、title、排序、主圖與完整刪除。

## 成交單與收件資料

1. 客戶從前台複製商品名稱、編號與網址並私訊品牌粉專。
2. 管理者貼入目前品牌的實株或資材網址與數量；建立成交單時立即保留庫存。
3. 將一次性顯示的收件資料網址傳給客戶。預設有效 7 天，重發會使舊 token 失效。
4. 客戶或管理者填寫 7-ELEVEN、全家、郵寄或自取資料；可重複修改至付款確認。
5. 管理者確認收款後才扣除實際庫存並建立售出紀錄；作廢或刪除會釋放／恢復庫存。

預設運費位於 `config/commerce.php`：7-ELEVEN 80、全家 80、郵寄 120、自取 0。成交單可覆寫運費或免運。

## Config 與環境變數

- `config/courses.php`：停止報名 3 天、付款期限 24 小時、前台封存 2 個月。
- `config/commerce.php`：運費與收件連結有效時間。
- `ORDER_RECIPIENT_LINK_TTL_HOURS=168`：收件連結有效時間。
- `QUEUE_CONNECTION=sync`：目前圖片同步轉檔，不需 worker；改成 queue 時應另建有 healthcheck 的 worker service。

## 常用指令

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose logs nginx app node db
```

## 正式上線安全清單

- 設定正式 APP_KEY、DB、MAIL、HTTPS、secure cookies、可信 proxy，並使用 `APP_DEBUG=false`。
- 移除展示管理者，不在正式環境共用密碼；限制資料庫與後台網路存取。
- 設定寄信後驗證忘記密碼與未來通知流程。
- 確認資料庫／媒體備份、還原演練、稽核保存期限與 log 脫敏。
- 以正式 hostname 驗證品牌隔離、權限、庫存鎖定、圖片儲存與外部網址 allowlist。
- 執行 migration、前端 build、完整測試及 390／768／1440px 瀏覽器驗收。
