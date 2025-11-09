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
use WPMoo\Layout\Header;
use WPMoo\Layout\Sidebar;
use WPMoo\Layout\Footer;
use WPMoo\Support\Concerns\TranslatesStrings;
use WPMoo\Options\OptionRepository;
use WPMoo\Support\Str;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Builds a WordPress admin options page from configuration.
 */
/**
 * Page configuration shape for static analysis.
 *
 * @phpstan-type PageConfig array{
 *   page_title: string,
 *   menu_title: string,
 *   menu_slug: string,
 *   option_key: string,
 *   capability: string,
 *   parent_slug: string,
 *   position: int|null,
 *   icon: string,
 *   sections: array<int, array<string,mixed>>,
 *   style: string,
 *   framework_title: string,
 *   menu_type: string,
 *   menu_parent: string,
 *   menu_hidden: bool,
 *   show_bar_menu: bool,
 *   show_sub_menu: bool,
 *   show_in_network: bool,
 *   show_in_customizer: bool,
 *   show_search: bool,
 *   show_reset_all: bool,
 *   show_reset_section: bool,
 *   show_all_options: bool,
 *   show_form_warning: bool,
 *   sticky_header: bool,
 *   save_defaults: bool,
 *   ajax_save: bool,
 *   admin_bar_menu_icon: string,
 *   admin_bar_menu_priority: int,
 *   footer_text: string,
 *   footer_after: string,
 *   footer_credit: string,
 *   database: string,
 *   transient_time: int,
 *   contextual_help: array<int,mixed>,
 *   contextual_help_sidebar: string,
 *   enqueue_webfont: bool,
 *   async_webfont: bool,
 *   output_css: bool,
 *   nav: string,
 *   theme: string,
 *   class: string,
 *   fluid: bool,
 *   sidebar_nav: bool,
 *   defaults: array<string,mixed>
 * }
 */
class Page {
	use TranslatesStrings;

	/**
	 * Registry of pages and their sections for sidebar navigation.
	 *
	 * @var array<int|string, array<string, mixed>>
	 */
	protected static $nav_registry = array();

	/**
	 * Default header component.
	 *
	 * @var Header|null
	 */
	protected static $default_header_component = null;

	/**
	 * Default sidebar component.
	 *
	 * @var Sidebar|null
	 */
	protected static $default_sidebar_component = null;

	/**
	 * Default footer component.
	 *
	 * @var Footer|null
	 */
	protected static $default_footer_component = null;

	/**
	 * Tracks whether smooth scroll script has been printed.
	 *
	 * @var bool
	 */
	protected static $smooth_scroll_script_printed = false;

	/**
	 * Normalized page configuration.
	 *
	 * @var PageConfig
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
	 * Header component instance.
	 *
	 * @var Header
	 */
	protected $header_component;

	/**
	 * Sidebar component instance.
	 *
	 * @var Sidebar
	 */
	protected $sidebar_component;

	/**
	 * Footer component instance.
	 *
	 * @var Footer
	 */
	protected $footer_component;

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
		$this->header_component  = $this->resolve_header_component( $this->config );
		$this->sidebar_component = $this->resolve_sidebar_component( $this->config );
		$this->footer_component  = $this->resolve_footer_component( $this->config );
		$this->register_nav_entry();
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
								'ajax_url'    => admin_url( 'admin-ajax.php' ),
								'nonce'       => wp_create_nonce( 'wpmoo_options_save' ),
								'menu_slug'   => $this->config['menu_slug'],
								'ajax_save'   => (bool) ( isset( $this->config['ajax_save'] ) ? $this->config['ajax_save'] : false ),
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

