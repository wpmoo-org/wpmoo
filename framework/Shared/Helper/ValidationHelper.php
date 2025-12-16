<?php

namespace WPMoo\Shared\Helper;

/**
 * Validation helper for WPMoo framework.
 *
 * Provides validation functions for various component types to ensure
 * proper format and security.
 *
 * @package WPMoo\Shared\Helper
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class ValidationHelper {

	/**
	 * Validates a component ID format.
	 *
	 * @param string $id The ID to validate.
	 * @param string $component_type The type of component (for error messages).
	 * @return bool True if valid, throws exception if invalid.
	 * @throws \InvalidArgumentException If the ID format is invalid.
	 */
	public static function validate_id_format( string $id, string $component_type = 'component' ): bool {
		if ( empty( $id ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: %s: Component type */
					esc_html__( '%s ID cannot be empty.', 'wpmoo' ),
					esc_html( $component_type )
				)
			);
		}

		if ( ! preg_match( '/^[a-z0-9_-]+$/', $id ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: 1: Component type 2: Invalid ID */
					esc_html__( 'Invalid %1$s ID: %2$s. Must contain only lowercase letters, numbers, hyphens, and underscores.', 'wpmoo' ),
					esc_html( $component_type ),
					esc_html( $id )
				)
			);
		}

		return true;
	}

	/**
	 * Validates a plugin slug format.
	 *
	 * @param string $slug The slug to validate.
	 * @return bool True if valid, throws exception if invalid.
	 * @throws \InvalidArgumentException If the slug format is invalid.
	 */
	public static function validate_plugin_slug( string $slug ): bool {
		if ( empty( $slug ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Plugin slug cannot be empty.', 'wpmoo' ) );
		}

		if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: %s: Invalid plugin slug */
					esc_html__( 'Invalid plugin slug: %s. Must contain only lowercase letters, numbers, and hyphens.', 'wpmoo' ),
					esc_html( $slug )
				)
			);
		}

		return true;
	}

	/**
	 * Validates a version string format.
	 *
	 * @param string $version The version to validate.
	 * @return bool True if valid, throws exception if invalid.
	 * @throws \InvalidArgumentException If the version format is invalid.
	 */
	public static function validate_version_format( string $version ): bool {
		if ( empty( $version ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Version cannot be empty.', 'wpmoo' ) );
		}

		// Basic semantic versioning format: X.Y or X.Y.Z, optionally with pre-release or build metadata.
		if ( ! preg_match( '/^[\d]+\.[\d]+(?:\.[\d]+)?(?:-[a-zA-Z0-9.]+)?(?:\+[a-zA-Z0-9.]+)?$/', $version ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: %s: Invalid version */
					esc_html__( 'Invalid version format: %s. Must follow semantic versioning (e.g., 1.0.0).', 'wpmoo' ),
					esc_html( $version )
				)
			);
		}

		return true;
	}

	/**
	 * Validates a file path exists and is readable.
	 *
	 * @param string $path The path to validate.
	 * @return bool True if valid, throws exception if invalid.
	 * @throws \InvalidArgumentException If the path is invalid.
	 */
	public static function validate_file_path( string $path ): bool {
		if ( empty( $path ) ) {
			throw new \InvalidArgumentException( esc_html__( 'File path cannot be empty.', 'wpmoo' ) );
		}

		if ( ! file_exists( $path ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: %s: File path */
					esc_html__( 'File does not exist: %s', 'wpmoo' ),
					esc_html( $path )
				)
			);
		}

		if ( ! is_readable( $path ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					/* translators: %s: File path */
					esc_html__( 'File is not readable: %s', 'wpmoo' ),
					esc_html( $path )
				)
			);
		}

		return true;
	}
}
