<?php
/**
 * Admin options page handler.
 *
 * @package WPMoo\Options
 * @since 0.1.0
 * @version 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Options;

use WPMoo\Admin\UI\Panel;
use WPMoo\Fields\Field;
use WPMoo\Fields\Manager;
use WPMoo\Support\Assets;
use WPMoo\Support\Concerns\TranslatesStrings;
use WPMoo\Support\Str;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds a WordPress admin options page from configuration.
 */
class Page {
	use TranslatesStrings;

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
			add_action( 'wp_ajax_wpmoo_save_options', array( $this, 'ajax_save' ) );
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

		$assets_url = Assets::url();
		$version    = defined( 'WPMOO_VERSION' ) ? WPMOO_VERSION : '0.4.3';

		if ( empty( $assets_url ) ) {
			return;
		}

		if ( function_exists( 'wp_enqueue_style' ) ) {
			wp_enqueue_style( 'dashicons' );
		}

		// Enqueue CSS.
		if ( function_exists( 'wp_enqueue_style' ) ) {
			wp_enqueue_style(
				'wpmoo',
				$assets_url . 'css/wpmoo.css',
				array(),
				$version
			);
		}

		// Enqueue JS.
		if ( function_exists( 'wp_enqueue_script' ) ) {
			$persist_tabs = ! empty( $_REQUEST['_wpmoo_active_panel'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Persists UI state only.
			wp_enqueue_script( 'postbox' );
			wp_enqueue_script(
				'wpmoo',
				$assets_url . 'js/wpmoo.js',
				array( 'jquery' ),
				$version,
				true
			);

			if ( function_exists( 'wp_localize_script' ) ) {
				wp_localize_script(
					'wpmoo',
					'wpmooAdminOptions',
					array(
						'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
						'nonce'       => wp_create_nonce( 'wpmoo_options_save' ),
						'menuSlug'    => $this->config['menu_slug'],
						'persistTabs' => (bool) $persist_tabs,
						'strings'     => array(
							'saving' => function_exists( '__' ) ? __( 'Saving…', 'wpmoo' ) : 'Saving…',
							'saved'  => function_exists( '__' ) ? __( 'Settings saved.', 'wpmoo' ) : 'Settings saved.',
							'error'  => function_exists( '__' ) ? __( 'Unable to save settings.', 'wpmoo' ) : 'Unable to save settings.',
						),
					)
				);
			}
		}
	}

	/**
	 * Get the URL to the assets directory.
	 *
	 * @return string
	 */
	protected function get_assets_url() {
		return Assets::url();
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

		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			if ( isset( $_POST['action'] ) && 'wpmoo_save_options' === $_POST['action'] ) {
				return;
			}
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
	 * Handle AJAX submissions for the options page.
	 *
	 * @return void
	 */
	public function ajax_save() {
		$slug         = $this->config['menu_slug'];
		$request_slug = isset( $_POST['menu_slug'] ) ? $this->sanitize_panel_target( wp_unslash( $_POST['menu_slug'] ) ) : '';

		if ( $slug !== $request_slug ) {
			return;
		}

		if ( ! function_exists( 'check_ajax_referer' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request.' ) );
		}

		check_ajax_referer( 'wpmoo_options_save', 'nonce' );

		if ( function_exists( 'current_user_can' ) && ! current_user_can( $this->config['capability'] ) ) {
			wp_send_json_error( array( 'message' => function_exists( '__' ) ? __( 'You are not allowed to save these settings.', 'wpmoo' ) : 'You are not allowed to save these settings.' ) );
		}

		$option_key = $this->repository->option_key();
		$submitted  = array();

		if ( isset( $_POST[ $option_key ] ) && is_array( $_POST[ $option_key ] ) ) {
			$submitted = function_exists( 'wp_unslash' ) ? wp_unslash( $_POST[ $option_key ] ) : $_POST[ $option_key ];
		}

		$clean = array();

		foreach ( $this->fields as $id => $field ) {
			$value        = array_key_exists( $id, $submitted ) ? $submitted[ $id ] : null;
			$clean[ $id ] = $field->sanitize( $value );
		}

		$this->repository->save( $clean );

		$panel_id     = 'wpmoo-options-panel-' . $slug;
		$active_panel = '';

		if ( isset( $_POST['_wpmoo_active_panel'] ) && is_array( $_POST['_wpmoo_active_panel'] ) && isset( $_POST['_wpmoo_active_panel'][ $panel_id ] ) ) {
			$active_panel = $this->sanitize_panel_target( wp_unslash( $_POST['_wpmoo_active_panel'][ $panel_id ] ) );
		}

		$message = function_exists( '__' ) ? __( 'Settings saved.', 'wpmoo' ) : 'Settings saved.';

		wp_send_json_success(
			array(
				'message'     => $message,
				'activePanel' => $active_panel,
			)
		);
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
		$sections = array_values( $this->sections );

		if ( empty( $sections ) && ! empty( $this->fields ) ) {
			$sections[] = array(
				'id'          => 'general',
				'title'       => $this->translate( 'General', 'wpmoo' ),
				'description' => '',
				'icon'        => '',
				'fields'      => array_values( $this->fields ),
			);
		}

		$panel_sections = array();

		foreach ( $sections as $section ) {
			$section_id    = $section['id'];
			$section_title = ! empty( $section['title'] ) ? $section['title'] : ucfirst( str_replace( '-', ' ', $section_id ) );
			$section_desc  = ! empty( $section['description'] ) ? $section['description'] : '';
			$section_icon  = ! empty( $section['icon'] ) ? $section['icon'] : '';

			ob_start();

			foreach ( $section['fields'] as $field ) {
				$this->render_field( $field, $values );
			}

			$content = ob_get_clean();

			if ( '' !== trim( $content ) ) {
				$content = '<div class="wpmoo-section-fields">' . $content . '</div>';
			}

			$panel_sections[] = array(
				'id'          => $section_id,
				'label'       => $section_title,
				'description' => $section_desc,
				'icon'        => $section_icon,
				'content'     => $content,
			);
		}

		$panel_id       = 'wpmoo-options-panel-' . $this->config['menu_slug'];
		$active_section = $this->determine_active_section( $panel_id, $panel_sections );
		$persist_tabs   = ! empty( $_REQUEST['_wpmoo_active_panel'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Persists UI state only.
		$panel          = Panel::make(
			array(
				'id'              => $panel_id,
				'title'           => $this->config['page_title'],
				'sections'        => $panel_sections,
				'collapsible'     => false,
				'accordion_multi' => true,
				'persist'         => $persist_tabs,
				'active'          => $active_section,
			)
		);

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Render methods ensure inner values are escaped.
		echo '<div class="wrap wpmoo-options">';
		echo '<h1 class="wp-heading-inline">' . esc_html( $this->config['page_title'] ) . '</h1>';

		if ( function_exists( 'settings_errors' ) ) {
			settings_errors( $this->repository->option_key() );
		}

		echo '<form method="post" id="wpmoo-options-form" action="" enctype="multipart/form-data" autocomplete="off" novalidate="novalidate">';

		if ( function_exists( 'wp_nonce_field' ) ) {
			wp_nonce_field( $this->nonce_action(), $this->nonce_name() );
		}

		echo '<input type="hidden" name="_wpmoo_options_page" value="' . esc_attr( $this->config['menu_slug'] ) . '" />';

		echo '<input type="hidden" class="wpmoo-active-panel" data-panel-id="' . esc_attr( $panel_id ) . '" name="_wpmoo_active_panel[' . esc_attr( $panel_id ) . ']" value="' . esc_attr( $active_section ) . '" />';
		echo $panel->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '<div class="wpmoo-options-actions">';

		if ( function_exists( 'submit_button' ) ) {
			submit_button( __( 'Save Changes', 'wpmoo' ) );
		} else {
			echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html( $this->translate( 'Save Changes', 'wpmoo' ) ) . '</button></p>';
		}

		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Determine which section should be active on render.
	 *
	 * @param string                           $panel_id Panel identifier.
	 * @param array<int, array<string, mixed>> $sections Available sections.
	 * @return string
	 */
	protected function determine_active_section( string $panel_id, array $sections ): string {
		if ( empty( $sections ) ) {
			return '';
		}

		$default   = $sections[0]['id'];
		$requested = '';

		if ( isset( $_REQUEST['_wpmoo_active_panel'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Maintains UI state only.
			$data = $_REQUEST['_wpmoo_active_panel'];

			if ( is_array( $data ) && isset( $data[ $panel_id ] ) ) {
				$requested = $this->sanitize_panel_target( $data[ $panel_id ] );
			} elseif ( is_string( $data ) ) {
				$requested = $this->sanitize_panel_target( $data );
			}
		}

		if ( '' === $requested ) {
			return $default;
		}

		foreach ( $sections as $section ) {
			if ( $section['id'] === $requested ) {
				return $requested;
			}
		}

		return $default;
	}

	/**
	 * Sanitize a requested panel target value.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function sanitize_panel_target( string $value ): string {
		$value = strtolower( $value );

		return preg_replace( '/[^a-z0-9_\-]/', '', $value );
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

		$width   = $field->width();
		$classes = array(
			'wpmoo-field',
			'wpmoo-field-' . $field->type(),
		);

		if ( 'fieldset' !== $field->type() ) {
			$classes[] = 'wpmoo-field--separated';
		}

		$style_attr = '';

		if ( $width > 0 && $width < 100 && 'fieldset' !== $field->type() ) {
			$classes[]  = 'wpmoo-field--has-width';
			$style_attr = ' style="' . esc_attr( '--wpmoo-field-width:' . (string) $width . '%;' ) . '"';
		}

		$help_text   = $field->help_text();
		$help_html   = $field->help_html();
		$help_button = '';

		if ( '' !== $help_text ) {
			$help_button  = '<button type="button" class="wpmoo-field-help" aria-label="' . esc_attr( $help_text ) . '"';
			$help_button .= ' data-tooltip="' . esc_attr( $help_text ) . '"';
			$help_button .= ' data-help-text="' . esc_attr( $help_text ) . '"';

			if ( '' !== $help_html ) {
				$help_button .= ' data-help-html="' . esc_attr( $help_html ) . '"';
			}

			$help_button .= '>';
			$help_button .= '<span aria-hidden="true">?</span>';
			$help_button .= '<span class="screen-reader-text">' . esc_html( $help_text ) . '</span>';
			$help_button .= '</button>';
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Field rendering handles escaping internally or via helper methods.
		echo '<div class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '"' . $style_attr . '>';

		if ( $field->label() ) {
			echo '<div class="wpmoo-title">';
			echo '<div class="wpmoo-title__heading">';
			echo '<h4>' . esc_html( $field->label() ) . '</h4>';
			echo '</div>';

			if ( $field->description() ) {
				echo '<div class="wpmoo-subtitle-text">' . esc_html( $field->description() ) . '</div>';
			}

			echo '</div>';
		}

		echo '<div class="wpmoo-fieldset">';

		if ( $field->before() ) {
			echo '<div class="wpmoo-field-before">' . $field->before_html() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<div class="wpmoo-fieldset__control">';

		if ( $help_button ) {
			echo '<div class="wpmoo-fieldset__control-inner">';
		}

		echo $field->render( $name, $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $help_button ) {
			echo $help_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		}

		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $field->after() ) {
			echo '<div class="wpmoo-field-after">' . $field->after_html() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( '' === $field->label() && '' !== $help_html && '' === $help_button ) {
			echo '<div class="wpmoo-field-help-text">' . $help_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Render a single field row inside the form table.
	 *
	 * @param Field                $field  Field instance.
	 * @param array<string, mixed> $values Option values.
	 * @return void
	 */
	protected function render_field_row( Field $field, array $values ) {
		$value = array_key_exists( $field->id(), $values ) ? $values[ $field->id() ] : $field->default();
		$name  = $this->field_input_name( $field );

		$args          = $field->args();
		$desc          = $field->description();
		$desc_position = isset( $args['description_position'] ) ? $args['description_position'] : 'field';

		echo '<tr>';
		echo '<th scope="row">';
		echo '<label for="' . esc_attr( $field->id() ) . '">' . esc_html( $field->label() ) . '</label>';
		if ( $desc && 'label' === $desc_position ) {
			echo '<p class="description">' . esc_html( $desc ) . '</p>';
		}
		echo '</th>';
		echo '<td>';
		echo $field->render( $name, $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered field handles escaping internally.

		if ( $desc && 'field' === $desc_position ) {
			echo '<p class="description">' . esc_html( $desc ) . '</p>';
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
			$config['menu_slug'] = Str::slug( $config['menu_title'] );
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
				'icon'        => '',
				'fields'      => array(),
			);

			$section = array_merge( $section_defaults, is_array( $section ) ? $section : array() );

			if ( '' === $section['id'] ) {
				$base          = '' !== $section['title'] ? $section['title'] : uniqid( 'section_', true );
				$section['id'] = Str::slug( $base );
			}

			$fields = array();

			foreach ( $section['fields'] as $field_config ) {
				if ( empty( $field_config['id'] ) ) {
					continue;
				}

				$field_config['field_manager'] = $this->field_manager;

				$field                        = $this->field_manager->make( $field_config );
				$fields[]                     = $field;
				$this->fields[ $field->id() ] = $field;
			}

			$section['fields'] = $fields;
			$normalized[]      = $section;
		}

		return $normalized;
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

		if ( isset( $_POST['_wpmoo_active_panel'] ) && is_array( $_POST['_wpmoo_active_panel'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified earlier in submission handler.
			$panels = array();

			foreach ( $_POST['_wpmoo_active_panel'] as $panel_id => $target ) {
				$panel_id = $this->sanitize_panel_target( (string) $panel_id );
				$target   = $this->sanitize_panel_target( (string) $target );

				if ( '' !== $panel_id && '' !== $target ) {
					$panels[ $panel_id ] = $target;
				}
			}

			if ( ! empty( $panels ) ) {
				$query['_wpmoo_active_panel'] = $panels;
			}
		}

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
