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
			/* translators: %s: Component type */
			throw new \InvalidArgumentException( sprintf( esc_html__( '%s ID cannot be empty.', 'wpmoo' ), sanitize_text_field( $component_type ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! preg_match( '/^[a-z0-9_-]+$/', $id ) ) {
			/* translators: 1: Component type 2: Invalid ID */
			throw new \InvalidArgumentException(
				sprintf(
					esc_html__( 'Invalid %1$s ID: %2$s. Must contain only lowercase letters, numbers, hyphens, and underscores.', 'wpmoo' ),
					sanitize_text_field( $component_type ),
					sanitize_text_field( $id )
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
			throw new \InvalidArgumentException( 'Plugin slug cannot be empty.' );
		}

		if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			/* translators: %s: Invalid plugin slug */
			throw new \InvalidArgumentException(
				sprintf(
					esc_html__( 'Invalid plugin slug: %s. Must contain only lowercase letters, numbers, and hyphens.', 'wpmoo' ),
					sanitize_text_field( $slug )
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
			throw new \InvalidArgumentException( esc_html__( 'Version cannot be empty.', 'wpmoo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// Basic semantic versioning format: X.Y or X.Y.Z, optionally with pre-release or build metadata.
		if ( ! preg_match( '/^[\d]+\.[\d]+(?:\.[\d]+)?(?:-[a-zA-Z0-9.]+)?(?:\+[a-zA-Z0-9.]+)?$/', $version ) ) {
			/* translators: %s: Invalid version */
			throw new \InvalidArgumentException(
				sprintf(
					esc_html__( 'Invalid version format: %s. Must follow semantic versioning (e.g., 1.0.0).', 'wpmoo' ),
					sanitize_text_field( $version )
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
			throw new \InvalidArgumentException( esc_html__( 'File path cannot be empty.', 'wpmoo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! file_exists( $path ) ) {
			/* translators: %s: File path */
			throw new \InvalidArgumentException(
				sprintf( esc_html__( 'File does not exist: %s', 'wpmoo' ), sanitize_text_field( $path ) )
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! is_readable( $path ) ) {
			/* translators: %s: File path */
			throw new \InvalidArgumentException(
				sprintf( esc_html__( 'File is not readable: %s', 'wpmoo' ), sanitize_text_field( $path ) )
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return true;
	}
}
