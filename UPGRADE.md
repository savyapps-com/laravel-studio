# Upgrade Guide

Instructions for upgrading between Laravel Studio versions.

---

## General Upgrade Steps

1. **Backup your database:**
   ```bash
   php artisan backup:run  # If using spatie/laravel-backup
   # or
   mysqldump -u user -p database > backup.sql
   ```

2. **Update the package:**
   ```bash
   composer update savyapps-com/laravel-studio
   ```

3. **Run migrations:**
   ```bash
   php artisan migrate
   ```

4. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   ```

5. **Rebuild frontend (if applicable):**
   ```bash
   npm install
   npm run build
   ```

---

## Version-Specific Guides

### Upgrading to v1.1.0

#### Breaking Changes

1. **Migration Ordering**

   The `roles` table is now created by the package migration. If you have an existing `roles` table with a different structure, you may need to:

   ```bash
   # Option 1: Let package manage roles (recommended)
   php artisan migrate

   # Option 2: Keep your existing structure
   # Add to your AppServiceProvider@boot:
   Schema::defaultStringLength(191);
   ```

2. **Media Field Security**

   SVG files are now blocked by default in `images()` method. If you need SVG:

   ```php
   // Before (v1.0.x)
   Media::make('Logo')->images(); // SVG was allowed

   // After (v1.1.0)
   Media::make('Logo')->imagesWithSvg(); // Explicitly allow SVG
   ```

3. **ActivityService Changes**

   The `getActivities()` method now uses selective column loading. If you're extending this service and relying on all columns:

   ```php
   // Override to get all columns
   protected function getActivityColumns(): array
   {
       return ['*'];
   }
   ```

#### New Features

- File extension whitelist for Media field
- Improved error handling in ResourceController
- N+1 query optimizations

#### Configuration Changes

No configuration changes required.

---

### Upgrading to v1.0.0 (from beta)

#### Breaking Changes

1. **Panel Configuration**

   Panel configuration moved from `config/admin.php` to `config/studio.php`:

   ```php
   // Before
   // config/admin.php
   return [
       'panels' => [...],
   ];

   // After
   // config/studio.php
   return [
       'panels' => [...],
   ];
   ```

   Migration:
   ```bash
   # Copy your panel config to studio.php
   # Delete config/admin.php
   ```

2. **Middleware Changes**

   Separate admin/user middleware consolidated into `EnsurePanelAccess`:

   ```php
   // Before
   Route::middleware(['admin'])->group(...);

   // After
   Route::middleware(['panel:admin'])->group(...);
   ```

3. **Country/Timezone Models Removed**

   If you were using the built-in Country/Timezone models, you'll need to create your own or use a package.

---

## Database Migrations

### Adding New Migrations After Upgrade

If new migrations are added in an upgrade:

```bash
# Run pending migrations
php artisan migrate

# Or see what will run
php artisan migrate --pretend
```

### Rolling Back (if needed)

```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback specific steps
php artisan migrate:rollback --step=2
```

---

## Configuration Changes

### Checking for New Config Options

After upgrading, compare your config with the package defaults:

```bash
# Publish fresh config (don't overwrite)
php artisan vendor:publish --provider="SavyApps\LaravelStudio\LaravelStudioServiceProvider" --tag="config"

# Compare with your existing config/studio.php
diff config/studio.php config/studio.php.backup
```

### Merging New Options

Add any new options from the published config to your existing config file.

---

## Frontend Assets

### When to Rebuild

Rebuild frontend assets when:
- Upgrading the package
- Vue component updates
- New features added

```bash
npm install
npm run build
```

### Clearing Browser Cache

After rebuilding, users may need to clear their browser cache or do a hard refresh (Ctrl+Shift+R).

---

## Testing After Upgrade

### Verify Core Functionality

1. **Login/Authentication:**
   - Can users log in?
   - Are sessions persisting?

2. **Panel Access:**
   - Can admin access admin panel?
   - Are permissions working?

3. **CRUD Operations:**
   - Can you list resources?
   - Can you create/edit/delete?

4. **Search & Filters:**
   - Does global search work?
   - Do resource filters work?

### Run Tests

```bash
php artisan test
# or
./vendor/bin/phpunit
```

---

## Troubleshooting Upgrades

### Migration Conflicts

**Problem:** Migration fails with "table already exists"

**Solution:**
```bash
# Check migration status
php artisan migrate:status

# If needed, mark migrations as run
php artisan migrate:refresh --path=database/migrations/specific_migration.php
```

### Class Not Found After Upgrade

**Problem:** `Class 'X' not found`

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

### Frontend Not Updating

**Problem:** Changes don't appear after rebuild

**Solution:**
```bash
# Clear Vite cache
rm -rf node_modules/.vite

# Rebuild
npm run build

# Clear browser cache or use incognito
```

### Permission Issues

**Problem:** Users lost permissions after upgrade

**Solution:**
```bash
# Re-sync permissions
php artisan studio:sync-permissions

# Clear permission cache
php artisan cache:clear
```

---

## Getting Help

If you encounter issues during upgrade:

1. Check the [TROUBLESHOOTING.md](TROUBLESHOOTING.md) guide
2. Search existing [GitHub issues](https://github.com/savyapps-com/laravel-studio/issues)
3. Create a new issue with:
   - Previous version
   - Target version
   - Error messages
   - Steps to reproduce

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.
