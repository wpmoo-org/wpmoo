<?php
/**
 * Fluent wrapper around the options page builder.
 *
 * @package WPMoo\Moo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Moo;

use InvalidArgumentException;
use WPMoo\Moo\SectionHandle;
use WPMoo\Page\Builder as OptionsBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provides a fluent interface for configuring option pages via the Moo facade.
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
		$this->builder->page_title( $title )->menu_title( $title );

		return $this;
	}

	/**
	 * Set the menu title (without changing the page title).
	 *
	 * @param string $title Menu title text.
	 * @return $this
	 */
	public function menu_title( string $title ): self {
		$this->builder->menu_title( $title );

		return $this;
	}

	/**
	 * Override the menu slug.
	 *
	 * @param string $slug Menu slug.
	 * @return $this
	 */
	public function menu_slug( string $slug ): self {
		$this->builder->menu_slug( $slug );

		return $this;
	}

	/**
	 * Append CSS classes to the page container.
	 *
	 * @param string $class Class name(s).
	 * @return $this
	 */
	public function css_class( string $class ): self { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->builder->css_class( $class );
		return $this;
	}

	/**
	 * Enable/disable full-width container.
	 *
	 * @param bool $enabled Whether fluid layout is enabled.
	 * @return $this
	 */
	public function fluid( bool $enabled = true ): self {
		$this->builder->fluid( $enabled );
		return $this;
	}

	/**
	 * Enable or disable sidebar navigation layout.
	 *
	 * @param bool $enabled Whether sidebar nav is enabled.
	 * @return $this
	 */
	public function sidebar_nav( bool $enabled = true ): self {
		if ( method_exists( $this->builder, 'sidebar_nav' ) ) {
			$this->builder->sidebar_nav( $enabled );
		} else {
			$this->builder->config( 'sidebar_nav', $enabled );
		}

		return $this;
	}

	/**
	 * Specify the parent slug (for sub-menus).
	 *
	 * @param string $parent Parent slug.
	 * @return $this
	 */
	public function parent( string $parent ): self {
		$this->builder->parent_slug( $parent );

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
	 * Enable or disable sticky header for this page.
	 *
	 * @param bool $enabled Whether sticky header is enabled.
	 * @return $this
	 */
	public function sticky_header( bool $enabled = true ): self { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->builder->config( 'sticky_header', $enabled );
		return $this;
	}

	/**
	 * Enable or disable AJAX save for this page.
	 *
	 * @param bool $enabled Whether AJAX save is enabled.
	 * @return $this
	 */
	public function ajax_save( bool $enabled = true ): self { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->builder->config( 'ajax_save', $enabled );
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
		return \WPMoo\Moo::section( $id, $title, $description )->parent( $this->id );
	}

	/**
	 * Convenience helper for chaining additional Moo facade calls.
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
