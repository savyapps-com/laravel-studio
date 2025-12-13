# Troubleshooting Guide

Common issues and solutions for Laravel Studio.

---

## Installation Issues

### Migration fails with "table already exists"

**Symptom:**
```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'permissions' already exists
```

**Solution:**
Run a fresh migration or use the `--force` flag:
```bash
php artisan migrate:fresh
# or
php artisan migrate --force
```

If you have existing data you want to keep, check which migrations have already run:
```bash
php artisan migrate:status
```

---

### Migration fails with "foreign key constraint" error

**Symptom:**
```
SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint
```

**Cause:** Migrations are running out of order, or referenced tables don't exist.

**Solution:**
1. Ensure the package migrations run before your app migrations
2. Check that `roles` table exists before `role_permissions` migrates
3. Run `php artisan migrate:fresh` to start clean

---

### "Class not found" after installation

**Symptom:**
```
Class 'App\Models\Role' not found
```

**Solution:**
1. Regenerate autoload files:
   ```bash
   composer dump-autoload
   ```

2. Clear Laravel caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

3. Verify the installer copied all model files:
   ```bash
   ls app/Models/
   ```

---

### Frontend assets not loading

**Symptom:** Admin panel shows blank page or missing styles.

**Solution:**
1. Install npm dependencies:
   ```bash
   npm install
   ```

2. Build assets:
   ```bash
   npm run build
   ```

3. For development with hot reload:
   ```bash
   npm run dev
   ```

4. Check that `vite.config.js` was installed correctly.

---

## Authentication Issues

### "Unauthenticated" error on API calls

**Symptom:**
```json
{"message": "Unauthenticated."}
```

**Causes & Solutions:**

1. **Missing Sanctum setup:**
   ```bash
   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
   ```

2. **Session domain mismatch (SPA):** Update `.env`:
   ```
   SESSION_DOMAIN=localhost
   SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
   ```

3. **CORS issues:** Check `config/cors.php`:
   ```php
   'supports_credentials' => true,
   ```

---

### User can't access admin panel

**Symptom:** User is logged in but sees "Access Denied" for panel.

**Solution:**
1. Check user has the correct role:
   ```php
   $user->roles()->attach($role); // or
   $user->assignRole('admin');
   ```

2. Verify panel configuration in `config/studio.php`:
   ```php
   'panels' => [
       'admin' => [
           'roles' => ['admin'], // User must have this role
       ],
   ],
   ```

3. Clear permission cache:
   ```bash
   php artisan cache:clear
   ```

---

## Resource Issues

### Resource not showing in panel

**Symptom:** Resource is registered but doesn't appear in the panel menu.

**Solution:**
1. Add resource to panel configuration:
   ```php
   // config/studio.php
   'panels' => [
       'admin' => [
           'resources' => ['users', 'your-resource'], // Add here
       ],
   ],
   ```

2. Check resource is registered:
   ```php
   // config/studio.php
   'resources' => [
       'your-resource' => [
           'class' => \App\Resources\YourResource::class,
       ],
   ],
   ```

---

### Validation errors not displaying

**Symptom:** Form submission fails silently.

**Solution:**
1. Check browser console for JavaScript errors
2. Verify API returns proper validation format:
   ```json
   {
     "message": "Validation failed",
     "errors": {
       "name": ["The name field is required."]
     }
   }
   ```

3. Ensure resource has validation rules:
   ```php
   public function rules(string $context): array
   {
       return [
           'name' => 'required|string|max:255',
       ];
   }
   ```

---

### BelongsTo dropdown empty

**Symptom:** Related resource dropdown shows no options.

**Solution:**
1. Check the related resource exists and is registered
2. Verify the search endpoint works:
   ```
   GET /api/panels/{panel}/resources/{resource}/search?query=test
   ```

3. Ensure the related model returns searchable data:
   ```php
   public static string $title = 'name'; // Column to display
   public static array $search = ['name', 'email']; // Searchable columns
   ```

---

## Database Issues

### "Could not find driver" error

**Symptom:**
```
PDOException: could not find driver
```

**Solution:**
Install the appropriate PHP extension:
```bash
# For MySQL
sudo apt install php-mysql

# For PostgreSQL
sudo apt install php-pgsql

# For SQLite
sudo apt install php-sqlite3
```

Then restart your web server:
```bash
sudo service php8.2-fpm restart
# or
sudo service apache2 restart
```

---

### Activity log growing too large

**Symptom:** Database size increasing rapidly due to activity logs.

**Solution:**
1. Run the cleanup command:
   ```bash
   php artisan studio:cleanup-activities
   ```

2. Schedule automatic cleanup in `app/Console/Kernel.php`:
   ```php
   $schedule->command('studio:cleanup-activities')
            ->daily();
   ```

3. Adjust retention period in `config/studio.php`:
   ```php
   'activity_log' => [
       'cleanup_days' => 30, // Keep last 30 days
   ],
   ```

---

## Performance Issues

### Slow resource index loading

**Symptoms:** Index page takes long to load, especially with many records.

**Solutions:**

1. **Add database indexes:**
   ```php
   // In migration
   $table->index(['status', 'created_at']);
   ```

2. **Limit eager loading:**
   ```php
   public function with(): array
   {
       return ['category']; // Only load needed relations
   }
   ```

3. **Reduce per-page count:**
   ```php
   public static int $perPage = 15; // Default is 25
   ```

4. **Enable query caching** (if available in your setup)

---

### API requests timing out

**Symptom:** Bulk operations fail with timeout errors.

**Solution:**
1. Reduce bulk operation batch size in `config/studio.php`:
   ```php
   'bulk_operations' => [
       'max_ids' => 100, // Reduce from 1000
       'chunk_size' => 50,
   ],
   ```

2. Increase PHP timeout (if needed):
   ```php
   // In controller or middleware
   set_time_limit(300);
   ```

---

## Common Error Messages

| Error | Likely Cause | Solution |
|-------|--------------|----------|
| `Resource not found: xyz` | Resource not registered | Add to `config/studio.php` resources |
| `Resource 'xyz' is not available in panel` | Resource not in panel config | Add to panel's resources array |
| `CSRF token mismatch` | Session expired or CORS issue | Refresh page, check CORS config |
| `Method not allowed` | Wrong HTTP method | Check API route expects GET/POST/etc |
| `Too Many Requests` | Rate limiting | Wait or adjust rate limits |

---

## Getting Help

If you're still experiencing issues:

1. **Check the logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Enable debug mode** (development only):
   ```
   APP_DEBUG=true
   ```

3. **Report an issue:** https://github.com/savyapps-com/laravel-studio/issues

When reporting, include:
- Laravel version (`php artisan --version`)
- Laravel Studio version
- PHP version (`php -v`)
- Error message and stack trace
- Steps to reproduce
