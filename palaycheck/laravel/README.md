# PalayCheck (Laravel)

Laravel 12 application for PalayCheck. See the project-level
[`README.md`](../README.md) for full setup instructions.

## Quick start

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Tests

```powershell
php artisan test
```

## Key locations

- `routes/web.php` - public, farmer, and admin routes
- `app/Http/Controllers` - controllers, including `Admin/` and `Api/`
- `app/Services/DetectionService.php` - local inference and Roboflow fallback
- `resources/views` - Blade templates
- `public/static` - CSS, pest images, and uploaded scan images
- `config/palaycheck.php` - detection and storage configuration
