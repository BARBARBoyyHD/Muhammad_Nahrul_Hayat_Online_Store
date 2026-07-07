# Online Store API

RESTful JSON API for an online store with flash sale capabilities and race condition protection. Built with Laravel 13 + Neon DB (Postgres 16).

## Features

- **Product listing** — browse products with inventory quantities
- **Order placement** — place orders with multiple items, stock validation, and price snapshots
- **Flash sales** — discounted pricing on products with concurrent-safe stock deduction
- **Race condition protection** — pessimistic row-level locking (`SELECT ... FOR UPDATE`) prevents overselling during flash sale bursts
- **Inventory integrity** — database-level `CHECK (quantity >= 0)` constraint as a safety net

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13.x |
| Language | PHP 8.3+ |
| Database | Neon DB (Postgres 16) |
| HTTP Client | Guzzle (with `Http::pool()` for concurrent testing) |
| Testing | PHPUnit |

## Setup

```bash
# 1. Clone & install dependencies
cd apps/online-store
composer install

# 2. Configure environment
cp .env.example .env
# Edit .env with your Neon DB credentials:
#   DB_CONNECTION=pgsql
#   DB_HOST=<your-neon-host>
#   DB_PORT=5432
#   DB_DATABASE=<your-db-name>
#   DB_USERNAME=<your-db-user>
#   DB_PASSWORD=<your-db-password>
#   DB_SSLMODE=require

# 3. Generate app key
php artisan key:generate

# 4. Run migrations & seed
php artisan migrate --seed

# 5. Start the dev server
php artisan serve --port=8000
```

## API Endpoints

### Products

| Method | Endpoint | Description | Response |
|--------|----------|-------------|----------|
| GET | `/api/products` | List all products with inventory | 200 |
| GET | `/api/products/{id}` | Single product detail | 200, 404 |

### Orders

| Method | Endpoint | Description | Response |
|--------|----------|-------------|----------|
| POST | `/api/orders` | Place an order | 201, 409, 422 |
| GET | `/api/orders` | List all orders with items | 200 |
| GET | `/api/orders/{id}` | Single order detail | 200, 404 |

### Example Requests

**List products:**
```bash
curl http://localhost:8000/api/products
```

**Place an order:**
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{"items": [{"product_id": 1, "quantity": 2}]}'
```

**PowerShell:**
```powershell
$body = @{ items = @(@{ product_id = 1; quantity = 2 }) } | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost:8000/api/orders" -Method Post -Body $body -ContentType "application/json"
```

**Success response (201):**
```json
{
  "data": {
    "id": 1,
    "items": [
      { "product_id": 1, "quantity": 2, "unit_price": 5000.00 }
    ],
    "total": 10000.00,
    "created_at": "2026-07-07T12:00:00Z"
  }
}
```

**Insufficient stock (409):**
```json
{
  "error": "insufficient_stock",
  "message": "Product 'Limited Sneakers' only has 0 units remaining."
}
```

## Race Condition Handling

During a flash sale, multiple customers compete for limited discounted inventory. Without safeguards, concurrent requests could read stale stock and oversell.

### How It Works

```
1. BEGIN TRANSACTION
2. SELECT quantity FROM inventory WHERE product_id = ? FOR UPDATE
   └── Locks the row; other transactions wait
3. CHECK quantity >= requested
   ├── YES → proceed
   └── NO  → ROLLBACK, return 409 Conflict
4. UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?
5. INSERT INTO orders / order_items
6. COMMIT
```

### Key Safeguards

- **`lockForUpdate()`** — issues `SELECT ... FOR UPDATE` (Postgres row-level lock). Competing transactions queue up and read fresh data
- **Application-level check** — verifies `quantity >= requested` inside the lock before deducting
- **Database `CHECK` constraint** — `CHECK (quantity >= 0)` on the `inventory` table prevents negative stock even if the application check is bypassed

### Implementation

See `app/Services/OrderService.php`:

```php
DB::transaction(function () use ($items) {
    $inventory = Inventory::where('product_id', $productId)
        ->lockForUpdate()
        ->firstOrFail();

    if ($inventory->quantity < $requestedQty) {
        abort(409, 'Insufficient stock');
    }

    $inventory->decrement('quantity', $requestedQty);
    // create order + order items...
});
```

## Testing

### Run all tests
```bash
php artisan test
```

### Race condition test

The `FlashSaleRaceConditionTest` fires 15 concurrent orders at a product with 5 units and asserts exactly 5 succeed and 10 are rejected. This requires the dev server to be running:

```bash
# Terminal 1: Start the server
php artisan serve --port=8000

# Terminal 2: Reset DB, seed, and run the test
php artisan migrate:fresh --seed
php artisan test --filter=FlashSale
```

## Deployment (Vercel)

This project includes Vercel deployment config (`vercel.json`, `api/index.php`).

1. Push the repo to GitHub
2. Import to Vercel (Framework Preset: **Other**)
3. Set environment variables in Vercel dashboard:
   - `APP_KEY` — generate with `php artisan key:generate --show`
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `DB_CONNECTION=pgsql`
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE=require`
   - `SESSION_DRIVER=cookie`
   - `CACHE_STORE=array`
   - `LOG_CHANNEL=stderr`
4. Deploy

Your Neon DB (Postgres) handles data — Vercel handles requests.
