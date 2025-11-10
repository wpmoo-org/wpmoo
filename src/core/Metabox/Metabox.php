<?php
/**
 * Handles WordPress metabox registration, rendering, and saving.
 *
 * @package WPMoo\Metabox
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Metabox;

use WP_Post;
use WPMoo\Fields\BaseField as Field;
use WPMoo\Fields\Manager;
use WPMoo\Support\Assets;
use WPMoo\Support\Str;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Represents a single metabox instance.
 */
class Metabox {

	/**
	 * Indicates whether the subsystem has been booted.
	 *
	 * @var bool
	 */
	protected static $booted = false;

	/**
	 * Shared field manager instance.
	 *
	 * @var Manager
	 */
	protected static $shared_manager;

	/**
	 * Whether panel assets are required on admin screens.
	 *
	 * @var bool
	 */
	protected static $needs_assets = false;

	/**
	 * Registered metaboxes.
	 *
	 * @var Metabox[]
	 */
	protected static $metaboxes = array();

	/**
	 * Normalized metabox configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected $config;

	/**
	 * Field instances keyed by field id.
	 *
	 * @var array<string, Field>
	 */
	protected $fields = array();

	/**
	 * Structured sections for panel layout.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $sections = array();

	/**
	 * Field manager dependency.
	 *
	 * @var Manager
	 */
	protected $field_manager;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config        Raw configuration.
	 * @param Manager|null         $field_manager Field manager instance.
	 */
	public function __construct( array $config, ?Manager $field_manager = null ) {
		if ( null === $field_manager ) {
			self::ensure_booted();
			$field_manager = self::$shared_manager;
		}

		$this->field_manager = $field_manager;
		$this->config        = $this->normalize_config( $config );
		$this->fields        = $this->instantiate_fields( $this->config['fields'] );
		$this->sections      = $this->prepare_sections( $this->config['sections'] );
	}

	/**
	 * Start building a new metabox.
	 *
	 * @param string $id Metabox identifier.
	 * @return Builder
	 */
	public static function create( string $id ): Builder {
		self::ensure_booted();

		return new Builder( $id, self::$shared_manager );
	}

	/**
	 * Register a new metabox (backward compatibility).
	 *
	 * @param string|array<string, mixed> $id_or_config Metabox ID or full config array.
	 * @return Builder|Metabox
	 */
	public static function register( $id_or_config ) {
		self::ensure_booted();

		// Backward compatibility: if array is passed, use old method.
		if ( is_array( $id_or_config ) ) {
			return self::registerFromArray( $id_or_config );
		}

		// New fluent API: return Builder.
		return self::create( (string) $id_or_config );
	}

	/**
	 * Register from array configuration (backward compatibility).
	 *
	 * @param array<string, mixed> $config Metabox configuration.
	 * @return Metabox
	 */
	protected static function registerFromArray( array $config ): Metabox {
		$metabox           = new self( $config, self::$shared_manager );
		self::$metaboxes[] = $metabox;

		$metabox->boot();

		return $metabox;
	}

	/**
	 * Internal method to register a metabox from Builder.
	 *
	 * @param Metabox $metabox Metabox instance.
	 * @return void
	 */
	public static function registerMetabox( Metabox $metabox ): void {
		self::$metaboxes[] = $metabox;

		if ( $metabox->uses_panel() ) {
			self::$needs_assets = true;
		}
	}

	/**
	 * Enqueue shared assets when panel layouts are used.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( ! self::$needs_assets ) {
			return;
		}

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$assets_url = Assets::url();
		$ui_css_url = Assets::ui_css_url();
		$version    = defined( 'WPMOO_VERSION' ) ? WPMOO_VERSION : '0.1.0';

		if ( function_exists( 'wp_enqueue_style' ) ) {
			wp_enqueue_style( 'dashicons' );
			// Prefer UI CSS bundle; fall back to framework assets when missing.
			if ( ! empty( $ui_css_url ) ) {
				wp_enqueue_style( 'wpmoo', $ui_css_url, array(), $version );
			} elseif ( ! empty( $assets_url ) ) {
				wp_enqueue_style( 'wpmoo', $assets_url . 'css/wpmoo.css', array(), $version );
			}
		}

		if ( function_exists( 'wp_enqueue_script' ) ) {
			wp_enqueue_script( 'postbox' );
			if ( ! empty( $assets_url ) ) {
				wp_enqueue_script( 'wpmoo', $assets_url . 'js/wpmoo.js', array( 'jquery', 'jquery-ui-sortable' ), $version, true );
			}
		}
	}

	/**
	 * Retrieve registered metabox instances.
	 *
	 * @return Metabox[]
	 */
	public static function all() {
		return self::$metaboxes;
	}

