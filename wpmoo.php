<?php
/**
 * Plugin Name: WPMoo Framework
 * Description: WordPress Micro Object-Oriented Framework (PHP 7.4+)
 * Version: 0.1.0
 * Author: You
 * Text Domain: wpmoo
 */

use WPMoo\Core\App;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

if (class_exists(App::class)) {
    App::instance()->boot(__FILE__, 'wpmoo');
}
