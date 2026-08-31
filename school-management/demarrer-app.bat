@echo off
set "PATH=C:\php8;%PATH%"
set "PHP_INI_SCAN_DIR=%~dp0php-conf"
php artisan serve --host=0.0.0.0 --port=8000
 



