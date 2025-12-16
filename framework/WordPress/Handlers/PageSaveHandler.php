<?php

namespace WPMoo\WordPress\Handlers;

use WPMoo\WordPress\Managers\FrameworkManager;
use WPMoo\Core;

/**
 * Handles form saving.
 *
 * @package WPMoo\WordPress
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class PageSaveHandler {
	/**
	 * Framework manager instance.
	 *
	 * @var FrameworkManager
	 */
	private FrameworkManager $framework_manager;

	/**
	 * Constructor.
	 *
	 * @param FrameworkManager $framework_manager Framework manager instance.
	 */
	public function __construct( FrameworkManager $framework_manager ) {
		$this->framework_manager = $framework_manager;
	}

	/**
	 * Process form submission for a specific page.
	 *
	 * @param string $option_group The option group name (matches the page slug).
	 * @param array  $submitted_data The submitted form data.
	 * @return array Result of the processing ['success' => bool, 'errors' => array, 'message' => string].
	 */
	public function process_page_submission( string $option_group, array $submitted_data ): array {
		$errors = array();
		$sanitized_data = array();

		// Get all fields for the specific plugin - we'll need to process fields differently.
		// Since they're stored by plugin slug, not directly by page.
		$all_fields_by_plugin = $this->framework_manager->get_fields();

		// For now, we'll process all fields in the submitted data.
		// In a real implementation, you'd need to map which fields belong to which page.
		foreach ( $submitted_data as $field_id => $field_value ) {
			// Since we don't have direct mapping of page to fields, we'll get the field by ID.
			// This is a simplified approach - in a complete implementation, you'd have a more sophisticated mapping.
			$field = null;

			// Look for the field across all plugins.
			foreach ( $all_fields_by_plugin as $plugin_fields ) {
				if ( isset( $plugin_fields[ $field_id ] ) ) {
					$field = $plugin_fields[ $field_id ];
					break;
				}
			}

			if ( $field ) {
				// Validate the field value
				$validation_result = $field->validate( $field_value );

				if ( ! $validation_result['valid'] ) {
					$errors[ $field_id ] = $validation_result['error'];
					continue;
				}

				// Sanitize the field value.
				$sanitized_data[ $field_id ] = $field->sanitize( $field_value );
			} else {
				// If we can't find a field definition, still sanitize the input for security.
				$sanitized_data[ $field_id ] = sanitize_text_field( $field_value );
			}
		}

		// If there are validation errors, return them
		if ( ! empty( $errors ) ) {
			return array(
				'success' => false,
				'errors' => $errors,
				'message' => __( 'Validation failed for one or more fields.', 'wpmoo' ),
			);
		}

		// If validation passed, save the sanitized data
		update_option( $option_group, $sanitized_data );

		return array(
			'success' => true,
			'errors' => array(),
			'message' => __( 'Settings saved successfully.', 'wpmoo' ),
		);
	}

	/**
	 * Register settings for validation and sanitization.
	 *
	 * @param string $option_group The option group name.
	 * @return void
	 */
	public function register_settings( string $option_group ): void {
		register_setting(
			$option_group,
			$option_group,
			array( $this, 'sanitize_callback' )
		);
	}

	/**
	 * Sanitization callback for the settings API.
	 *
	 * @param array $input The input data to sanitize.
	 * @return array The sanitized data.
	 */
	public function sanitize_callback( array $input ): array {
		$all_fields_by_plugin = $this->framework_manager->get_fields();
		$sanitized_data = array();

		foreach ( $input as $field_id => $field_value ) {
			$field = null;

			// Look for the field across all plugins.
			foreach ( $all_fields_by_plugin as $plugin_fields ) {
				if ( isset( $plugin_fields[ $field_id ] ) ) {
					$field = $plugin_fields[ $field_id ];
					break;
				}
			}

			if ( $field ) {
				// Validate the field value.
				$validation_result = $field->validate( $field_value );

				if ( ! $validation_result['valid'] ) {
					// Add error message using WordPress settings API.
					add_settings_error(
						$field_id,
						'validation_error',
						$validation_result['error']
					);

					// Use the old value if validation fails
					$old_value = get_option( $field_id, '' );
					$sanitized_data[ $field_id ] = $field->sanitize( $old_value );
				} else {
					// Sanitize the field value.
					$sanitized_data[ $field_id ] = $field->sanitize( $field_value );
				}
			} else {
				// If we can't find a field definition, still sanitize the input for security.
				$sanitized_data[ $field_id ] = sanitize_text_field( $field_value );
			}
		}

		return $sanitized_data;
	}
}
