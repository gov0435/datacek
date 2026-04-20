# Database Architecture: Neon PostgreSQL + SQLite Fallback

## Overview

Aplikasi menggunakan **dual database strategy** untuk high availability:
- **Primary**: Neon PostgreSQL (cloud)
- **Fallback**: SQLite (local storage)

Otomatis switch saat Neon unreachable, switch kembali saat pulih.

---

## Setup Neon PostgreSQL

### 1. Create Neon Project

1. Kunjungi https://neon.tech
2. Sign up / Login
3. Create new project
4. Copy connection string format:
   ```
   postgresql://user:password@ep-xxx.neon.tech/dbname?sslmode=require
   ```

### 2. Configure Environment

Di file `.env` (production):

```env
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://user:password@ep-xxx.neon.tech/dbname?sslmode=require
DB_FALLBACK_DATABASE=storage/fallback.sqlite
```

### 3. Initial Migration

Jalankan migrasi ke Neon (hanya 1x saat setup):

```bash
php artisan migrate --database=pgsql
```

Output diharapkan:
```
✓ create_telegram_users_table
✓ create_incomes_table
✓ create_expenses_table
✓ create_sync_logs_table
(+ other default tables)
```

---

## Database Schema

### telegram_users
```
id (bigint)
telegram_id (bigint, UNIQUE)
username (nullable)
first_name
created_at, updated_at
```

### incomes
```
id (bigint)
chat_id (bigint)
user_telegram_id (FK → telegram_users.telegram_id)
amount (decimal 15,2)
description (varchar, default: "Pemasukan")
income_date (timestamp)
telegram_message_id (bigint, nullable)
sync_status (enum: synced, pending)
synced_at (timestamp, nullable)
deleted_at (timestamp, nullable - soft delete)
created_at, updated_at

Indexes:
- (chat_id, income_date)
- (user_telegram_id)
- (sync_status)
```

### expenses
```
id (bigint)
chat_id (bigint)
user_telegram_id (FK → telegram_users.telegram_id)
description (varchar)
amount (decimal 15,2)
category (enum: makan, transport, hiburan, other)
source (enum: text, ocr)
is_reimbursable (boolean)
reimbursed_at (timestamp, nullable)
reimbursed_by (bigint, nullable - telegram_id executor)
sync_status (enum: synced, pending, pending_ocr)
expense_date (timestamp, default: now())
telegram_message_id (bigint, nullable)
synced_at (timestamp, nullable)
deleted_at (timestamp, nullable - soft delete)
created_at, updated_at

Indexes:
- (chat_id, expense_date)
- (user_telegram_id)
- (chat_id, is_reimbursable, reimbursed_at)
- (sync_status)
```

### sync_logs
```
id (bigint)
source_table (enum: incomes, expenses)
source_id (bigint)
neon_id (bigint, nullable)
status (enum: success, failed, pruned)
synced_at (timestamp, nullable)
pruned_at (timestamp, nullable)
error_message (text, nullable)
created_at, updated_at

Indexes:
- (status, synced_at)
```

---

## Fallback Logic

### How It Works

```
User Request
    ↓
Middleware: EnsureDatabaseConnection
    ↓
DatabaseFallbackService::ensureConnection()
    ↓
    ├─ Current = pgsql?
    │  ├─ Test: SELECT 1 → Success → Stay on Neon ✅
    │  └─ Test: SELECT 1 → Timeout → Switch to SQLite ⚡
    │
    └─ Current = sqlite_fallback?
       ├─ Test Neon: SELECT 1 → Success → Switch back to Neon ✅
       └─ Test Neon: SELECT 1 → Fail → Stay on SQLite ⚡
    ↓
Request continues using active connection
```

### Components

#### 1. **DatabaseFallbackService**
Location: `app/Services/DatabaseFallbackService.php`

```php
// Entry point
DatabaseFallbackService::ensureConnection();

// Checks current connection and switches if needed
// - isPgsqlReachable() → test with SELECT 1
// - switchToFallback() → Use SQLite
// - switchToPrimary() → Use Neon
```

#### 2. **EnsureDatabaseConnection Middleware**
Location: `app/Http/Middleware/EnsureDatabaseConnection.php`

Runs on every HTTP request to check/switch database connection.

#### 3. **Configuration**
Location: `config/database.php`

Defines two connections:
- `pgsql` - Neon PostgreSQL (primary)
- `sqlite_fallback` - Local SQLite (fallback)

---

## Deployment Checklist

### Local Development
- ✅ SQLite schema migrated: `storage/fallback.sqlite`
- ✅ `.env`: `DB_CONNECTION=pgsql` (ready for Neon)

