# Security Guide

Best practices and security considerations for Laravel Studio.

---

## Reporting Vulnerabilities

If you discover a security vulnerability, please report it responsibly:

1. **Do NOT** create a public GitHub issue
2. Email security concerns to: security@savyapps.com
3. Include detailed reproduction steps
4. Allow reasonable time for a fix before public disclosure

---

## Built-in Security Features

### SQL Injection Prevention

Laravel Studio uses parameterized queries and validates all user input:

```php
// Sort columns are whitelisted
protected function applySort(Builder $query, string $column, string $direction): void
{
    $allowedColumns = $this->getSortableColumns();

    if (!in_array($column, $allowedColumns, true)) {
        $query->orderBy('id', 'desc'); // Fall back to safe default
        return;
    }

    $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
    $query->orderBy($column, $direction);
}
```

**Best Practice:** Only mark columns as `sortable()` that are safe to sort by.

---

### XSS Protection

- Vue 3 auto-escapes all template expressions
- User input is never rendered as raw HTML
- API responses are JSON-encoded

**Best Practice:** Never use `v-html` with user-provided content.

---

### CSRF Protection

API routes use Laravel Sanctum for authentication:
- SPA requests use session-based CSRF tokens
- API tokens are stateless and don't require CSRF

**Configuration:**
```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost')),
```

---

### Authorization

Multi-layer authorization system:

1. **Panel Access:** User must have panel's required role
2. **Resource Access:** User must have resource permission
3. **Action Authorization:** Individual CRUD operations checked

```php
// Resource authorization
public function authorize(string $ability, $model = null): void
{
    if (!auth()->user()->can("{$this->permissionGroup()}.{$ability}")) {
        abort(403, "Unauthorized to {$ability} this resource.");
    }
}
```

---

### File Upload Security

The Media field includes security features:

```php
// Dangerous extensions are always blocked
protected static array $dangerousExtensions = [
    'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
    'exe', 'bat', 'cmd', 'com', 'msi', 'dll', 'scr',
    'js', 'vbs', 'sh', 'py', 'pl', 'rb',
    'asp', 'aspx', 'jsp', 'cfm',
    'htaccess', 'htpasswd', 'config',
    'svg', // SVG can contain JavaScript
];
```

**Best Practices:**

1. Use explicit extension whitelists:
   ```php
   Media::make('Avatar')->images(); // Only jpg, jpeg, png, gif, webp
   ```

2. Set file size limits:
   ```php
   Media::make('Document')->documents()->maxFileSize(10); // 10MB max
   ```

3. If you need SVG, use explicit method:
   ```php
   Media::make('Logo')->imagesWithSvg(); // Explicitly allow SVG
   ```

4. Store uploads outside web root when possible

---

## Security Configuration

### Rate Limiting

Configure rate limits in `routes/api.php`:

```php
Route::middleware(['throttle:60,1'])->group(function () {
    // 60 requests per minute
});
```

For bulk operations:
```php
// config/studio.php
'bulk_operations' => [
    'max_ids' => 100, // Limit bulk operation size
],
```

---

### Permission Caching

Enable permission caching in production:

```php
// config/studio.php
'authorization' => [
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
    ],
],
```

Clear cache when permissions change:
```bash
php artisan cache:clear
```

---

### Session Security

Recommended `.env` settings for production:

```env
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

---

### CORS Configuration

Restrict CORS to your domains in `config/cors.php`:

```php
'allowed_origins' => [
    'https://your-domain.com',
    'https://admin.your-domain.com',
],
'supports_credentials' => true,
```

---

## Security Checklist

### Before Deployment

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secured
- [ ] File permissions set correctly (755 for dirs, 644 for files)
- [ ] Storage and bootstrap/cache writable by web server only
- [ ] SSL/HTTPS enabled
- [ ] Session cookies marked as secure

### Authentication

- [ ] Strong password requirements enforced
- [ ] Account lockout after failed attempts (optional)
- [ ] Session timeout configured
- [ ] API tokens have appropriate expiration

### Authorization

- [ ] All resources have proper permission checks
- [ ] Super admin role used sparingly
- [ ] Sensitive resources require additional verification
- [ ] Bulk operations limited appropriately

### Data Protection

- [ ] Sensitive data encrypted at rest
- [ ] Passwords hashed (Laravel default)
- [ ] Personal data handling complies with regulations (GDPR, etc.)
- [ ] Activity logging enabled for audit trail
- [ ] Regular database backups

### File Uploads

- [ ] File extension whitelist configured
- [ ] File size limits set
- [ ] Uploads stored outside web root (or protected)
- [ ] Antivirus scanning (if applicable)

---

## Secure Coding Practices

### Input Validation

Always validate input in your resources:

```php
public function rules(string $context): array
{
    return [
        'email' => 'required|email|unique:users,email',
        'name' => 'required|string|max:255',
        'role_id' => 'required|exists:roles,id',
    ];
}
```

### Output Encoding

When displaying user data in custom components:

```vue
<!-- Good: Auto-escaped -->
<span>{{ user.name }}</span>

<!-- Bad: Raw HTML -->
<span v-html="user.bio"></span>
```

### Database Queries

Use Eloquent or query builder:

```php
// Good: Parameterized
User::where('email', $request->email)->first();

// Bad: Raw query with interpolation
DB::select("SELECT * FROM users WHERE email = '$email'"); // NEVER do this
```

### Sensitive Data

Never expose in API responses:

```php
// In your resource
public function transform(Model $model): array
{
    return [
        'id' => $model->id,
        'name' => $model->name,
        'email' => $model->email,
        // NEVER include: password, api_token, remember_token, etc.
    ];
}
```

---

## Security Updates

Stay updated:

1. **Watch the repository** for security announcements
2. **Update dependencies** regularly:
   ```bash
   composer update
   npm update
   ```
3. **Run security audits:**
   ```bash
   composer audit
   npm audit
   ```

---

## Additional Resources

- [Laravel Security](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
