<?php
/**
 * Admin options page handler.
 *
 * @package WPMoo\Options
 * @since 0.1.0
 * @version 0.1.0
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Options;

use WPMoo\Fields\Field;
use WPMoo\Fields\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}
	}

	/**
	 * Enqueue framework assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		// Only load on our options page.
		$menu_slug = $this->config['menu_slug'];
		
		if ( ! empty( $this->config['parent_slug'] ) ) {
			$page_hook = get_plugin_page_hookname( $menu_slug, $this->config['parent_slug'] );
		} else {
			$page_hook = 'toplevel_page_' . $menu_slug;
		}
		
		if ( $hook !== $page_hook ) {
			return;
		}

		$assets_url = $this->get_assets_url();
		
		// Debug: Log the assets URL
		if ( function_exists( 'error_log' ) ) {
			error_log( '🎨 WPMoo Framework Assets URL: ' . $assets_url );
		}
		
		$version = '0.2.0';

		// Enqueue CSS.
		if ( function_exists( 'wp_enqueue_style' ) ) {
			wp_enqueue_style(
				'wpmoo-framework',
				$assets_url . 'css/wpmoo-framework.css',
				array(),
				$version
			);
		}

		// Enqueue JS.
		if ( function_exists( 'wp_enqueue_script' ) ) {
			wp_enqueue_script(
				'wpmoo-framework',
				$assets_url . 'js/wpmoo-framework.js',
				array( 'jquery' ),
				$version,
				true
			);
		}
	}

	/**
	 * Get the URL to the assets directory.
	 *
	 * @return string
	 */
	protected function get_assets_url() {
		// Simple approach: find vendor/wpmoo-org/wpmoo in current plugin
		// This works for symlinked composer packages
		
		if ( defined( 'WP_PLUGIN_URL' ) ) {
			// Hardcode for now - we know wpmoo-starter is loading this
			return WP_PLUGIN_URL . '/wpmoo-starter/vendor/wpmoo-org/wpmoo/assets/';
		}
		
		return '';
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

		// Check if custom renderer is configured.
		if ( isset( $this->config['render'] ) && is_callable( $this->config['render'] ) ) {
			call_user_func( $this->config['render'], $this, $values );
			return;
		}

		// Default container.
		$this->render_container( $values );
	}

	/**
	 * Render the default admin container.
	 *
	 * @param array<string, mixed> $values Current option values.
	 * @return void
	 */
	protected function render_container( array $values ) {
		$sections        = array_values( $this->sections );
		$default_section = ! empty( $sections ) ? $sections[0]['id'] : '';
		$framework_title = $this->config['page_title'];

		echo '<div class="wpmoo-framework wpmoo-option-framework">';
		
		// Header.
		echo '<div class="wpmoo-header">';
		echo '<div class="wpmoo-header-inner">';
		echo '<div class="wpmoo-header-left">';
		echo '<h1>' . $this->esc_html( $framework_title ) . '</h1>';
		echo '</div>';
		echo '<div class="wpmoo-header-right">';
		echo '<div class="wpmoo-search">';
		echo '<input type="text" class="wpmoo-search-input" placeholder="' . $this->esc_attr( function_exists( '__' ) ? __( 'Search...', 'wpmoo' ) : 'Search...' ) . '" autocomplete="off" />';
		echo '</div>';
		echo '<div class="wpmoo-buttons">';
		echo '<button type="submit" form="wpmoo-options-form" class="button button-primary wpmoo-save">' . $this->esc_html( function_exists( '__' ) ? __( 'Save', 'wpmoo' ) : 'Save' ) . '</button>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';

		// Notices.
		if ( function_exists( 'settings_errors' ) ) {
			ob_start();
			settings_errors( $this->repository->option_key() );
			$notices = ob_get_clean();
			if ( $notices ) {
				echo '<div class="wpmoo-wrapper wpmoo-show">';
				echo '<div class="wpmoo-content">';
				echo '<div class="wpmoo-sections">';
				echo $notices; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</div>';
				echo '</div>';
				echo '</div>';
			}
		}

		echo '<div class="wpmoo-wrapper wpmoo-show">';

		// Navigation.
		echo '<div class="wpmoo-nav wpmoo-nav-options">';
		echo '<ul>';
		foreach ( $sections as $section ) {
			$section_id    = $section['id'];
			$section_title = ! empty( $section['title'] ) ? $section['title'] : ucfirst( str_replace( '-', ' ', $section_id ) );
			$is_active     = $section_id === $default_section ? ' class="wpmoo-section-active"' : '';

			echo '<li' . $is_active . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<a href="#" data-section="' . $this->esc_attr( $section_id ) . '">';
			echo $this->esc_html( $section_title );
			echo '</a>';
			echo '</li>';
		}
		echo '</ul>';
		echo '</div>';

		// Content.
		echo '<div class="wpmoo-content">';
		echo '<form method="post" id="wpmoo-options-form" action="" enctype="multipart/form-data" autocomplete="off" novalidate="novalidate">';

		if ( function_exists( 'wp_nonce_field' ) ) {
			wp_nonce_field( $this->nonce_action(), $this->nonce_name() );
		}

		echo '<input type="hidden" name="_wpmoo_options_page" value="' . $this->esc_attr( $this->config['menu_slug'] ) . '" />';

		echo '<div class="wpmoo-sections">';

		foreach ( $sections as $section ) {
			$section_id    = $section['id'];
			$section_title = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_desc  = ! empty( $section['description'] ) ? $section['description'] : '';
			$is_active     = $section_id === $default_section ? ' wpmoo-section-active' : '';

			echo '<div class="wpmoo-section' . $is_active . '" data-section="' . $this->esc_attr( $section_id ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( $section_title ) {
				echo '<div class="wpmoo-section-title">';
				echo '<h3>' . $this->esc_html( $section_title ) . '</h3>';
				if ( $section_desc ) {
					echo '<div class="wpmoo-section-description">' . $this->esc_html( $section_desc ) . '</div>';
				}
				echo '</div>';
			}

			foreach ( $section['fields'] as $field ) {
				$this->render_field( $field, $values );
			}

			echo '</div>';
		}

		echo '</div>';
		echo '</form>';

		// Footer.
		echo '<div class="wpmoo-footer">';
		echo '<div class="wpmoo-footer-left">';
		echo 'Thank you for creating with WPMoo Framework';
		echo '</div>';
		echo '<div class="wpmoo-footer-right">';
		echo '<button type="submit" form="wpmoo-options-form" class="button button-primary wpmoo-save">' . $this->esc_html( function_exists( '__' ) ? __( 'Save', 'wpmoo' ) : 'Save' ) . '</button>';
		echo '</div>';
		echo '</div>';

		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render a single field in the layout.
	 *
	 * @param Field                $field  Field instance.
	 * @param array<string, mixed> $values Current option values.
	 * @return void
	 */
	protected function render_field( Field $field, array $values ) {
		$value = array_key_exists( $field->id(), $values ) ? $values[ $field->id() ] : $field->default();
		$name  = $this->field_input_name( $field );

		echo '<div class="wpmoo-field wpmoo-field-' . $this->esc_attr( $field->type() ) . '">';

		if ( $field->label() ) {
			echo '<div class="wpmoo-title">';
			echo '<h4>' . $this->esc_html( $field->label() ) . '</h4>';
			if ( $field->description() ) {
				echo '<div class="wpmoo-subtitle-text">' . $this->esc_html( $field->description() ) . '</div>';
			}
			echo '</div>';
		}

		echo '<div class="wpmoo-fieldset">';
		echo $field->render( $name, $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';

		echo '<div class="clear"></div>';
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

		$args = $field->args();
		$desc = $field->description();
		$desc_position = isset( $args['description_position'] ) ? $args['description_position'] : 'field';

		echo '<tr>';
		echo '<th scope="row">';
		echo '<label for="' . $this->esc_attr( $field->id() ) . '">' . $this->esc_html( $field->label() ) . '</label>';
		if ( $desc && 'label' === $desc_position ) {
			echo '<p class="description">' . $this->esc_html( $desc ) . '</p>';
		}
		echo '</th>';
		echo '<td>';
		echo $field->render( $name, $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered field handles escaping internally.

		if ( $desc && 'field' === $desc_position ) {
			echo '<p class="description">' . $this->esc_html( $desc ) . '</p>';
		}

		echo '</td>';
		echo '</tr>';
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

	/**
	 * Get the option key.
	 *
	 * @return string
	 */
	public function option_key() {
		return $this->repository->option_key();
	}

	/**
	 * Get all sections.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function sections() {
		return $this->sections;
	}

	/**
	 * Get configuration value.
	 *
	 * @param string $key Configuration key.
	 * @return mixed
	 */
	public function config( $key ) {
		return isset( $this->config[ $key ] ) ? $this->config[ $key ] : null;
	}

	/**
	 * Get nonce action name.
	 *
	 * @return string
	 */
	public function nonce_action_name() {
		return $this->nonce_action();
	}

	/**
	 * Get nonce field name.
	 *
	 * @return string
	 */
	public function nonce_field_name() {
		return $this->nonce_name();
	}

	/**
	 * Build field input name for custom renderers.
	 *
	 * @param Field $field Field instance.
	 * @return string
	 */
	public function field_input_name( Field $field ) {
		return $this->repository->option_key() . '[' . $field->id() . ']';
	}
}
