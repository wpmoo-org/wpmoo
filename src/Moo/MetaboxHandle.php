<?php
/**
 * Fluent wrapper around the metabox builder for Moo::metabox().
 *
 * @package WPMoo\Moo
 */

namespace WPMoo\Moo;

use InvalidArgumentException;
use Traversable;
use WPMoo\Metabox\Builder as MetaboxBuilder;
use WPMoo\Metabox\FieldBuilder as MetaboxFieldBuilder;
use WPMoo\Metabox\Metabox as MetaboxInstance;
use WPMoo\Metabox\SectionBuilder as MetaboxSectionBuilder;
use WPMoo\Options\Field as FieldDefinition;
use WPMoo\Options\FieldBuilder as OptionsFieldBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides a fluent interface for configuring metaboxes via Moo::metabox().
 */
class MetaboxHandle {

	/**
	 * Metabox identifier.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Underlying metabox builder.
	 *
	 * @var MetaboxBuilder
	 */
	protected $builder;

	/**
	 * Registered metabox instance.
	 *
	 * @var MetaboxInstance|null
	 */
	protected $instance = null;

	/**
	 * Whether the metabox has been registered.
	 *
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * Whether an init hook has been scheduled.
	 *
	 * @var bool
	 */
	protected $registration_hooked = false;

	/**
	 * Priority used when scheduling registration.
	 *
	 * @var int
	 */
	protected $register_priority = 20;

	/**
	 * Constructor.
	 *
	 * @param string         $id      Metabox identifier.
	 * @param MetaboxBuilder $builder Builder instance.
	 */
	public function __construct( string $id, MetaboxBuilder $builder ) {
		$this->id      = $id;
		$this->builder = $builder;
	}

	/**
	 * Retrieve the metabox identifier.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Retrieve the underlying builder.
	 *
	 * @return MetaboxBuilder
	 */
	public function builder(): MetaboxBuilder {
		return $this->builder;
	}

	/**
	 * Set the displayed metabox title.
	 *
	 * @param string $title Title text.
	 * @return $this
	 */
	public function title( string $title ): self {
		$this->builder->title( $title );

		return $this;
	}

	/**
	 * Assign a description stored with the metabox config.
	 *
	 * @param string $description Description text.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->builder->config( 'description', $description );

		return $this;
	}

	/**
	 * Set post types (screens) supported by the metabox.
	 *
	 * @param mixed $screens Post type(s) as array, Traversable, or delimited string.
	 * @return $this
	 */
	public function postType( $screens ): self {
		$normalized = $this->normalize_screens( $screens );

		if ( ! empty( $normalized ) ) {
			$this->builder->postType( $normalized );
		}

		return $this;
	}

	/**
	 * Alias of postType().
	 *
	 * @param mixed $screens Post type(s).
	 * @return $this
	 */
	public function screens( $screens ): self {
		return $this->postType( $screens );
	}

	/**
	 * Set the metabox context.
	 *
	 * @param string $context Context ('normal', 'side', 'advanced').
	 * @return $this
	 */
	public function context( string $context ): self {
		$this->builder->context( $context );

		return $this;
	}

	/**
	 * Set the metabox priority.
	 *
	 * @param string $priority Priority ('high', 'low', 'default').
	 * @return $this
	 */
	public function priority( string $priority ): self {
		$this->builder->priority( $priority );

		return $this;
	}

	/**
	 * Convenience for priority('high').
	 *
	 * @return $this
	 */
	public function high(): self {
		$this->builder->high();

		return $this;
	}

	/**
	 * Convenience for priority('low').
	 *
	 * @return $this
	 */
	public function low(): self {
		$this->builder->low();

		return $this;
	}

	/**
	 * Set capability required to edit the metabox.
	 *
	 * @param string $capability Capability string.
	 * @return $this
	 */
	public function capability( string $capability ): self {
		$this->builder->capability( $capability );

		return $this;
	}

	/**
	 * Assign a custom layout identifier.
	 *
	 * @param string $layout Layout identifier.
	 * @return $this
	 */
	public function layout( string $layout ): self {
		$this->builder->layout( $layout );

		return $this;
	}

	/**
	 * Enable the panel layout (tabbed interface).
	 *
	 * @return $this
	 */
	public function panel(): self {
		$this->builder->panel();

		return $this;
	}

	/**
	 * Convenience for context('normal').
	 *
	 * @return $this
	 */
	public function normal(): self {
		$this->builder->normal();

		return $this;
	}

	/**
	 * Convenience for context('side').
	 *
	 * @return $this
	 */
	public function side(): self {
		$this->builder->side();

		return $this;
	}

	/**
	 * Convenience for context('advanced').
	 *
	 * @return $this
	 */
	public function advanced(): self {
		$this->builder->advanced();

		return $this;
	}

	/**
	 * Directly mutate the builder via callback.
	 *
	 * @param callable $callback Callback receiving the builder.
	 * @return $this
	 */
	public function tap( callable $callback ): self {
		$callback( $this->builder );

		return $this;
	}

	/**
	 * Add a field definition.
	 *
	 * @param mixed $field Field definition.
	 * @return $this
	 */
	public function field( $field ): self {
		return $this->fields( $field );
	}

