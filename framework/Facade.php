<?php

namespace WPMoo;

use ReflectionClass;
use WPMoo\Field\Field;
use WPMoo\Shared\Helper\ValidationHelper;

/**
 * Abstract Facade class for creating static interfaces to the WPMoo framework services.
 *
 * @package WPMoo
 * @since 0.1.0
 */
abstract class Facade {

	/**
	 * Holds the automatically detected app IDs for each child facade.
	 *
	 * @var array<string, string>
	 */
	protected static array $app_ids = array();

	/**
	 * Handle dynamic static method calls into the facade.
	 *
	 * @param string       $method Method name.
	 * @param array<mixed> $args Method arguments.
	 * @return mixed
	 */
	public static function __callStatic( $method, $args ) {
		$called_class = static::class;

		// If the ID for this specific facade has not been determined yet, detect and store it.
		if ( ! isset( static::$app_ids[ $called_class ] ) ) {
			static::$app_ids[ $called_class ] = static::detect_app_id();
		}

		// Use the app_id specific to the child class that was called.
		$app_id = static::$app_ids[ $called_class ];

		return Core::get( $app_id )->$method( ...$args );
	}

	/**
	 * Create an input field.
	 *
	 * @param string $id Field ID.
	 * @return \WPMoo\Field\Type\Input
	 */
	public static function input( string $id ) {
		return Field::input( $id );
	}

	/**
	 * Create a textarea field.
	 *
	 * @param string $id Field ID.
	 * @return \WPMoo\Field\Type\Textarea
	 */
	public static function textarea( string $id ) {
		return Field::textarea( $id );
	}

	/**
	 * Create a toggle field.
	 *
	 * @param string $id Field ID.
	 * @return \WPMoo\Field\Type\Toggle
	 */
	public static function toggle( string $id ) {
		return Field::toggle( $id );
	}


	/**
	 * Create a field using the field type registry.
	 *
	 * @param string $type The field type slug.
	 * @param string $id The field ID.
	 * @return \WPMoo\Field\Interfaces\FieldInterface|null The field instance or null if type is not registered.
	 */
	public static function create_field( string $type, string $id ) {
		$app_id = static::detect_app_id();
		return Core::get( $app_id )->create_field( $type, $id );
	}

	/**
	 * Create a layout component using the layout type registry.
	 *
	 * @param string $type The layout type slug.
	 * @param string $id The layout ID.
	 * @param string $title The layout title (where applicable).
	 * @return \WPMoo\Layout\Interfaces\LayoutInterface|null The layout instance or null if type is not registered.
	 */
	public static function create_layout( string $type, string $id, string $title = '' ) {
		$app_id = static::detect_app_id();
		return Core::get( $app_id )->create_layout( $type, $id, $title );
	}

	/**
	 * Magic Method: Extracts the slug from the file path of the inheriting class.
	 *
	 * @return string The detected app ID.
	 * @throws \RuntimeException If the class file path is not found.
	 */
	public static function detect_app_id(): string {
		// 1. Get the identity of the class calling this method (inheriting class) using Reflection.
		$reflector = new ReflectionClass( static::class );

		// 2. Get the full path of the file where the class is located.
		// E.g.: /var/www/html/wp-content/plugins/super-form/src/App.php.
		$file_path = $reflector->getFileName();

		if ( ! $file_path ) {
			throw new \RuntimeException( 'Class file path not found.' );
		}

		// 3. Make the path plugin-relative using a WordPress function.
		// Result: super-form/src/App.php.
		$plugin_basename = plugin_basename( $file_path );

		// 4. Check if plugin_basename returned an unexpected absolute path (e.g., starts with directory like 'Users').
		// This can happen when the plugin is symlinked from outside the standard WordPress directory structure.
		$parts = explode( '/', $plugin_basename );

		// If the first part looks like a system directory instead of a plugin slug, we need to determine the slug differently.
		if ( isset( $parts[0] ) && ( 'Users' === $parts[0] || 'home' === $parts[0] || 'var' === $parts[0] || 'opt' === $parts[0] ) ) {
			// Alternative approach: get the plugin slug from the file path by finding the plugin directory.
			$plugin_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';

			if ( 0 === strpos( $file_path, $plugin_dir ) ) {
				// Extract the relative path from the plugin directory.
				$relative_path = substr( $file_path, strlen( $plugin_dir ) + 1 );
				$parts = explode( '/', $relative_path );
			} else {
				// If the file is not in the standard plugin directory, try to get the directory name from the path.
				// Find the plugin directory name by looking for the last directory before 'src' or other common directories.
				$path_parts = explode( '/', $file_path );

				// Look for the plugin directory name in the path.
				$plugin_dir_name = '';
				for ( $i = count( $path_parts ) - 1; $i >= 0; $i-- ) {
					if ( 'src' === $path_parts[ $i ] || 'includes' === $path_parts[ $i ] || 'admin' === $path_parts[ $i ] ) {
						// The parent directory is likely the plugin directory.
						if ( isset( $path_parts[ $i - 1 ] ) ) {
							$plugin_dir_name = $path_parts[ $i - 1 ];
							break;
						}
					}
				}

				if ( ! empty( $plugin_dir_name ) ) {
					$parts = array( $plugin_dir_name );
				} else {
					// Fallback: use the directory name from the symlink.
					$real_path = realpath( $file_path );
					if ( $real_path ) {
						$path_parts = explode( '/', dirname( $real_path ) );
						$parts = array( end( $path_parts ) );
					} else {
						// Last fallback: use the last directory in the original path.
						$path_parts = explode( '/', dirname( $file_path ) );
						$parts = array( end( $path_parts ) );
					}
				}
			}
		}

		// 5. Get the part before the first '/' (Folder name = Slug).
		// Result: super-form.
		$slug = $parts[0];

		if ( str_ends_with( $slug, '.php' ) ) {
			$slug = basename( $slug, '.php' );
		}

		// Validate that the slug is a proper plugin slug.
		ValidationHelper::validate_plugin_slug( $slug );

		return $slug;
	}
}
