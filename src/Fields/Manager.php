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
use WPMoo\Fields\Accordion\Accordion;
use WPMoo\Fields\Checkbox\Checkbox;
use WPMoo\Fields\Color\Color;
use WPMoo\Fields\Fieldset\Fieldset;
use WPMoo\Fields\Text\Text;
use WPMoo\Fields\Textarea\Textarea;
use WPMoo\Support\Concerns\TranslatesStrings;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Keeps track of field type mappings.
 */
class Manager {
	use TranslatesStrings;

	/**
	 * Shared singleton instance.
	 *
	 * @var Manager|null
	 */
	protected static $instance = null;

	/**
	 * Registered field type map.
	 *
	 * @var array<string, class-string<BaseField>>
	 */
	protected $types = array();

	/**
	 * Tracks missing field notifications to avoid duplicates.
	 *
	 * @var array<string, bool>
	 */
	protected $missing_notices = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_default_types();
		$this->trigger_registration_hook();
	}

	/**
	 * Retrieve the shared manager instance.
	 *
	 * @return Manager
	 */
	public static function instance(): Manager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

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
	 * @return BaseField
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
	 * @throws InvalidArgumentException When the type/class pair is invalid.
	 */
	protected function validate_type_class_pair( $type, $class ) {
		if ( ! is_string( $type ) || '' === $type ) {
			/* phpcs:disable WordPress.Security.EscapeOutput */
			throw new InvalidArgumentException( $this->translate( 'Field type must be a non-empty string.' ) );
			/* phpcs:enable WordPress.Security.EscapeOutput */
		}

		if ( ! class_exists( $class ) ) {
				/* phpcs:disable WordPress.Security.EscapeOutput */
				throw new InvalidArgumentException(
					sprintf(
						$this->translate( 'Field class "%s" does not exist.' ),
						$class
					)
				);
				/* phpcs:enable WordPress.Security.EscapeOutput */
		}

		if ( ! is_subclass_of( $class, BaseField::class ) ) {
				/* phpcs:disable WordPress.Security.EscapeOutput */
				throw new InvalidArgumentException(
					sprintf(
						$this->translate( 'Field class "%1$s" must extend %2$s.' ),
						$class,
						BaseField::class
					)
				);
				/* phpcs:enable WordPress.Security.EscapeOutput */
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

			$message_template = function_exists( '__' )
				// translators: %s: field type slug.
				? \__( 'WPMoo: Field type "%s" could not be loaded. Please ensure its class is autoloaded or registered.', 'wpmoo' )
				: 'WPMoo: Field type "%s" could not be loaded. Please ensure its class is autoloaded or registered.';

			$raw_notice = sprintf( $message_template, $type );

		$admin_notice = function_exists( 'esc_html' )
			? esc_html( $raw_notice )
			: htmlspecialchars( $raw_notice, ENT_QUOTES, 'UTF-8' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

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
				$debug_template = function_exists( '__' )
					// translators: %s: field type slug.
					? \__( 'WPMoo: Field type "%s" is not registered.', 'wpmoo' )
					: 'WPMoo: Field type "%s" is not registered.';

				$log_message = sprintf( $debug_template, $type );
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
			protected string $missing_type;

			public function __construct( array $config, string $missing_type ) {
				$this->missing_type = $missing_type;
				parent::__construct( $config );
			}

			public function render( $name, $value ) {
					$missing_template = function_exists( '__' )
						// translators: %s: missing field type slug.
						? \__( 'Missing WPMoo field type "%s". Please register or include the field class.', 'wpmoo' )
						: 'Missing WPMoo field type "%s". Please register or include the field class.';

					$raw_message = sprintf( $missing_template, $this->missing_type );
				$message     = function_exists( 'esc_html' ) ? esc_html( $raw_message ) : $raw_message;

				return sprintf( '<div class="notice notice-error inline"><p>%s</p></div>', $message );
			}

		};
	}

	/**
	 * Register the built-in field types with this manager instance.
	 *
	 * @return void
	 */
	protected function register_default_types(): void {
		$defaults = $this->default_type_map();

		if ( function_exists( 'apply_filters' ) ) {
			$defaults = (array) apply_filters( 'wpmoo_default_field_types', $defaults, $this );
		}

		foreach ( $defaults as $type => $class ) {
			if ( ! is_string( $type ) || '' === $type || $this->has( $type ) ) {
				continue;
			}

			try {
				$this->register( $type, $class );
			} catch ( InvalidArgumentException $exception ) {
				// Skip invalid default registrations silently to avoid fatal errors.
				continue;
			}
		}
	}

	/**
	 * Provide the framework's default field map.
	 *
	 * @return array<string, class-string<Field>>
	 */
	protected function default_type_map(): array {
		return array(
			'text'      => Text::class,
			'textarea'  => Textarea::class,
			'checkbox'  => Checkbox::class,
			'color'     => Color::class,
			'accordion' => Accordion::class,
			'fieldset'  => Fieldset::class,
		);
	}

	/**
	 * Trigger the registration hook so consumers can add custom field types.
	 *
	 * @return void
	 */
	protected function trigger_registration_hook(): void {
		if ( function_exists( 'do_action' ) ) {
			do_action( 'wpmoo_register_field_types', $this );
		}
	}
}