		// Compose optional style attribute.
		// New config key: 'style' (raw style string). Back-compat: 'css_vars' map of --wpmoo-* to values.
		$style_attr = '';
		if ( isset( $this->config['style'] ) && is_string( $this->config['style'] ) && '' !== trim( $this->config['style'] ) ) {
			$style_attr = ' style="' . esc_attr( trim( (string) $this->config['style'] ) ) . '"';
		} elseif ( isset( $this->config['css_vars'] ) && is_array( $this->config['css_vars'] ) && ! empty( $this->config['css_vars'] ) ) {
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
		// Base classes with container auto-detection.
		$base_classes    = array( 'wpmoo' );
		$has_container   = false;
		// Sticky header marker class to activate CSS.
		if ( ! empty( $this->config['sticky_header'] ) ) {
			$base_classes[] = 'wpmoo--sticky-header';
		}
		if ( isset( $this->config['class'] ) && is_string( $this->config['class'] ) && '' !== trim( $this->config['class'] ) ) {
			$extras = preg_split( '/\s+/', trim( (string) $this->config['class'] ) );
			if ( ! is_array( $extras ) ) {
				$extras = array();
			}
			foreach ( $extras as $c ) {
				$c = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $c );
				if ( $c && ! in_array( $c, $base_classes, true ) ) {
					$base_classes[] = $c;
				}
				if ( in_array( $c, array( 'container', 'container-fluid' ), true ) ) {
					$has_container = true;
				}
			}
		}

		if ( ! empty( $this->config['fluid'] ) ) {
			if ( ! in_array( 'container-fluid', $base_classes, true ) ) {
				$base_classes[] = 'container-fluid';
			}
			$has_container = true;
		}

		if ( ! $has_container ) {
			$base_classes[] = 'container';
		}

		$class_attr = ' class="' . esc_attr( implode( ' ', $base_classes ) ) . '"';

        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output assembled with proper escaping below.
		echo '<main' . $class_attr . ' id="wpmoo-options"' . $style_attr . '>';
		echo $this->header_component->render( $this );

		// Show any settings errors.
		if ( function_exists( 'settings_errors' ) ) {
			settings_errors( $this->repository->option_key() );
		}

		$use_sidebar_nav = ! empty( $this->config['sidebar_nav'] ) && count( $sections ) > 0;

		if ( $use_sidebar_nav ) {
			echo '<div class="wpmoo-layout" data-wpmoo-sidebar>';
			echo $this->sidebar_component->render( $this, self::$nav_registry );
			echo '<div class="wpmoo-content">';
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
			echo '<h3>' . esc_html( $section_title ) . '</h3>';
			if ( '' !== $section_desc ) {
				echo '<p>' . esc_html( $section_desc ) . '</p>';
			}
			echo '</header>';

			$layout_meta = $this->prepare_section_layout( $section );
			$active      = null;

			foreach ( $section['fields'] as $field ) {
				$field_id    = method_exists( $field, 'id' ) ? (string) $field->id() : '';
				$group_index = $this->section_layout_group_index( $layout_meta, $field_id );

				if ( null !== $active && ( null === $group_index || $group_index !== $active['index'] ) ) {
					$this->close_layout_group( $layout_meta, $active['index'] );
					$active = null;
				}

				if ( null !== $group_index && ( null === $active || $group_index !== $active['index'] ) ) {
					$this->open_layout_group( $layout_meta, $group_index );
					$active = array(
						'index'     => $group_index,
						'remaining' => $this->section_layout_group_size( $layout_meta, $group_index ),
					);
				}

				$this->render_field( $field, $values );

				if ( null !== $active && null !== $group_index && $group_index === $active['index'] ) {
					$active['remaining']--;

					if ( $active['remaining'] <= 0 ) {
						$this->close_layout_group( $layout_meta, $group_index );
						$active = null;
					}
				}
			}

			if ( null !== $active ) {
				$this->close_layout_group( $layout_meta, $active['index'] );
			}

			echo '</section>';
		}

		// Actions
		echo $this->footer_component->render( $this );

		echo '</form>';

		if ( $use_sidebar_nav ) {
			echo '</div>';
			echo '</div>';
		}

		echo '</main>';
		$this->maybe_print_smooth_scroll_script();
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the sidebar navigation for sections.
	 *
	 * @param array<int, array<string, mixed>> $sections Sections array.
	 * @return void
	 */
	protected function render_sidebar_navigation( array $sections ): void {
		echo $this->sidebar_component->render( $this, self::$nav_registry );
	}

