# WPMoo

WordPress Micro Object-Oriented Framework (PHP 7.4+).

## Quick start
```bash
composer install
php bin/moo info
```

## Bootstrap in a plugin
```php
use WPMoo\Core\App;
require __DIR__ . '/vendor/autoload.php';
App::instance()->boot(__FILE__, 'your-plugin-textdomain');
```