	/**
	 * Add multiple field definitions.
	 *
	 * @param mixed ...$fields Field definitions.
	 * @return $this
	 */
	public function fields( ...$fields ): self {
		if ( 1 === count( $fields ) && is_array( $fields[0] ) && $this->is_list_array( $fields[0] ) ) {
			$fields = $fields[0];
		}

		$prepared = array();

		foreach ( $fields as $field ) {
			$prepared[] = $this->normalize_field( $field );
		}

		if ( ! empty( $prepared ) ) {
			$this->builder->fields( $prepared );
		}

		return $this;
	}

	/**
	 * Attach a fluent section handle to the metabox.
	 *
	 * @param SectionHandle $section Section handle instance.
	 * @return void
	 */
	public function attachSection( SectionHandle $section ): void {
		$section->attachToMetabox( $this );
	}

	/**
	 * Define a section for the panel layout.
	 *
	 * @param string $id          Section identifier.
	 * @param string $title       Section title.
	 * @param string $description Optional description.
	 * @return MetaboxSectionBuilder
	 */
	public function section( string $id, string $title = '', string $description = '' ): MetaboxSectionBuilder {
		return $this->builder->section( $id, $title, $description );
	}

	/**
	 * Register the metabox immediately.
	 *
	 * @return MetaboxInstance
	 */
	public function register(): MetaboxInstance {
		if ( $this->registered && $this->instance instanceof MetaboxInstance ) {
			return $this->instance;
		}

		$this->instance   = $this->builder->register();
		$this->registered = true;

		if ( $this->registration_hooked && function_exists( 'remove_action' ) ) {
			remove_action( 'init', array( $this, 'maybe_register' ), $this->register_priority );
			$this->registration_hooked = false;
		}

		return $this->instance;
	}

	/**
	 * Schedule registration on init (mirrors options behaviour).
	 *
	 * @param int $priority Action priority.
	 * @return $this
	 */
	public function registerOnInit( int $priority = 20 ): self {
		if ( $this->registered ) {
			return $this;
		}

		if ( $this->registration_hooked ) {
			if ( $priority === $this->register_priority || ! function_exists( 'remove_action' ) ) {
				return $this;
			}

			remove_action( 'init', array( $this, 'maybe_register' ), $this->register_priority );
			$this->registration_hooked = false;
		}

		$priority = max( 1, $priority );

		if ( function_exists( 'did_action' ) && did_action( 'init' ) ) {
			if ( function_exists( 'doing_action' ) && doing_action( 'init' ) && function_exists( 'add_action' ) ) {
				$adjusted_priority           = max( $priority, 99 );
				add_action( 'init', array( $this, 'maybe_register' ), $adjusted_priority );
				$this->registration_hooked = true;
				$this->register_priority   = $adjusted_priority;

				return $this;
			}

			$this->register();

			return $this;
		}

		if ( function_exists( 'add_action' ) ) {
			add_action( 'init', array( $this, 'maybe_register' ), $priority );
			$this->registration_hooked = true;
			$this->register_priority   = $priority;

			return $this;
		}

		$this->register();

		return $this;
	}

	/**
	 * Register when the init hook fires.
	 *
	 * @return void
	 */
	public function maybe_register(): void {
		if ( $this->registered ) {
			return;
		}

		$this->register();
	}

	/**
	 * Convenience helper for chaining additional Moo definitions.
	 *
	 * @param string $type Type identifier.
	 * @param mixed  ...$arguments Arguments.
	 * @return mixed
	 */
	public function make( string $type, ...$arguments ) {
		return \WPMoo\Moo::make( $type, ...$arguments );
	}

	/**
	 * Retrieve the registered metabox instance (if any).
	 *
	 * @return MetaboxInstance|null
	 */
	public function instance(): ?MetaboxInstance {
		return $this->instance;
	}

	/**
	 * Normalise screen inputs into an array of strings.
	 *
	 * @param mixed $screens Screens input.
	 * @return array<int, string>
	 */
	protected function normalize_screens( $screens ): array {
		if ( $screens instanceof Traversable ) {
			$screens = iterator_to_array( $screens );
		}

		if ( is_string( $screens ) ) {
			$screens = preg_split( '/[,\s]+/', $screens );
		}

		if ( ! is_array( $screens ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $screens as $screen ) {
			$screen = is_string( $screen ) ? trim( $screen ) : '';

			if ( '' !== $screen ) {
				$normalized[] = $screen;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Determine whether an array is a sequential list.
	 *
	 * @param array<int|string, mixed> $items Input array.
	 * @return bool
	 */
	protected function is_list_array( array $items ): bool {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $items );
		}

		$expected = 0;

		foreach ( $items as $key => $_value ) {
			if ( $key !== $expected ) {
				return false;
			}

			++$expected;
		}

		return true;
	}

	/**
	 * Normalise a field definition accepted by the handle.
	 *
	 * @param mixed $field Raw field definition.
	 * @return array<string, mixed>
	 */
	protected function normalize_field( $field ): array {
		if ( $field instanceof FieldDefinition ) {
			return $field->toArray();
		}

		if ( $field instanceof OptionsFieldBuilder || $field instanceof MetaboxFieldBuilder ) {
			return $field->build();
		}

		if ( is_array( $field ) ) {
			if ( empty( $field['id'] ) || empty( $field['type'] ) ) {
				throw new InvalidArgumentException( 'Field arrays require both "id" and "type" keys.' );
			}

			return $field;
		}

		throw new InvalidArgumentException( 'Unsupported field definition supplied to Moo::metabox().' );
	}
}
