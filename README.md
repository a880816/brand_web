# Flora Multi Brand

Laravel 12 多品牌形象網站與維護後台。依 hostname 解析品牌，共用內容元件，但每個品牌可獨立設定頁面區塊、順序、版型與圖片輪播。

## 啟動

```bash
cp .env.example .env
# 在 .env 設定 APP_KEY 與 DEMO_ADMIN_PASSWORD
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

若是全新的本機資料庫，也可用 `php artisan migrate:fresh --seed`；既有環境請使用 `migrate`，避免刪除資料。

網址：

- http://localhost:8085
- http://brand-a.localhost:8085
- http://brand-b.localhost:8085
- http://brand-a.localhost:8085/admin

## 測試與建置

測試使用容器內 PostgreSQL 的獨立資料庫 `brand_web_testing`，不會清空本機展示資料。

```bash
docker compose exec db createdb -U brand_web brand_web_testing
docker compose exec app vendor/bin/phpunit
docker compose restart node
docker compose ps
```

`createdb` 只需執行一次。Node service 會執行 `npm install` 與 Vite build。

## TablePlus

- Host：`127.0.0.1`
- Port：`5433`
- User：`brand_web`
- Password：查看 `.env` 的 `DB_PASSWORD`
- Database：`brand_web`
- SSL：停用

資料庫只綁定 loopback。若 5433 已占用，修改 `.env` 的 `DB_FORWARD_PORT` 後重建 db service。

後台、權限、展示帳號、發布與圖片流程見 [README-ADMIN.md](README-ADMIN.md)。
