# Flora Multi Brand

Laravel 12 多品牌官網與維護後台。每個 hostname 對應一個品牌，前台只保留首頁、手作課程、線上商店、訪客課程報名及成交單收件資料頁；各品牌首頁使用獨立 Blade 版型。

## Docker 啟動

```bash
cp .env.example .env
# 設定 APP_KEY、DB_PASSWORD、DEMO_ADMIN_PASSWORD
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec node npm run build
docker compose ps
```

全新本機資料庫可執行：

```bash
docker compose exec app php artisan migrate:fresh --seed
```

網址：

- http://brand-a.localhost:8085
- http://brand-b.localhost:8085
- http://brand-a.localhost:8085/admin
- http://brand-b.localhost:8085/admin

## 測試

測試使用容器內獨立 PostgreSQL 資料庫 `brand_web_testing`。

```bash
docker compose exec db createdb -U brand_web brand_web_testing
docker compose exec app php artisan test
```

`createdb` 只需執行一次；若已存在可略過。

## TablePlus

- Host：`127.0.0.1`
- Port：`5433`
- User：查看 `.env` 的 `DB_USERNAME`
- Password：查看 `.env` 的 `DB_PASSWORD`
- Database：查看 `.env` 的 `DB_DATABASE`
- SSL：停用

資料庫只綁定本機 loopback。完整後台與營運流程見 [README-ADMIN.md](README-ADMIN.md)。
