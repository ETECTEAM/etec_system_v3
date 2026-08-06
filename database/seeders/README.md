# Database Seeders

Run default required seeders:

```bash
php artisan db:seed
```

Run core seeders only:

```bash
php artisan db:seed --class="Database\Seeders\Core\CoreSeeder"
```

Run class seeders only:

```bash
php artisan db:seed --class="Database\Seeders\Class\ClassSeeder"
```

Run report feature seeders only:

```bash
php artisan db:seed --class="Database\Seeders\Feature\Report\ReportSeeder"
php artisan db:seed --class="Database\Seeders\Feature\Report\LandRentReportSeeder"
```

Run invoice test seeder only:

```bash
php artisan db:seed --class="Database\Seeders\Feature\Invoice\InvoiceTestSeeder"
```

Run one core seeder only:

```bash
php artisan db:seed --class="Database\Seeders\Core\UserSeeder"
```