	/**
	 * Return the shared field manager.
	 *
	 * @return Manager
	 */
	public static function field_manager() {
		self::ensure_booted();

		return self::$shared_manager;
	}

	/**
	 * Bootstrap the subsystem.
	 *
	 * @return void
	 */
	public static function ensure_booted() {
		if ( self::$booted ) {
			return;
		}

		self::$shared_manager = Manager::instance();

		if ( function_exists( 'add_action' ) ) {
			add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		}

		self::$booted = true;
	}

	/**
	 * Register required WordPress hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register the metabox with WordPress.
	 *
	 * @return void
	 */
	public function register_metabox() {
		$screens = $this->config['screens'];

		foreach ( $screens as $screen ) {
			add_meta_box(
				$this->config['id'],
				$this->config['title'],
				array( $this, 'render' ),
				$screen,
				$this->config['context'],
				$this->config['priority'],
				$this->config['callback_args']
			);
		}
	}

	/**
	 * Render the metabox UI.
	 *
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public function render( $post ) {
		if ( function_exists( 'wp_nonce_field' ) ) {
			wp_nonce_field( $this->nonce_action(), $this->nonce_name() );
		}

		// Scope UI with .wpmoo so Pico-based styles apply only within WPMoo areas.
		echo '<div class="wpmoo">';

		// Render sections with grid; fallback to all fields in a single section.
		if ( 'panel' === $this->config['layout'] || ! empty( $this->sections ) ) {
			$this->render_sections( $post );
			echo '</div>';
			return;
		}

		echo '<section class="wpmoo-metabox">';
		echo '<div class="grid">';

		foreach ( $this->fields as $field ) {
			$this->render_field( $field, $post );
		}

		echo '</div>';
		echo '</section>';
		echo '</div>';
	}

	/**
	 * Determine whether the metabox uses the panel layout.
	 *
	 * @return bool
	 */
	public function uses_panel(): bool {
		return 'panel' === $this->config['layout'] || ! empty( $this->sections );
	}

