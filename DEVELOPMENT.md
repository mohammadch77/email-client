# Local Development

## Requirements
- PHP 8.3+
- Composer
- Node.js 18+
- MySQL 8 (Laragon)
- Redis (Laragon)

## Start Backend
```bash
# Terminal 1 — Laravel API
php artisan serve
# Runs on http://localhost:8000
```

## Start Queue Worker
```bash
# Terminal 2 — Process sync/send jobs
php artisan queue:work --tries=3 --backoff=60
```

## Start Scheduler (optional)
```bash
# Terminal 3 — Every-5-min sync trigger
php artisan schedule:work
```

## Start Frontend
```bash
# Terminal 4 — Vue dev server
cd frontend
npm run dev
# Runs on http://localhost:5173
```

## First Run
1. Start MySQL and Redis (via Laragon)
2. Run: php artisan migrate
3. Start all terminals above
4. Open http://localhost:5173
5. Register an account
6. Go to Settings > Add Account
7. Enter IranServer credentials
8. Click "Test & Save"
9. Click "Sync" to fetch emails
