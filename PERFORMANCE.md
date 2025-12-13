# Performance Guide

Optimize Laravel Studio for production workloads.

---

## Quick Wins

### 1. Enable Configuration Caching

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Note:** Run these after any config changes.

### 2. Enable Permission Caching

```php
// config/studio.php
'authorization' => [
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
    ],
],
```

### 3. Enable Panel Caching

```php
// config/studio.php
'panels' => [
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
],
```

---

## Database Optimization

### Add Indexes

Create indexes for frequently queried columns:

```php
// Migration
Schema::table('users', function (Blueprint $table) {
    $table->index('status');
    $table->index('role_id');
    $table->index(['status', 'created_at']); // Composite index
});
```

**Columns to index:**
- Foreign keys (automatically indexed in some databases)
- Columns used in filters
- Columns used in sorting
- Columns used in search (consider full-text indexes)

### Optimize Eager Loading

Only load relationships you need:

```php
// In your resource
public function with(): array
{
    return ['category', 'author']; // Specific relations only
}
```

Avoid loading unnecessary data:

```php
// Bad: Loads all comments for every post
public function with(): array
{
    return ['comments'];
}

// Better: Load count instead
public function indexQuery(Builder $query): Builder
{
    return $query->withCount('comments');
}
```

### Select Specific Columns

The ActivityService demonstrates selective loading:

```php
protected function getActivityColumns(): array
{
    return [
        'id',
        'log_name',
        'description',
        'event',
        'created_at',
        // Only columns we need
    ];
}
```

Apply this pattern to your resources:

```php
public function indexQuery(Builder $query): Builder
{
    return $query->select(['id', 'name', 'email', 'status', 'created_at']);
}
```

---

## Pagination

### Use Appropriate Page Sizes

```php
// In your resource
public static int $perPage = 25; // Reasonable default

// For data-heavy resources
public static int $perPage = 15;
```

### Cursor Pagination for Large Datasets

For tables with millions of rows, consider cursor pagination:

```php
// Custom implementation in ResourceService
public function indexWithCursor(array $params = [])
{
    return $this->buildQuery($params)->cursorPaginate($this->resource::$perPage);
}
```

---

## Caching Strategies

### Cache Expensive Queries

```php
use Illuminate\Support\Facades\Cache;

// In your service
public function getStatistics(): array
{
    return Cache::remember('dashboard_stats', 300, function () {
        return [
            'users_count' => User::count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            // ... expensive calculations
        ];
    });
}
```

### Cache Dashboard Cards

Cards automatically cache when configured:

```php
// config/studio.php
'cards' => [
    'enabled' => true,
    'cache_ttl' => 300, // 5 minutes
],
```

### Invalidate Cache Appropriately

```php
// After creating/updating/deleting
Cache::forget('dashboard_stats');

// Or use tags
Cache::tags(['dashboard'])->flush();
```

---

## Query Monitoring

### Detect N+1 Queries

In development, use Laravel Debugbar or Telescope:

```bash
composer require barryvdh/laravel-debugbar --dev
```

Or add query logging:

```php
// In a service provider
if (config('app.debug')) {
    DB::listen(function ($query) {
        Log::debug($query->sql, $query->bindings);
    });
}
```

### Monitor Slow Queries

```php
// Log queries over 100ms
DB::listen(function ($query) {
    if ($query->time > 100) {
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

---

## Bulk Operations

### Configure Chunk Size

```php
// config/studio.php
'bulk_operations' => [
    'max_ids' => 1000,
    'chunk_size' => 100, // Process in batches
],
```

### Use Database Transactions

Bulk operations are wrapped in transactions for consistency:

```php
DB::transaction(function () use ($ids, $data) {
    foreach (array_chunk($ids, 100) as $chunk) {
        Model::whereIn('id', $chunk)->update($data);
    }
});
```

---

## Frontend Performance

### Build for Production

```bash
npm run build
```

This enables:
- Minification
- Tree shaking
- Code splitting
- Asset hashing

### Enable Gzip/Brotli

In nginx:
```nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript;
```

### Lazy Load Components

Vue routes are already lazy-loaded:

```javascript
const routes = [
    {
        path: '/admin/users',
        component: () => import('./pages/Users.vue'), // Lazy loaded
    },
];
```

---

## Activity Log Management

### Regular Cleanup

Schedule automatic cleanup:

```php
// app/Console/Kernel.php
$schedule->command('studio:cleanup-activities')
         ->daily()
         ->at('03:00');
```

### Adjust Retention

```php
// config/studio.php
'activity_log' => [
    'cleanup_days' => 30, // Keep 30 days
],
```

### Disable for High-Traffic Resources

```php
// In your model
protected static function booted()
{
    // Skip activity logging for this model
    static::disableLogging();
}
```

---

## Server Configuration

### PHP OPcache

Enable in `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  ; Set to 1 in development
```

### Database Connection Pooling

For high traffic, use connection pooling (PgBouncer for PostgreSQL, ProxySQL for MySQL).

### Queue Workers

Offload heavy tasks to queues:

```php
// Instead of inline processing
ProcessExport::dispatch($ids);
```

Run workers:
```bash
php artisan queue:work --daemon
```

---

## Benchmarking

### Measure Response Times

```php
// In middleware
$start = microtime(true);

$response = $next($request);

$duration = microtime(true) - $start;
Log::info("Request took {$duration}s", [
    'path' => $request->path(),
]);

return $response;
```

### Load Testing

Use tools like Apache Bench or k6:

```bash
ab -n 1000 -c 10 http://localhost:8000/api/panels/admin/resources/users
```

---

## Performance Checklist

### Database
- [ ] Indexes on foreign keys
- [ ] Indexes on filtered/sorted columns
- [ ] Eager loading configured
- [ ] Selective column loading
- [ ] Query caching where appropriate

### Application
- [ ] Config cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] Permission caching enabled
- [ ] Panel caching enabled

### Frontend
- [ ] Production build (`npm run build`)
- [ ] Assets minified
- [ ] Gzip/Brotli enabled
- [ ] CDN for static assets (optional)

### Server
- [ ] OPcache enabled
- [ ] Adequate PHP memory limit
- [ ] Queue workers for background tasks
- [ ] Database connection pooling (high traffic)

---

## Monitoring Tools

- **Laravel Telescope:** Development debugging
- **Laravel Horizon:** Queue monitoring
- **Debugbar:** Query and memory profiling
- **New Relic/Datadog:** Production APM
- **Blackfire:** PHP profiling
