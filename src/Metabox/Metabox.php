<?php
/**
 * Handles WordPress metabox registration, rendering, and saving.
 *
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Metabox
 * @since 0.1.0
 */

namespace WPMoo\Metabox;

use WP_Post;
use WPMoo\Fields\Field;
use WPMoo\Fields\Manager;

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
	}

	/**
	 * Register a new metabox.
	 *
	 * @param array<string, mixed> $config Metabox configuration.
	 * @return Metabox
	 */
	public static function register( array $config ) {
		self::ensure_booted();

		$metabox = new self( $config, self::$shared_manager );
		self::$metaboxes[] = $metabox;

		$metabox->boot();

		return $metabox;
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
	protected static function ensure_booted() {
		if ( self::$booted ) {
			return;
		}

		self::$shared_manager = new Manager();

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

		echo '<div class="wpmoo-metabox-fields">';

		foreach ( $this->fields as $field ) {
			$this->render_field( $field, $post );
		}

		echo '</div>';
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

		$submitted = isset( $_POST['wpmoo_metabox'][ $this->config['id'] ] ) && is_array( $_POST['wpmoo_metabox'][ $this->config['id'] ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified via nonce in should_handle_save().
			? $_POST['wpmoo_metabox'][ $this->config['id'] ]
			: array();

		$submitted = function_exists( 'wp_unslash' )
			? wp_unslash( $submitted ) // phpcs:ignore WordPress.Security.SafeInput.NotSanitizedInput
			: $submitted;

		foreach ( $this->fields as $field ) {
			$key      = $field->id();
			$value    = array_key_exists( $key, $submitted ) ? $submitted[ $key ] : null;
			$sanitized = $field->sanitize( $value );

			update_post_meta( $post_id, $key, $sanitized );
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

		if ( ! isset( $_POST[ $nonce_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked here.
			return false;
		}

		if ( ! function_exists( 'wp_verify_nonce' ) ) {
			return true;
		}

		return (bool) wp_verify_nonce( $_POST[ $nonce_name ], $this->nonce_action() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified here.
	}

	/**
	 * Render a single field wrapper.
	 *
	 * @param Field   $field Field instance.
	 * @param WP_Post $post  Current post object.
	 * @return void
	 */
	protected function render_field( Field $field, $post ) {
		$current = get_post_meta( $post->ID, $field->id(), true );
		$value   = '' !== $current ? $current : $field->default();
		$name    = sprintf( 'wpmoo_metabox[%s][%s]', $this->config['id'], $field->id() );

		echo '<div class="wpmoo-metabox-field">';
		echo '<label for="' . $this->esc_attr( $field->id() ) . '"><strong>' . $this->esc_html( $field->label() ) . '</strong></label>';
		echo '<div class="wpmoo-metabox-control">';
		echo $field->render( $name, $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Field render method handles escaping.

		if ( $field->description() ) {
			echo '<p class="description">' . $this->esc_html( $field->description() ) . '</p>';
		}

		echo '</div>';
		echo '</div>';
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

			$field = $this->field_manager->make( $field_config );
			$fields[ $field->id() ] = $field;
		}

		return $fields;
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

	/**
	 * Escape a value for HTML output.
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
	 * Escape a value for attribute usage.
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
}
