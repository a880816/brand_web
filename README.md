# Flora Multi Brand — 第一階段

Laravel 12 模組化單體形象網站。以 hostname 解析品牌，一份程式與 PostgreSQL 提供品牌首頁、介紹、服務、課程、多圖片與外部社群連結；不含登入、後台、會員、商品、報名、訂單或金流。

## 需求與啟動

本機只需 Git、Docker、Docker Compose。PHP、Composer、Node、npm、Nginx、PostgreSQL 都在容器內。

```bash
cp .env.example .env
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
```

驗收網址：

- http://localhost:8085（預設蕨光植研）
- http://brand-a.localhost:8085（蕨光植研）
- http://brand-b.localhost:8085（土日植所）

現代瀏覽器會將 `*.localhost` 解析到 loopback。若環境不支援，於 hosts 檔加入 `127.0.0.1 brand-a.localhost brand-b.localhost`。

## 常用指令

```bash
docker compose up -d
docker compose down
docker compose logs -f nginx app node db
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan test
docker compose run --rm node npm run build
docker compose exec app composer install
docker compose exec node npm install
```

## 使用 TablePlus 連線 PostgreSQL

資料庫僅映射至本機 `127.0.0.1:5433`，不對區網公開。TablePlus 建立 PostgreSQL 連線時填入：

- Host：`127.0.0.1`
- Port：`5433`
- User：`brand_web`
- Password：`brand_web_local`
- Database：`brand_web`
- SSL：停用

若本機 5433 已被占用，可修改 `.env` 的 `DB_FORWARD_PORT` 後執行 `docker compose up -d db`。容器內 Laravel 仍使用 `db:5432`，不需跟著修改。

資料庫使用 named volume `postgres_data`；`vendor_data`、`node_modules` 與 `public_storage` 也不依賴主機工具。要連資料一起清除，需明確執行 `docker compose down -v`（不可復原）。

## 架構

- `ResolveBrand` 依 domain/本機 hostname 設定單例 `BrandContext`。
- Controller 只從目前 `Brand` 的 relationships 取資料，tenant 條件不散落。
- 色票與主題文案由 `brands` 與 CSS variables 控制，共用同一組 Blade。
- `Brand`、`Page`、`Service`、`Course` 透過 polymorphic `morphMany` 擁有多筆 `Media`。
- `MediaService` 限制 JPEG/PNG/WebP、10MB、允許 collection、安全 UUID 路徑、品牌一致性、public disk 與實體刪除。
- 原圖目前保留；`variants` JSONB 與 service 邊界已保留，WebP resizing pipeline 尚未在第一階段執行。

## 常見問題

- `8085` 被占用：修改 `.env` 的 `APP_PORT`（正式驗收仍以 8085 為準）。
- 首次啟動較慢：app 會安裝 Composer dependencies，node 會安裝並編譯 Vite；用 `docker compose ps` 等待 health 為 healthy。
- 圖片 404：確認 `public_storage` volume 已掛載，重新執行 `migrate:fresh --seed`。
- 權限錯誤：app entrypoint 會建立 storage/cache 目錄並交給 `www-data`。