	/**
	 * Render the panel layout inside the metabox.
	 *
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	protected function render_sections( $post ) {
		$sections = $this->sections;
		$used_ids = array();

		foreach ( $sections as $section ) {
			foreach ( $section['fields'] as $field ) {
				$used_ids[] = $field->id();
			}
		}

		$remaining_fields = array();
		foreach ( $this->fields as $field ) {
			if ( ! in_array( $field->id(), $used_ids, true ) ) {
				$remaining_fields[] = $field;
			}
		}

		if ( empty( $sections ) ) {
			$sections = array(
				array(
					'id'          => $this->config['id'] . '-section',
					'title'       => $this->config['title'],
					'description' => '',
					'icon'        => '',
					'fields'      => array_values( $this->fields ),
				),
			);
		} elseif ( ! empty( $remaining_fields ) ) {
			$sections[] = array(
				'id'          => $this->config['id'] . '-general',
				'title'       => $this->config['title'],
				'description' => '',
				'icon'        => '',
				'fields'      => $remaining_fields,
			);
		}

		foreach ( $sections as $section ) {
			$section_id    = $section['id'];
			$section_title = $section['title'];
			$section_desc  = $section['description'];

			echo '<section id="' . esc_attr( $section_id ) . '">';
			echo '<header>';
			// In metabox context, the box title is already visible; use h3 for sub-sections when multiple.
			echo '<h3>' . esc_html( $section_title ) . '</h3>';
			if ( '' !== $section_desc ) {
				echo '<p>' . esc_html( $section_desc ) . '</p>';
			}
			echo '</header>';

			echo '<div class="grid">';
			foreach ( $section['fields'] as $field ) {
				$this->render_field( $field, $post );
			}
			echo '</div>';
			echo '</section>';
		}
	}

	/**
	 * Persist submitted field values.
	 *
	 * @param int     $post_id Post identifier.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! $this->should_handle_save( $post_id, $post ) ) {
			return;
		}

		$submitted = array();
		$metabox_payload = filter_input( \INPUT_POST, 'wpmoo_metabox', FILTER_DEFAULT, \FILTER_REQUIRE_ARRAY );
		if ( is_array( $metabox_payload ) && isset( $metabox_payload[ $this->config['id'] ] ) && is_array( $metabox_payload[ $this->config['id'] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified via nonce in should_handle_save().
			$submitted = $metabox_payload[ $this->config['id'] ];
		}

		$submitted = function_exists( 'wp_unslash' )
			? wp_unslash( $submitted )
			: $submitted;

		foreach ( $this->fields as $field ) {
			$key       = $field->id();
			$value     = array_key_exists( $key, $submitted ) ? $submitted[ $key ] : null;

			if ( method_exists( $field, 'should_save' ) && ! $field->should_save() ) {
				continue;
			}

			$sanitized = $field->sanitize( $value );

			if ( method_exists( $field, 'is_repeatable' ) && $field->is_repeatable() && is_array( $sanitized ) ) {
				// Optionally store as multiple rows.
				if ( method_exists( $field, 'repeatable_as_multiple' ) && $field->repeatable_as_multiple() ) {
					if ( function_exists( 'delete_post_meta' ) ) {
						delete_post_meta( $post_id, $key );
					}

					foreach ( $sanitized as $item ) {
						if ( '' === (string) $item ) {
							continue;
						}
						add_post_meta( $post_id, $key, $item );
					}
				} else {
					update_post_meta( $post_id, $key, array_values( $sanitized ) );
				}
			} else {
				update_post_meta( $post_id, $key, $sanitized );
			}
		}
	}

	/**
	 * Determine whether the current save request should be processed.
	 *
	 * @param int     $post_id Current post id.
	 * @param WP_Post $post    Current post object.
	 * @return bool
	 */
	protected function should_handle_save( $post_id, $post ) {
		if ( ! in_array( $post->post_type, $this->config['screens'], true ) ) {
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
			return false;
		}

		if ( function_exists( 'current_user_can' ) && ! current_user_can( $this->config['capability'], $post_id ) ) {
			return false;
		}

		$nonce_name = $this->nonce_name();

		if ( ! isset( $_POST[ $nonce_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.NoNonceVerification -- Checked here.
			return false;
		}

		if ( ! function_exists( 'wp_verify_nonce' ) ) {
			return true;
		}

			$nonce_val = isset( $_POST[ $nonce_name ] )
				? ( function_exists( 'sanitize_text_field' ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) ) : (string) $_POST[ $nonce_name ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				: '';
			return (bool) wp_verify_nonce( $nonce_val, $this->nonce_action() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Render a single field wrapper.
	 *
	 * @param Field   $field Field instance.
	 * @param WP_Post $post  Current post object.
	 * @return void
	 */
	protected function render_field( Field $field, $post ) {
		$is_repeatable = method_exists( $field, 'is_repeatable' ) ? $field->is_repeatable() : false;
		$as_multiple   = $is_repeatable && method_exists( $field, 'repeatable_as_multiple' ) ? $field->repeatable_as_multiple() : false;

		if ( $is_repeatable && $as_multiple ) {
			$current = get_post_meta( $post->ID, $field->id(), false );
		} else {
			$current = get_post_meta( $post->ID, $field->id(), true );
		}

		$value = '' !== $current ? $current : $field->default();
		$name  = sprintf( 'wpmoo_metabox[%s][%s]', $this->config['id'], $field->id() );
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

		// Pico form semantics: prefer <label> wrapping control + <small> for help.
		echo '<div class="wpmoo-field">';

		if ( $field->before() ) {
			echo $field->before_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
				echo '<button type="button" class="wpmoo-repeat__handle" aria-label="' . esc_attr__( 'Move', 'wpmoo' ) . '"><span class="dashicons dashicons-menu" aria-hidden="true"></span></button>';
				echo '<span class="wpmoo-repeat__title">' . esc_html( $__i . '. ' . ( $field->label() ? $field->label() : ( function_exists( '__' ) ? __( 'Item', 'wpmoo' ) : 'Item' ) ) ) . '</span>';
				echo '</legend>';
				echo '<div class="wpmoo-repeat__body">';
				echo $field->render( $name . '[]', $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</div>';
				echo '<div class="wpmoo-repeat__actions">';
				echo '<button type="button" class="wpmoo-repeat__move-up" data-repeat-up aria-label="' . esc_attr__( 'Move up', 'wpmoo' ) . '"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>';
				echo '<button type="button" class="wpmoo-repeat__move-down" data-repeat-down aria-label="' . esc_attr__( 'Move down', 'wpmoo' ) . '"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>';
				echo '<button type="button" class="wpmoo-repeat__remove" data-repeat-remove aria-label="' . esc_attr__( 'Remove', 'wpmoo' ) . '"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>';
				echo '</div>';
				echo '</fieldset>';
			}
			echo '</div>';
			echo '<button type="button" class="wpmoo-repeat__add" data-repeat-add><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> ' . esc_html( $btn ) . '</button>';
			echo '</div>';
		} else {
			// Let the field render its own label/control per PicoCSS; show description outside.
			echo $field->render( $name, $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( $field->description() ) {
				echo '<small class="description">' . esc_html( $field->description() ) . '</small>';
			}
		}

		if ( $field->after() ) {
			echo $field->after_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</div>';
		echo '<div class="clear"></div>';
	}

	/**
	 * Normalize configuration values.
	 *
	 * @param array<string, mixed> $config Raw configuration.
	 * @return array<string, mixed>
	 */
	protected function normalize_config( array $config ) {
		$defaults = array(
			'id'            => '',
			'title'         => '',
			'screens'       => array( 'post' ),
			'context'       => 'advanced',
			'priority'      => 'default',
			'callback_args' => array(),
			'capability'    => 'edit_post',
			'fields'        => array(),
			'layout'        => 'default',
			'sections'      => array(),
		);

		$config = array_merge( $defaults, $config );

		if ( '' === $config['id'] ) {
			if ( function_exists( 'sanitize_key' ) ) {
				$config['id'] = sanitize_key( $config['title'] );
			} else {
				$config['id'] = strtolower( preg_replace( '/[^a-z0-9]+/', '_', $config['title'] ) );
			}
		}

		if ( '' === $config['title'] ) {
			$config['title'] = ucfirst( str_replace( '_', ' ', $config['id'] ) );
		}

		if ( empty( $config['screens'] ) ) {
			$config['screens'] = array( 'post' );
		}

		if ( is_string( $config['screens'] ) ) {
			$config['screens'] = array( $config['screens'] );
		}

		if ( ! empty( $config['sections'] ) && 'panel' !== $config['layout'] ) {
			$config['layout'] = 'panel';
		}

		return $config;
	}

	/**
	 * Instantiate field objects.
	 *
	 * @param array<int, array<string, mixed>> $field_configs Raw field definitions.
	 * @return array<string, Field>
	 */
	protected function instantiate_fields( array $field_configs ) {
		$fields = array();

		foreach ( $field_configs as $field_config ) {
			if ( empty( $field_config['id'] ) ) {
				continue;
			}

			$field_config['field_manager'] = $this->field_manager;

			$field                  = $this->field_manager->make( $field_config );
			$fields[ $field->id() ] = $field;
		}

		return $fields;
	}

	/**
	 * Normalize panel sections using instantiated fields.
	 *
	 * @param array<int, array<string, mixed>> $sections Raw section configuration.
	 * @return array<int, array<string, mixed>>
	 */
	protected function prepare_sections( array $sections ) {
		$normalized = array();

		foreach ( $sections as $section ) {
			$defaults = array(
				'id'          => '',
				'title'       => '',
				'description' => '',
				'icon'        => '',
				'fields'      => array(),
			);

			$section = array_merge( $defaults, is_array( $section ) ? $section : array() );

			if ( '' === $section['id'] ) {
				$section['id'] = Str::slug( $section['title'] ? $section['title'] : uniqid( 'section_', true ) );
			}

			if ( '' === $section['title'] ) {
				$section['title'] = ucfirst( str_replace( array( '-', '_' ), ' ', $section['id'] ) );
			}

			$field_objects = array();

			foreach ( $section['fields'] as $field_config ) {
				if ( empty( $field_config['id'] ) ) {
					continue;
				}

				$identifier = $field_config['id'];

				if ( isset( $this->fields[ $identifier ] ) ) {
					$field_objects[] = $this->fields[ $identifier ];
				}
			}

			$section['fields'] = $field_objects;
			$normalized[]      = $section;
		}

		return $normalized;
	}

	/**
	 * Build the nonce field name.
	 *
	 * @return string
	 */
	protected function nonce_name() {
		return '_wpmoo_metabox_nonce_' . $this->config['id'];
	}

	/**
	 * Build the nonce action.
	 *
	 * @return string
	 */
	protected function nonce_action() {
		return 'wpmoo_metabox_' . $this->config['id'] . '_save';
	}
}
