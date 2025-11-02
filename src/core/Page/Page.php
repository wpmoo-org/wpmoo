<?php
/**
 * Admin options page handler.
 *
 * @package WPMoo\Options
 * @since 0.1.0
 * @version 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Page;

use WPMoo\Fields\BaseField as Field;
use WPMoo\Fields\Manager;
use WPMoo\Support\Assets;
use WPMoo\Support\Concerns\TranslatesStrings;
use WPMoo\Options\OptionRepository;
use WPMoo\Support\Str;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
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
			// Add a body class on our pages to scope layout overrides safely.
			if ( function_exists( 'add_filter' ) ) {
				add_filter( 'admin_body_class', array( $this, 'filter_admin_body_class' ) );
			}
			add_action( 'wp_ajax_wpmoo_save_options', array( $this, 'ajax_save' ) );
		}
	}

	/**
	 * Add a marker CSS class to the admin body when viewing this page.
	 *
	 * @param string $classes Existing body classes.
	 * @return string
	 */
	public function filter_admin_body_class( $classes ) {
		$slug        = isset( $this->config['menu_slug'] ) ? (string) $this->config['menu_slug'] : '';
		$current     = '';
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raw     = function_exists( 'wp_unslash' ) ? wp_unslash( $_GET['page'] ) : (string) $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current = function_exists( 'sanitize_key' ) ? sanitize_key( $raw ) : preg_replace( '/[^a-z0-9_\-]/', '', (string) $raw );
		}

		if ( $slug && $current === $slug ) {
			$slug_class = function_exists( 'sanitize_html_class' ) ? sanitize_html_class( $slug ) : preg_replace( '/[^A-Za-z0-9_-]/', '', $slug );
			$classes   .= ' wpmoo-view wpmoo-page-' . $slug_class;
		}

		return $classes;
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

		// Allow consumers (e.g., starter plugin) to disable framework CSS/JS enqueues
		// and provide their own bundled assets.
		$enqueue_framework_assets = true;
		if ( function_exists( 'apply_filters' ) ) {
			$enqueue_framework_assets = (bool) apply_filters( 'wpmoo_enqueue_assets', true, $hook, 'options' );
		}

		$assets_url = Assets::url();
		$ui_css_url = Assets::ui_css_url();
		$version    = defined( 'WPMOO_VERSION' ) ? WPMOO_VERSION : '0.1.0';

		if ( function_exists( 'wp_enqueue_style' ) ) {
			wp_enqueue_style( 'dashicons' );
		}

		// Enqueue framework CSS if permitted.
		if ( $enqueue_framework_assets && function_exists( 'wp_enqueue_style' ) ) {
			if ( ! empty( $ui_css_url ) ) {
				wp_enqueue_style( 'wpmoo', $ui_css_url, array(), $version );
			} elseif ( ! empty( $assets_url ) ) {
				// Legacy fallback
				wp_enqueue_style( 'wpmoo', $assets_url . 'css/wpmoo.css', array(), $version );
			}
		}

		// Enqueue JS.
		if ( function_exists( 'wp_enqueue_script' ) ) {
			wp_enqueue_script( 'postbox' );

			if ( $enqueue_framework_assets ) {
				if ( ! empty( $assets_url ) ) {
					wp_enqueue_script(
						'wpmoo',
						$assets_url . 'js/wpmoo.js',
						array( 'jquery', 'jquery-ui-sortable' ),
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

		if ( isset( $_POST[ $option_key ] ) && is_array( $_POST[ $option_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Verified above; unslashed below and sanitized per-field.
			$submitted = function_exists( 'wp_unslash' )
			? wp_unslash( $_POST[ $option_key ] )
			: $_POST[ $option_key ];
		}

		$clean = array();

		foreach ( $this->fields as $id => $field ) {
			if ( method_exists( $field, 'should_save' ) && ! $field->should_save() ) {
				continue;
			}
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
			return;
		}
	}

	/**
	 * Handle AJAX submissions for the options page.
	 *
	 * @return void
	 */
	public function ajax_save() {
		$slug         = $this->config['menu_slug'];
		$request_slug = '';
		if ( isset( $_POST['menu_slug'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw = function_exists( 'wp_unslash' ) ? wp_unslash( $_POST['menu_slug'] ) : (string) $_POST['menu_slug']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$request_slug = function_exists( 'sanitize_key' ) ? sanitize_key( $raw ) : preg_replace( '/[^a-z0-9_\-]/', '', (string) $raw );
		}

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

		if ( isset( $_POST[ $option_key ] ) && is_array( $_POST[ $option_key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$submitted = function_exists( 'wp_unslash' ) ? wp_unslash( $_POST[ $option_key ] ) : $_POST[ $option_key ];
		}

		$clean = array();

		foreach ( $this->fields as $id => $field ) {
			if ( method_exists( $field, 'should_save' ) && ! $field->should_save() ) {
				continue;
			}
			$value        = array_key_exists( $id, $submitted ) ? $submitted[ $id ] : null;
			$clean[ $id ] = $field->sanitize( $value );
		}

		$this->repository->save( $clean );

		$message = function_exists( '__' ) ? __( 'Settings saved.', 'wpmoo' ) : 'Settings saved.';

		wp_send_json_success( array( 'message' => $message ) );
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

		// Compose optional style attribute from configured CSS variables.
		$style_attr = '';
		if ( isset( $this->config['css_vars'] ) && is_array( $this->config['css_vars'] ) && ! empty( $this->config['css_vars'] ) ) {
			$pairs = array();
			foreach ( $this->config['css_vars'] as $var => $val ) {
				$name = (string) $var;
				// Accept only our scoped custom properties for safety.
				if ( 0 !== strpos( $name, '--wpmoo-' ) ) {
					continue;
				}
				$pairs[] = $name . ':' . (string) $val;
			}
			if ( ! empty( $pairs ) ) {
				$style_attr = ' style="' . esc_attr( implode( ';', $pairs ) ) . '"';
			}
		}

		// Compose classes for the page container.
		$base_classes = array( 'wpmoo', 'container' );
		if ( isset( $this->config['classes'] ) && is_string( $this->config['classes'] ) && '' !== trim( $this->config['classes'] ) ) {
			$extras = preg_split( '/\s+/', trim( (string) $this->config['classes'] ) ) ?: array();
			foreach ( $extras as $c ) {
				$c = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $c );
				if ( $c && ! in_array( $c, $base_classes, true ) ) {
					$base_classes[] = $c;
				}
			}
		}

		$class_attr = ' class="' . esc_attr( implode( ' ', $base_classes ) ) . '"';

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output assembled with proper escaping below.
		echo '<main' . $class_attr . ' id="wpmoo-options"' . $style_attr . '>';
		echo '<header>';
		echo '<h1>' . esc_html( $this->config['page_title'] ) . '</h1>';
		echo '</header>';

		if ( function_exists( 'settings_errors' ) ) {
			settings_errors( $this->repository->option_key() );
		}

		echo '<form method="post" id="wpmoo-options-form" action="" enctype="multipart/form-data" autocomplete="off" novalidate="novalidate">';

		if ( function_exists( 'wp_nonce_field' ) ) {
			wp_nonce_field( $this->nonce_action(), $this->nonce_name() );
		}

		echo '<input type="hidden" name="_wpmoo_options_page" value="' . esc_attr( $this->config['menu_slug'] ) . '" />';

		foreach ( $sections as $section ) {
			$section_id    = $section['id'];
			$section_title = ! empty( $section['title'] ) ? $section['title'] : ucfirst( str_replace( '-', ' ', $section_id ) );
			$section_desc  = ! empty( $section['description'] ) ? $section['description'] : '';

			$sec_class = '';
			if ( isset( $section['css_class'] ) && is_string( $section['css_class'] ) && '' !== trim( $section['css_class'] ) ) {
				$sec_class = ' class="' . esc_attr( trim( (string) $section['css_class'] ) ) . '"';
			}
			echo '<section id="' . esc_attr( $section_id ) . '"' . $sec_class . '>';
			echo '<header>';
			echo '<h2>' . esc_html( $section_title ) . '</h2>';
			if ( '' !== $section_desc ) {
				echo '<p>' . esc_html( $section_desc ) . '</p>';
			}
			echo '</header>';

			echo '<div class="grid">';
			foreach ( $section['fields'] as $field ) {
				$this->render_field( $field, $values );
			}
			echo '</div>';
			echo '</section>';
		}

		// Actions
		echo '<footer class="wpmoo-options-actions">';
		if ( function_exists( 'submit_button' ) ) {
			submit_button( __( 'Save Changes', 'wpmoo' ) );
		} else {
			echo '<p class="submit"><button type="submit">' . esc_html( $this->translate( 'Save Changes', 'wpmoo' ) ) . '</button></p>';
		}
		echo '</footer>';

		echo '</form>';
		echo '</main>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// Panel/tab state helpers removed under Pico-first layout.

	/**
	 * Render a single field in the layout.
	 *
	 * @param Field                $field  Field instance.
	 * @param array<string, mixed> $values Current option values.
	 * @return void
	 */
	protected function render_field( Field $field, array $values ) {
		$value         = array_key_exists( $field->id(), $values ) ? $values[ $field->id() ] : $field->default();
		$name          = $this->field_input_name( $field );
		$is_repeatable = method_exists( $field, 'is_repeatable' ) ? $field->is_repeatable() : false;

		$help_html = $field->help_html();

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Field rendering handles escaping.
		$wrapper_classes = array( 'wpmoo-field' );
		if ( method_exists( $field, 'css_class' ) ) {
			$extra = (string) $field->css_class();
			if ( '' !== trim( $extra ) ) {
				// Split by whitespace and append.
				$extras = preg_split( '/\s+/', trim( $extra ) ) ?: array();
				foreach ( $extras as $c ) {
					$c = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $c );
					if ( $c && ! in_array( $c, $wrapper_classes, true ) ) {
						$wrapper_classes[] = $c;
					}
				}
			}
		}
		echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '">';

		if ( $field->before() ) {
			echo $field->before_html();
		}

		if ( $is_repeatable ) {
			$items = is_array( $value ) ? $value : ( ( null !== $value && '' !== $value ) ? array( $value ) : array() );
			if ( empty( $items ) && is_array( $field->default() ) ) {
				$items = (array) $field->default();
			}
			if ( empty( $items ) ) {
				$items = array( '' );
			}
			$min = method_exists( $field, 'min_repeatable' ) ? (int) $field->min_repeatable() : 0;
			$max = method_exists( $field, 'max_repeatable' ) ? (int) $field->max_repeatable() : 0;
			$btn = method_exists( $field, 'add_button_text' ) ? (string) $field->add_button_text() : 'Add';

			echo '<div class="wpmoo-repeat" data-repeat-name="' . esc_attr( $name ) . '" data-repeat-min="' . esc_attr( (string) $min ) . '" data-repeat-max="' . esc_attr( (string) $max ) . '" data-repeat-label="' . esc_attr( $field->label() ? $field->label() : ( function_exists( '__' ) ? __( 'Item', 'wpmoo' ) : 'Item' ) ) . '">';
			echo '<div class="wpmoo-repeat__items">';
			$__i = 0;
			foreach ( $items as $item ) {
				$__i++;
				echo '<fieldset class="wpmoo-repeat__item" data-repeat-index="' . esc_attr( (string) $__i ) . '">';
				echo '<legend>';
				echo '<button type="button" class="wpmoo-repeat__handle" aria-label="' . esc_attr__( 'Move', 'wpmoo' ) . '"><span class="dashicons dashicons-move" aria-hidden="true"></span></button>';
				echo '<span class="wpmoo-repeat__title">' . esc_html( $__i . '. ' . ( $field->label() ? $field->label() : ( function_exists( '__' ) ? __( 'Item', 'wpmoo' ) : 'Item' ) ) ) . '</span>';
				echo '</legend>';
				echo '<div class="wpmoo-repeat__body">';
				echo $field->render( $name . '[]', $item );
				echo '</div>';
				echo '<div class="wpmoo-repeat__actions">';
				echo '<button type="button" class="wpmoo-repeat__clone" data-repeat-clone aria-label="' . esc_attr__( 'Duplicate', 'wpmoo' ) . '"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button>';
				echo '<button type="button" class="wpmoo-repeat__remove" data-repeat-remove aria-label="' . esc_attr__( 'Remove', 'wpmoo' ) . '"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>';
				echo '</div>';
				echo '</fieldset>';
			}
			echo '</div>';
			echo '<button type="button" class="wpmoo-repeat__add" data-repeat-add><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> ' . esc_html( $btn ) . '</button>';
			echo '</div>';
		} else {
			// Let the field render its own label/control; keep description outside per PicoCSS patterns.
			echo $field->render( $name, $value );
			if ( $field->description() ) {
				echo '<small class="description">' . esc_html( $field->description() ) . '</small>';
			}
		}

		if ( $field->after() ) {
			echo $field->after_html();
		}

		echo '</div>';
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render a single field row inside the form table.
	 *
	 * @param Field                $field  Field instance.
	 * @param array<string, mixed> $values Option values.
	 * @return void
	 */
	protected function render_field_row( Field $field, array $values ) {
		$value         = array_key_exists( $field->id(), $values ) ? $values[ $field->id() ] : $field->default();
		$name          = $this->field_input_name( $field );
		$is_repeatable = method_exists( $field, 'is_repeatable' ) ? $field->is_repeatable() : false;

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
		if ( $is_repeatable ) {
			$items = is_array( $value ) ? $value : ( ( null !== $value && '' !== $value ) ? array( $value ) : array() );
			if ( empty( $items ) && is_array( $field->default() ) ) {
				$items = (array) $field->default();
			}
			if ( empty( $items ) ) {
				$items = array( '' );
			}
			$min = method_exists( $field, 'min_repeatable' ) ? (int) $field->min_repeatable() : 0;
			$max = method_exists( $field, 'max_repeatable' ) ? (int) $field->max_repeatable() : 0;
			$btn = method_exists( $field, 'add_button_text' ) ? (string) $field->add_button_text() : 'Add';

			echo '<div class="wpmoo-repeat" data-repeat-name="' . esc_attr( $name ) . '" data-repeat-min="' . esc_attr( (string) $min ) . '" data-repeat-max="' . esc_attr( (string) $max ) . '" data-repeat-label="' . esc_attr( $field->label() ? $field->label() : ( function_exists( '__' ) ? __( 'Item', 'wpmoo' ) : 'Item' ) ) . '">';
			echo '<div class="wpmoo-repeat__items">';
			$__i = 0;
			foreach ( $items as $item ) {
				$__i++;
				echo '<fieldset class="wpmoo-repeat__item" data-repeat-index="' . esc_attr( (string) $__i ) . '">';
				echo '<legend>';
				echo '<button type="button" class="wpmoo-repeat__handle" aria-label="' . esc_attr__( 'Move', 'wpmoo' ) . '"><span class="dashicons dashicons-move" aria-hidden="true"></span></button>';
				echo '<span class="wpmoo-repeat__title">' . esc_html( $__i . '. ' . ( $field->label() ? $field->label() : ( function_exists( '__' ) ? __( 'Item', 'wpmoo' ) : 'Item' ) ) ) . '</span>';
				echo '</legend>';
				echo '<div class="wpmoo-repeat__body">';
				echo $field->render( $name . '[]', $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered field handles escaping internally.
				echo '</div>';
				echo '<div class="wpmoo-repeat__actions">';
				echo '<button type="button" class="wpmoo-repeat__clone" data-repeat-clone aria-label="' . esc_attr__( 'Duplicate', 'wpmoo' ) . '"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button>';
				echo '<button type="button" class="wpmoo-repeat__remove" data-repeat-remove aria-label="' . esc_attr__( 'Remove', 'wpmoo' ) . '"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>';
				echo '</div>';
				echo '</fieldset>';
			}
			echo '</div>';
			echo '<button type="button" class="wpmoo-repeat__add" data-repeat-add><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> ' . esc_html( $btn ) . '</button>';
			echo '</div>';
		} else {
			echo $field->render( $name, $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered field handles escaping internally.

		}

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
			'css_vars'    => array(),
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

		if ( ! is_array( $config['css_vars'] ) ) {
			$config['css_vars'] = array();
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

		// Panel state persistence removed.

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
