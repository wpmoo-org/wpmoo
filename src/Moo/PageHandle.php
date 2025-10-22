<?php
/**
 * Fluent wrapper around the options page builder.
 *
 * @package WPMoo\Moo
 */

namespace WPMoo\Moo;

use InvalidArgumentException;
use WPMoo\Moo\SectionHandle;
use WPMoo\Options\Builder as OptionsBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides a fluent interface for configuring option pages via Moo::make().
 */
class PageHandle {

	/**
	 * Page identifier (option key).
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Underlying options builder.
	 *
	 * @var OptionsBuilder
	 */
	protected $builder;

	/**
	 * Constructor.
	 *
	 * @param string         $id      Page identifier.
	 * @param OptionsBuilder $builder Options builder instance.
	 */
	public function __construct( string $id, OptionsBuilder $builder ) {
		$this->id      = $id;
		$this->builder = $builder;
	}

	/**
	 * Retrieve the builder instance.
	 *
	 * @return OptionsBuilder
	 */
	public function builder(): OptionsBuilder {
		return $this->builder;
	}

	/**
	 * Retrieve the registered identifier.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Set the displayed page title.
	 *
	 * @param string $title Title text.
	 * @return $this
	 */
	public function title( string $title ): self {
		$this->builder->pageTitle( $title )->menuTitle( $title );

		return $this;
	}

	/**
	 * Set the menu title (without changing the page title).
	 *
	 * @param string $title Menu title text.
	 * @return $this
	 */
	public function menuTitle( string $title ): self {
		$this->builder->menuTitle( $title );

		return $this;
	}

	/**
	 * Override the menu slug.
	 *
	 * @param string $slug Menu slug.
	 * @return $this
	 */
	public function menuSlug( string $slug ): self {
		$this->builder->menuSlug( $slug );

		return $this;
	}

	/**
	 * Specify the parent slug (for sub-menus).
	 *
	 * @param string $parent Parent slug.
	 * @return $this
	 */
	public function parent( string $parent ): self {
		$this->builder->parentSlug( $parent );

		return $this;
	}

	/**
	 * Set required capability.
	 *
	 * @param string $capability Capability string.
	 * @return $this
	 */
	public function capability( string $capability ): self {
		$this->builder->capability( $capability );

		return $this;
	}

	/**
	 * Configure the menu icon.
	 *
	 * @param string $icon Dashicon class or custom URL.
	 * @return $this
	 */
	public function icon( string $icon ): self {
		$this->builder->icon( $icon );

		return $this;
	}

	/**
	 * Position the menu entry.
	 *
	 * @param int $position Menu position.
	 * @return $this
	 */
	public function position( int $position ): self {
		$this->builder->position( $position );

		return $this;
	}

	/**
	 * Provide a closure to configure the page builder directly.
	 *
	 * @param callable $callback Callback receiving the underlying builder.
	 * @return $this
	 */
	public function tap( callable $callback ): self {
		$callback( $this->builder );

		return $this;
	}

	/**
	 * Create and attach a section to this page.
	 *
	 * @param string $id          Section identifier.
	 * @param string $title       Section title.
	 * @param string $description Optional description.
	 * @return SectionHandle
	 */
	public function section( string $id, string $title = '', string $description = '' ): SectionHandle {
		return \WPMoo\Moo::make( 'section', $id, $title, $description )->parent( $this->id );
	}

	/**
	 * Convenience helper for chaining additional Moo::make() calls.
	 *
	 * @param string $type Type identifier.
	 * @param mixed  ...$arguments Additional arguments.
	 * @return mixed
	 */
	public function make( string $type, ...$arguments ) {
		return \WPMoo\Moo::make( $type, ...$arguments );
	}

	/**
	 * Register immediately (mostly useful in procedural contexts).
	 *
	 * @return void
	 */
	public function register(): void {
		$this->builder->register();
	}

	/**
	 * Attach a pending section handle to this page.
	 *
	 * @param SectionHandle $section Section instance.
	 * @return void
	 */
	public function attachSection( SectionHandle $section ): void {
		$section->attach( $this );
	}
}
