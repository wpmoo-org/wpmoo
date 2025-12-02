<?php
declare(strict_types=1);

// Define ABSPATH for library guards when running outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

require dirname( __DIR__ ) . '/vendor/autoload.php';
