<?php
/**
 * Admin options page handler.
 *
 * WPMoo — WordPress Micro Object-Oriented Framework.
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Options
 * @since 0.1.0
 * @version 0.1.0
 */

namespace WPMoo\Options;

use WPMoo\Fields\Field;
use WPMoo\Fields\Manager;

/**
 * Builds a WordPress admin options page from configuration.
 */
class Page {

	/**
	 * Normalized page configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected $config;

	/**
	 * Normalized section definitions.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $sections = array();

	/**
	 * Map of registered fields keyed by id.
	 *
	 * @var array<string, Field>
	 */
	protected $fields = array();

	/**
	 * Field manager instance.
	 *
	 * @var Manager
	 */
	protected $field_manager;

	/**
	 * Option repository.
	 *
	 * @var OptionRepository
	 */
	protected $repository;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config        Raw page configuration.
	 * @param Manager              $field_manager Field manager dependency.
	 */
	public function __construct( array $config, Manager $field_manager ) {
		$this->field_manager = $field_manager;
		$this->config        = $this->normalize_config( $config );
		$this->sections      = $this->normalize_sections( $this->config['sections'] );
		$this->repository    = new OptionRepository( $this->config['option_key'], $this->collect_defaults() );
	}

	/**
	 * Register hooks required to display and save the page.
	 *
	 * @return void
	 */
	public function boot() {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'admin_menu', array( $this, 'register_page' ) );
			add_action( 'admin_init', array( $this, 'handle_submission' ) );
		}
	}

	/**
	 * Register the admin page with WordPress.
	 *
	 * @return void
	 */
	public function register_page() {
		if ( ! function_exists( 'add_menu_page' ) ) {
			return;
		}

		if ( ! empty( $this->config['parent_slug'] ) && function_exists( 'add_submenu_page' ) ) {
			add_submenu_page(
				$this->config['parent_slug'],
				$this->config['page_title'],
				$this->config['menu_title'],
				$this->config['capability'],
				$this->config['menu_slug'],
				array( $this, 'render' )
			);
			return;
		}

		add_menu_page(
			$this->config['page_title'],
			$this->config['menu_title'],
			$this->config['capability'],
			$this->config['menu_slug'],
			array( $this, 'render' ),
			$this->config['icon'],
			$this->config['position']
		);
	}

	/**
	 * Process a submitted options form.
	 *
	 * @return void
	 */
	public function handle_submission() {
		if ( ! function_exists( 'current_user_can' ) ) {
			return;
		}

		$slug = $this->config['menu_slug'];

		if ( ! isset( $_POST['_wpmoo_options_page'] ) || $slug !== $_POST['_wpmoo_options_page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified below.
			return;
		}

		if ( ! current_user_can( $this->config['capability'] ) ) {
			return;
		}

		if ( function_exists( 'check_admin_referer' ) ) {
			check_admin_referer( $this->nonce_action(), $this->nonce_name() );
		}

		$option_key = $this->repository->option_key();
		$submitted  = array();

		if ( isset( $_POST[ $option_key ] ) && is_array( $_POST[ $option_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
			$submitted = function_exists( 'wp_unslash' )
				? wp_unslash( $_POST[ $option_key ] )
				: $_POST[ $option_key ];
		}

		$clean = array();

		foreach ( $this->fields as $id => $field ) {
			$value        = isset( $submitted[ $id ] ) ? $submitted[ $id ] : null;
			$clean[ $id ] = $field->sanitize( $value );
		}

		$this->repository->save( $clean );

		if ( function_exists( 'add_settings_error' ) ) {
			$message = function_exists( '__' ) ? __( 'Settings saved.', 'wpmoo' ) : 'Settings saved.';
			add_settings_error( $option_key, 'wpmoo_options_saved', $message, 'updated' );
		}

		$redirect = $this->build_redirect_url();

		if ( $redirect && function_exists( 'wp_safe_redirect' ) ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Render the admin page output.
	 *
	 * @return void
	 */
	public function render() {
		if ( function_exists( 'current_user_can' ) && ! current_user_can( $this->config['capability'] ) ) {
			return;
		}

		$values = $this->repository->all();

		echo '<div class="wrap">';
		echo '<h1>' . $this->esc_html( $this->config['page_title'] ) . '</h1>';

		if ( function_exists( 'settings_errors' ) ) {
			settings_errors( $this->repository->option_key() );
		}

		echo '<form method="post">';

		if ( function_exists( 'wp_nonce_field' ) ) {
			wp_nonce_field( $this->nonce_action(), $this->nonce_name() );
		}

		echo '<input type="hidden" name="_wpmoo_options_page" value="' . $this->esc_attr( $this->config['menu_slug'] ) . '" />';

		foreach ( $this->sections as $section ) {
			if ( ! empty( $section['title'] ) ) {
				echo '<h2>' . $this->esc_html( $section['title'] ) . '</h2>';
			}

			if ( ! empty( $section['description'] ) ) {
				echo '<p class="description">' . $this->esc_html( $section['description'] ) . '</p>';
			}

			echo '<table class="form-table" role="presentation">';

			foreach ( $section['fields'] as $field ) {
				$this->render_field_row( $field, $values );
			}

			echo '</table>';
		}

		echo '<p class="submit">';

		$label = function_exists( '__' ) ? __( 'Save Changes', 'wpmoo' ) : 'Save Changes';

		echo '<button type="submit" class="button button-primary">' . $this->esc_html( $label ) . '</button>';
		echo '</p>';
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render a single field row inside the form table.
	 *
	 * @param Field                 $field  Field instance.
	 * @param array<string, mixed>  $values Option values.
	 * @return void
	 */
	protected function render_field_row( Field $field, array $values ) {
		$value = array_key_exists( $field->id(), $values ) ? $values[ $field->id() ] : $field->default();
		$name  = $this->field_input_name( $field );

		echo '<tr>';
		echo '<th scope="row">';
		echo '<label for="' . $this->esc_attr( $field->id() ) . '">' . $this->esc_html( $field->label() ) . '</label>';
		echo '</th>';
		echo '<td>';
		echo $field->render( $name, $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered field handles escaping internally.

		if ( $field->description() ) {
			echo '<p class="description">' . $this->esc_html( $field->description() ) . '</p>';
		}

		echo '</td>';
		echo '</tr>';
	}

	/**
	 * Build the HTML input name for a field.
	 *
	 * @param Field $field Field instance.
	 * @return string
	 */
	protected function field_input_name( Field $field ) {
		return $this->repository->option_key() . '[' . $field->id() . ']';
	}

	/**
	 * Gather default values across all fields.
	 *
	 * @return array<string, mixed>
	 */
	protected function collect_defaults() {
		$defaults = array();

		foreach ( $this->fields as $field ) {
			$defaults[ $field->id() ] = $field->default();
		}

		return $defaults;
	}

	/**
	 * Normalize the page configuration array.
	 *
	 * @param array<string, mixed> $config Raw configuration values.
	 * @return array<string, mixed>
	 */
	protected function normalize_config( array $config ) {
		$defaults = array(
			'page_title'  => '',
			'menu_title'  => '',
			'menu_slug'   => '',
			'option_key'  => '',
			'capability'  => 'manage_options',
			'parent_slug' => '',
			'position'    => null,
			'icon'        => '',
			'sections'    => array(),
		);

		$config = array_merge( $defaults, $config );

		if ( '' === $config['page_title'] ) {
			$config['page_title'] = '' !== $config['menu_title'] ? $config['menu_title'] : 'Settings';
		}

		if ( '' === $config['menu_title'] ) {
			$config['menu_title'] = $config['page_title'];
		}

		if ( '' === $config['menu_slug'] ) {
			$config['menu_slug'] = $this->slugify( $config['menu_title'] );
		}

		if ( '' === $config['option_key'] ) {
			$config['option_key'] = $config['menu_slug'];
		}

		if ( ! is_array( $config['sections'] ) ) {
			$config['sections'] = array();
		}

		return $config;
	}

	/**
	 * Normalize configured sections and instantiate their fields.
	 *
	 * @param array<int, mixed> $sections Raw section list.
	 * @return array<int, array<string, mixed>>
	 */
	protected function normalize_sections( array $sections ) {
		$normalized = array();

		foreach ( $sections as $section ) {
			$section_defaults = array(
				'id'          => '',
				'title'       => '',
				'description' => '',
				'fields'      => array(),
			);

			$section = array_merge( $section_defaults, is_array( $section ) ? $section : array() );

			if ( '' === $section['id'] ) {
				$base          = '' !== $section['title'] ? $section['title'] : uniqid( 'section_', true );
				$section['id'] = $this->slugify( $base );
			}

			$fields = array();

			foreach ( $section['fields'] as $field_config ) {
				if ( empty( $field_config['id'] ) ) {
					continue;
				}

				$field     = $this->field_manager->make( $field_config );
				$fields[]  = $field;
				$this->fields[ $field->id() ] = $field;
			}

			$section['fields'] = $fields;
			$normalized[]      = $section;
		}

		return $normalized;
	}

	/**
	 * Generate a slug from the given value.
	 *
	 * @param string $value Raw string.
	 * @return string
	 */
	protected function slugify( $value ) {
		if ( function_exists( 'sanitize_title' ) ) {
			return sanitize_title( $value );
		}

		$value = strtolower( preg_replace( '/[^a-zA-Z0-9]+/', '-', $value ) );

		return trim( $value, '-' );
	}

	/**
	 * Build the nonce action name.
	 *
	 * @return string
	 */
	protected function nonce_action() {
		return 'wpmoo_options_' . $this->config['menu_slug'];
	}

	/**
	 * Build the nonce field name.
	 *
	 * @return string
	 */
	protected function nonce_name() {
		return '_wpmoo_options_nonce';
	}

	/**
	 * Construct a redirect URL after saving.
	 *
	 * @return string
	 */
	protected function build_redirect_url() {
		$query = array(
			'page'             => $this->config['menu_slug'],
			'settings-updated' => 'true',
		);

		if ( function_exists( 'wp_get_referer' ) ) {
			$referer = wp_get_referer();

			if ( $referer ) {
				return add_query_arg( $query, $referer );
			}
		}

		if ( function_exists( 'menu_page_url' ) ) {
			return menu_page_url( $this->config['menu_slug'], false );
		}

		if ( function_exists( 'admin_url' ) ) {
			return add_query_arg( $query, admin_url( 'admin.php' ) );
		}

		return '';
	}

	/**
	 * Escape HTML output.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function esc_html( $value ) {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $value );
		}

		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Escape an attribute value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	protected function esc_attr( $value ) {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Provide access to the repository instance.
	 *
	 * @return OptionRepository
	 */
	public function repository() {
		return $this->repository;
	}
}
