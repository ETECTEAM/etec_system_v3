## 🔁 Daily Development Flow

After pulling latest code:

```bash
git pull origin dev
composer install
```

---

### 🔄 Sync environment (if `.env` changed)

```bash
composer run sync-env
```


### 🧹 Clear cache (if something breaks)

```bash
php artisan optimize:clear
```

---

### 🐳 Restart Docker (if needed)

```bash
docker compose down
docker compose up -d
```

---