### Production Setup

1. **Create Neon Project**
   ```bash
   # At neon.tech console
   Create project → Copy DATABASE_URL
   ```

2. **Set Environment**
   ```bash
   # .env production
   DB_CONNECTION=pgsql
   DATABASE_URL=postgresql://user:password@ep-xxx.neon.tech/dbname?sslmode=require
   ```

3. **Copy SQLite Fallback**
   ```bash
   # Ensure storage/fallback.sqlite exists
   # (copy from local or will auto-create on first migration)
   ```

4. **Migrate Neon**
   ```bash
   php artisan migrate --database=pgsql
   ```

5. **Verify Connection**
   ```bash
   # Should succeed
   php artisan tinker
   > DB::select('SELECT 1');
   ```

---

## Operational Scenarios

### Scenario 1: Neon Downtime (1 minute)

```
t=0s   : User posts expense
         → Middleware checks: Neon OK ✅
         → Write to Neon

t=30s  : Network issue
         → Middleware checks: Neon timeout ❌
         → Auto switch to SQLite
         → User still can read/write (from SQLite)
         → Logged: "Neon DB unreachable, switching to SQLite fallback"

t=60s  : Neon recovers
         → Middleware checks: Neon OK ✅
         → Auto switch back to Neon
         → Logged: "Neon DB is back online, switching to primary"
         → sync_logs records the outage
```

### Scenario 2: Data Sync (SQLite → Neon)

After switching back from SQLite to Neon:

1. Find pending records: `sync_status = 'pending'`
2. Insert into Neon
3. Record in `sync_logs` with status='success'
4. Update `synced_at` & `sync_status='synced'`

(Implementation: Job/Command TBD)

### Scenario 3: OCR Processing (pending_ocr)

For expense entries from OCR:

1. Entry created with `source='ocr'` & `sync_status='pending_ocr'`
2. OCR processing queue runs
3. On success: `sync_status='synced'`, `source='ocr'`
4. Recorded in `sync_logs`

---

## Monitoring

### Check Current Connection

```bash
php artisan tinker

> config('database.default');
"pgsql"  // or "sqlite_fallback"

> DB::connection()->getName();
"pgsql"  // currently using
```

### Check Connection Health

```bash
php artisan tinker

> \App\Services\DatabaseFallbackService::isPgsqlReachable();
true  // Neon is reachable
```

### View Logs

```bash
# Check fallback events
tail -f storage/logs/laravel.log | grep "Neon DB"
```

### View Sync Logs

```bash
php artisan tinker

> DB::table('sync_logs')->latest()->take(20)->get();
// Shows recent sync operations
```

---

## Troubleshooting

### Neon Connection Failed

**Error**: `SQLSTATE[HY000]: General error: unable to connect to server`

**Solution**:
1. Verify DATABASE_URL in .env
2. Check Neon project status (neon.tech console)
3. Ensure sslmode=require in connection string
4. Check IP whitelist settings

### SQLite File Not Found

**Error**: `unable to open database file`

**Solution**:
```bash
# Ensure storage directory is writable
chmod -R 755 storage/

# Trigger auto-migration on sqlite_fallback
php artisan migrate --database=sqlite_fallback
```

### Stuck on Fallback

If system keeps using SQLite even when Neon is up:

```bash
# Check logs
tail -f storage/logs/laravel.log

# Manually test connection
php artisan tinker
> DB::connection('pgsql')->select('SELECT 1');

# Force switch
php artisan tinker
> \Illuminate\Support\Facades\Config::set('database.default', 'pgsql');
> \Illuminate\Support\Facades\DB::reconnect('pgsql');
```

---

## Development Tips

### Run Only on SQLite (Local)

```bash
# Force SQLite for testing
php artisan migrate --database=sqlite

# Reset
php artisan migrate:fresh --database=sqlite
```

### Run Only on Neon (Staging/Prod)

```bash
# Only if DATABASE_URL is set
php artisan migrate --database=pgsql
```

### Test Fallback Logic

```bash
php artisan tinker

// Simulate Neon down
> \Illuminate\Support\Facades\Config::set('database.default', 'sqlite_fallback');
> \Illuminate\Support\Facades\DB::reconnect('sqlite_fallback');

// Check logs show switch
> tail -f storage/logs/laravel.log
```

---

## References

- Neon Docs: https://neon.tech/docs
- Laravel Database: https://laravel.com/docs/13/database
- Soft Deletes: https://laravel.com/docs/13/eloquent#soft-deletes
- Migration: https://laravel.com/docs/13/migrations
