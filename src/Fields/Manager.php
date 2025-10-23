<?php
/**
 * Handles field registration and instantiation for WPMoo.
 *
 * @package WPMoo\Fields
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Fields;

use InvalidArgumentException;
use WPMoo\Support\Concerns\TranslatesStrings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps track of field type mappings.
 */
class Manager {
	use TranslatesStrings;

	/**
	 * Registered field type map.
	 *
	 * @var array<string, class-string<Field>>
	 */
	protected $types = array();

	/**
	 * Tracks missing field notifications to avoid duplicates.
	 *
	 * @var array<string, bool>
	 */
	protected $missing_notices = array();

	/**
	 * Register a new field type.
	 *
	 * @param string $type  Field type key.
	 * @param string $class Field class name.
	 * @return void
	 * @throws InvalidArgumentException If the registration arguments are invalid.
	 */
	public function register( $type, $class ) {
		$this->validate_type_class_pair( $type, $class );

		$this->types[ $type ] = $class;
	}

	/**
	 * Determine whether a field type is registered.
	 *
	 * @param string $type Field type key.
	 * @return bool
	 */
	public function has( $type ) {
		return isset( $this->types[ $type ] );
	}

	/**
	 * Create a field instance.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @return Field
	 * @throws InvalidArgumentException When a field type has not been registered.
	 */
	public function make( array $config ) {
		$type  = isset( $config['type'] ) ? $config['type'] : 'text';
		$class = isset( $config['class'] ) ? $config['class'] : null;

		if ( ! $this->has( $type ) ) {
			$this->maybe_autoregister( $type, $class );
		}

		if ( ! $this->has( $type ) ) {
			$this->notify_missing_field( $type );
			return $this->fallback_field( $config );
		}

		$class          = $this->types[ $type ];
		$config['type'] = $type;

		return new $class( $config );
	}

	/**
	 * Return a list of registered type keys.
	 *
	 * @return string[]
	 */
	public function types() {
		return array_keys( $this->types );
	}

	/**
	 * Validate the provided type/class pair.
	 *
	 * @param string $type  Field type key.
	 * @param string $class Field class name.
	 * @return void
	 */
	protected function validate_type_class_pair( $type, $class ) {
		if ( ! is_string( $type ) || '' === $type ) {
			throw new InvalidArgumentException( esc_html__( 'Field type must be a non-empty string.', 'wpmoo' ) );
		}

		if ( ! class_exists( $class ) ) {
			throw new InvalidArgumentException(
				sprintf(
					esc_html__( 'Field class "%s" does not exist.', 'wpmoo' ),
					esc_html( $class )
				)
			);
		}

		if ( ! is_subclass_of( $class, Field::class ) ) {
			throw new InvalidArgumentException(
				sprintf(
					esc_html__( 'Field class "%1$s" must extend %2$s.', 'wpmoo' ),
					esc_html( $class ),
					esc_html( Field::class )
				)
			);
		}
	}

	/**
	 * Attempt to automatically register a field class for the given type.
	 *
	 * @param string      $type  Field type key or class name.
	 * @param string|null $class Optional explicit class name.
	 * @return void
	 */
	protected function maybe_autoregister( $type, $class = null ) {
		if ( $class ) {
			$this->register( $type, $class );
			return;
		}

		if ( class_exists( $type ) ) {
			$this->register( $type, $type );
			return;
		}

		$candidate = $this->resolve_class_from_type( $type );

		if ( $candidate && class_exists( $candidate ) ) {
			$this->register( $type, $candidate );
		}
	}

	/**
	 * Resolve a potential class name from a field type slug.
	 *
	 * @param string $type Field type key.
	 * @return string|null
	 */
	protected function resolve_class_from_type( $type ) {
		$studly = str_replace( ' ', '', ucwords( str_replace( array( '-', '_' ), ' ', $type ) ) );

		$candidates = array(
			"WPMoo\\Fields\\{$studly}\\{$studly}",
			"WPMoo\\Fields\\{$studly}",
		);

		foreach ( $candidates as $candidate ) {
			if ( class_exists( $candidate ) ) {
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Notify administrators about missing field types.
	 *
	 * @param string $type Missing type key.
	 * @return void
	 */
	protected function notify_missing_field( $type ) {
		if ( isset( $this->missing_notices[ $type ] ) ) {
			return;
		}

		$admin_notice = sprintf(
			esc_html__( 'WPMoo: Field type "%s" could not be loaded. Please ensure its class is autoloaded or registered.', 'wpmoo' ),
			esc_html( $type )
		);

		if ( function_exists( 'add_action' ) ) {
			add_action(
				'admin_notices',
				static function () use ( $admin_notice ) {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html( $admin_notice )
					);
				}
			);
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$log_message = sprintf(
				esc_html__( 'WPMoo: Field type "%s" is not registered.', 'wpmoo' ),
				esc_html( $type )
			);
			error_log( $log_message );
		}

		$this->missing_notices[ $type ] = true;
	}

	/**
	 * Provide a harmless fallback field when a type cannot be resolved.
	 *
	 * @param array<string, mixed> $config Field configuration.
	 * @return Field
	 */
	protected function fallback_field( array $config ) {
		$type = isset( $config['type'] ) ? $config['type'] : 'unknown';

		return new class( $config, $type ) extends Field {

			/**
			 * Missing type slug.
			 *
			 * @var string
			 */
			protected $missing_type;

			public function __construct( array $config, $missing_type ) {
				$this->missing_type = $missing_type;
				parent::__construct( $config );
			}

			public function render( $name, $value ) {
				$raw_message = sprintf(
					function_exists( '__' )
						? \__( 'Missing WPMoo field type "%s". Please register or include the field class.', 'wpmoo' )
						: 'Missing WPMoo field type "%s". Please register or include the field class.',
					$this->missing_type
				);
				$message     = function_exists( 'esc_html' ) ? esc_html( $raw_message ) : $raw_message;

				return sprintf( '<div class="notice notice-error inline"><p>%s</p></div>', $message );
			}

		};
	}

	/**
	 * Translate strings while remaining compatible with non-WordPress contexts.
	 *
	 * @param string $text Text to translate.
	 * @return string
	 */
}
