<?php
/**
 * Bootstrap for WordPress integration tests.
 */
declare(strict_types=1);

// Composer autoload (project under test).
$autoload = __DIR__ . '/../vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require $autoload;
} elseif ( file_exists( __DIR__ . '/../../vendor/autoload.php' ) ) {
	require __DIR__ . '/../../vendor/autoload.php';
}

// Location of the WordPress PHPUnit test library.
$wp_php_unit_dir = getenv( 'WP_PHPUNIT__DIR' );
if ( ! $wp_php_unit_dir ) {
	$wp_php_unit_dir = '/tmp/wordpress-develop/tests/phpunit';
}

$bootstrap = rtrim( $wp_php_unit_dir, '/\n\r\t ' ) . '/includes/bootstrap.php';
if ( ! file_exists( $bootstrap ) ) {
	fwrite( STDERR, "WordPress test bootstrap not found: {$bootstrap}\n" );
	throw new \RuntimeException( 'WordPress test bootstrap not found.' );
}

require $bootstrap;
