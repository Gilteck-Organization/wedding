php artisan queue:work --stop-when-empty --tries=3 --timeout=60
# 3. Keep worker running permanently (Supervisor example)
php artisan queue:work --tries=3 --timeout=60