	/**
	 * Print the smooth scroll helper script once per request.
	 *
	 * @return void
	 */
	protected function maybe_print_smooth_scroll_script(): void {
		if ( self::$smooth_scroll_script_printed ) {
			return;
		}

		$script = <<<'JS'
(function () {
	var root = document.querySelector('.wpmoo');
	if (!root) {
		return;
	}

	root.addEventListener('click', function (event) {
		var anchor = event.target.closest('a[href^="#"]');
		if (!anchor) {
			return;
		}

		var href = anchor.getAttribute('href');
		if (!href || href.charAt(0) !== '#' || href.length <= 1) {
			return;
		}

		var target = root.querySelector(href);
		if (!target) {
			return;
		}

		event.preventDefault();
		target.scrollIntoView({ behavior: 'smooth', block: 'start' });

		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', href);
		} else {
			window.location.hash = href.substring(1);
		}
	}, true);
})();
JS;

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<script id="wpmoo-smooth-scroll">' . $script . '</script>';
		self::$smooth_scroll_script_printed = true;
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
				$extras = preg_split( '/\s+/', trim( $extra ) );
				if ( ! is_array( $extras ) ) {
					$extras = array();
				}
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
	 * Normalize layout metadata for a section definition.
	 *
	 * @param array<string, mixed> $section Section configuration.
	 * @return array{groups: array<int, array<string,mixed>>, map: array<string,int>}
	 */
	protected function prepare_section_layout( array $section ): array {
		$groups = array();
		$map    = array();

		if ( empty( $section['layout'] ) || ! is_array( $section['layout'] ) ) {
			return array(
				'groups' => $groups,
				'map'    => $map,
			);
		}

		$raw_groups = isset( $section['layout']['groups'] ) && is_array( $section['layout']['groups'] )
			? $section['layout']['groups']
			: array();

		foreach ( $raw_groups as $group ) {
			if ( ! is_array( $group ) || empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			$type = isset( $group['type'] ) ? strtolower( (string) $group['type'] ) : 'grid';

			if ( '' === $type ) {
				continue;
			}

			$field_ids = array();

			foreach ( $group['fields'] as $field_id ) {
				$id = trim( (string) $field_id );

				if ( '' === $id ) {
					continue;
				}

				$field_ids[] = $id;
			}

			if ( empty( $field_ids ) ) {
				continue;
			}

			$index = count( $groups );

			$groups[] = array(
				'type'   => $type,
				'fields' => $field_ids,
			);

			foreach ( $field_ids as $id ) {
				$map[ $id ] = $index;
			}
		}

		return array(
			'groups' => $groups,
			'map'    => $map,
		);
	}

	/**
	 * Resolve which layout group (if any) the field belongs to.
	 *
	 * @param array<string, mixed> $layout_meta Layout metadata.
	 * @param string               $field_id    Field identifier.
	 * @return int|null
	 */
	protected function section_layout_group_index( array $layout_meta, string $field_id ): ?int {
		if ( '' === $field_id || empty( $layout_meta['map'] ) || ! is_array( $layout_meta['map'] ) ) {
			return null;
		}

		return isset( $layout_meta['map'][ $field_id ] )
			? (int) $layout_meta['map'][ $field_id ]
			: null;
	}

	/**
	 * Determine the number of fields inside a group.
	 *
	 * @param array<string, mixed> $layout_meta Layout metadata.
	 * @param int                  $group_index Group index.
	 * @return int
	 */
	protected function section_layout_group_size( array $layout_meta, int $group_index ): int {
		if ( empty( $layout_meta['groups'] ) || ! isset( $layout_meta['groups'][ $group_index ] ) ) {
			return 0;
		}

		$group = $layout_meta['groups'][ $group_index ];

		return isset( $group['fields'] ) && is_array( $group['fields'] ) ? count( $group['fields'] ) : 0;
	}

	/**
	 * Open the wrapper element for the given layout group.
	 *
	 * @param array<string, mixed> $layout_meta Layout metadata.
	 * @param int                  $group_index Group index.
	 * @return void
	 */
	protected function open_layout_group( array $layout_meta, int $group_index ): void {
		if ( empty( $layout_meta['groups'] ) || ! isset( $layout_meta['groups'][ $group_index ] ) ) {
			return;
		}

		$group = $layout_meta['groups'][ $group_index ];

		switch ( $group['type'] ) {
			case 'grid':
				echo '<div class="grid">';
				break;
		}
	}

	/**
	 * Close the wrapper element for the given layout group.
	 *
	 * @param array<string, mixed> $layout_meta Layout metadata.
	 * @param int                  $group_index Group index.
	 * @return void
	 */
	protected function close_layout_group( array $layout_meta, int $group_index ): void {
		if ( empty( $layout_meta['groups'] ) || ! isset( $layout_meta['groups'][ $group_index ] ) ) {
			return;
		}

		$group = $layout_meta['groups'][ $group_index ];

		switch ( $group['type'] ) {
			case 'grid':
				echo '</div>';
				break;
		}
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
	 * @param array<string, mixed>|PageConfig $config Raw configuration values.
	 * @return PageConfig
	 */
	protected function normalize_config( array $config ) {
		$defaults = self::defaults();
		$config   = array_merge( $defaults, $config );

		// Derive required basics when omitted.
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
		if ( ! isset( $config['style'] ) || ! is_string( $config['style'] ) ) {
			$config['style'] = '';
		}

		// Legacy mappings.
		if ( isset( $config['menu_capability'] ) && ! isset( $config['capability'] ) ) {
			$config['capability'] = (string) $config['menu_capability'];
		}
		if ( isset( $config['classes'] ) && is_string( $config['classes'] ) ) {
			$config['class'] = trim( (string) ( $config['class'] ? $config['class'] . ' ' : '' ) . $config['classes'] );
		}

		// Submenu → adopt parent when provided.
		if ( '' === $config['parent_slug'] && isset( $config['menu_type'] ) && 'submenu' === $config['menu_type'] && ! empty( $config['menu_parent'] ) ) {
			$config['parent_slug'] = (string) $config['menu_parent'];
		}

		return $config;
	}

	/**
	 * Default configuration for a page.
	 *
	 * Grouped by concern for readability; see PageConfig for schema.
	 *
	 * @return PageConfig
	 */
	protected static function defaults() {
		return array(
			// Basics
			'page_title'  => '',
			'menu_title'  => '',
			'menu_slug'   => '',
			'option_key'  => '',
			'capability'  => 'manage_options',
			'parent_slug' => '',
			'position'    => null,
			'icon'        => '',
			'sections'    => array(),
			// Appearance
			'style'       => '',
			'class'       => '',
			'fluid'       => false,
			'sidebar_nav' => false,
			'theme'       => 'dark',
			'nav'         => 'normal',
			// Behavior/UI
			'framework_title'         => '',
			'menu_type'               => 'menu',
			'menu_parent'             => '',
			'menu_hidden'             => false,
			'show_bar_menu'           => true,
			'show_sub_menu'           => true,
			'show_in_network'         => true,
			'show_in_customizer'      => false,
			'show_search'             => true,
			'show_reset_all'          => true,
			'show_reset_section'      => true,
			'show_all_options'        => true,
			'show_form_warning'       => true,
			'sticky_header'           => true,
			'save_defaults'           => true,
			'ajax_save'               => true,
			'admin_bar_menu_icon'     => '',
			'admin_bar_menu_priority' => 80,
			'footer_text'             => '',
			'footer_after'            => '',
			'footer_credit'           => '',
			'database'                => 'option',
			'transient_time'          => 0,
			'contextual_help'         => array(),
			'contextual_help_sidebar' => '',
			'enqueue_webfont'         => true,
			'async_webfont'           => false,
			'output_css'              => true,
			'header_component'        => null,
			'sidebar_component'       => null,
			'footer_component'        => null,
			'defaults'                => array(),
		);
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
				'layout'      => array(),
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
	 * Register the page and its sections for sidebar navigation.
	 *
	 * @return void
	 */
	protected function register_nav_entry(): void {
		$slug = isset( $this->config['menu_slug'] ) ? (string) $this->config['menu_slug'] : '';

		if ( '' === $slug ) {
			return;
		}

		$page_title = isset( $this->config['page_title'] ) && '' !== (string) $this->config['page_title']
			? (string) $this->config['page_title']
			: ucfirst( str_replace( array( '-', '_' ), ' ', $slug ) );

		$sections = array();

		foreach ( $this->sections as $section ) {
			$section_id    = isset( $section['id'] ) ? (string) $section['id'] : '';
			$section_title = ! empty( $section['title'] ) ? (string) $section['title'] : ucfirst( str_replace( '-', ' ', $section_id ) );

			$sections[] = array(
				'id'    => $section_id,
				'title' => $section_title,
			);
		}

		self::$nav_registry[ $slug ] = array(
			'title'    => $page_title,
			'sections' => $sections,
		);
	}

	/**
	 * Resolve header component for this page.
	 *
	 * @param array<string, mixed> $config Page config.
	 * @return Header
	 */
	protected function resolve_header_component( array $config ): Header {
		if ( isset( $config['header_component'] ) && $config['header_component'] instanceof Header ) {
			$component = clone $config['header_component'];
		} elseif ( self::$default_header_component instanceof Header ) {
			$component = clone self::$default_header_component;
		} else {
			$component = Header::make();
		}

		if ( isset( $config['sticky_header'] ) ) {
			$component->sticky( (bool) $config['sticky_header'] );
		}

		return $component;
	}

	/**
	 * Resolve sidebar component.
	 *
	 * @param array<string, mixed> $config Page config.
	 * @return Sidebar
	 */
	protected function resolve_sidebar_component( array $config ): Sidebar {
		if ( isset( $config['sidebar_component'] ) && $config['sidebar_component'] instanceof Sidebar ) {
			return clone $config['sidebar_component'];
		}

		if ( self::$default_sidebar_component instanceof Sidebar ) {
			return clone self::$default_sidebar_component;
		}

		return Sidebar::make();
	}

	/**
	 * Resolve footer component.
	 *
	 * @param array<string, mixed> $config Page config.
	 * @return Footer
	 */
	protected function resolve_footer_component( array $config ): Footer {
		if ( isset( $config['footer_component'] ) && $config['footer_component'] instanceof Footer ) {
			return clone $config['footer_component'];
		}

		if ( self::$default_footer_component instanceof Footer ) {
			return clone self::$default_footer_component;
		}

		return Footer::make();
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
	 * Retrieve the current page title.
	 *
	 * @return string
	 */
	public function page_title(): string {
		return isset( $this->config['page_title'] ) ? (string) $this->config['page_title'] : '';
	}

	/**
	 * Retrieve the menu slug.
	 *
	 * @return string
	 */
	public function menu_slug(): string {
		return isset( $this->config['menu_slug'] ) ? (string) $this->config['menu_slug'] : '';
	}

	/**
	 * Provide read-only access to the nav registry.
	 *
	 * @return array<int|string, array<string, mixed>>
	 */
	public static function nav_registry(): array {
		return self::$nav_registry;
	}

	/**
	 * Override the default header component for all pages.
	 *
	 * @param Header $header Header component instance.
	 * @return void
	 */
	public static function default_header_component( Header $header ): void {
		self::$default_header_component = clone $header;
	}

	/**
	 * Override the default sidebar component for all pages.
	 *
	 * @param Sidebar $sidebar Sidebar component instance.
	 * @return void
	 */
	public static function default_sidebar_component( Sidebar $sidebar ): void {
		self::$default_sidebar_component = clone $sidebar;
	}

	/**
	 * Override the default footer component for all pages.
	 *
	 * @param Footer $footer Footer component instance.
	 * @return void
	 */
	public static function default_footer_component( Footer $footer ): void {
		self::$default_footer_component = clone $footer;
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
