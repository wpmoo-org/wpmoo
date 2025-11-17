<?php

namespace WPMoo\App\WordPress;

use WPMoo\App\Container;
use WPMoo\Field\WordPress\Registrar as FieldRegistrar;
use WPMoo\Page\WordPress\Registrar as PageRegistrar;
use WPMoo\Metabox\WordPress\Registrar as MetaboxRegistrar;

/**
 * Plugin bootstrap handler.
 *
 * @package WPMoo
 * @since 1.0.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class Bootstrap {
	/**
	 * Singleton instance.
	 *
	 * @var ?self
	 */
	private static ?self $instance = null;

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Boot status flag.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Get singleton instance.
	 *
	 * @return self Bootstrap instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->container = new Container();
		$this->register_bindings();
	}

	/**
	 * Register service bindings in the container.
	 *
	 * @return void
	 */
	private function register_bindings(): void {
		// Register core services
		$this->container->singleton( FieldRegistrar::class );
		$this->container->singleton( PageRegistrar::class );
		$this->container->singleton( MetaboxRegistrar::class );

		// Bind aliases
		$this->container->bind( 'field_registrar', FieldRegistrar::class );
		$this->container->bind( 'page_registrar', PageRegistrar::class );
		$this->container->bind( 'metabox_registrar', MetaboxRegistrar::class );
	}

	/**
	 * Boot the application.
	 *
	 * @param string $plugin_file Full path to the main plugin file.
	 * @param string $plugin_slug Unique plugin identifier.
	 * @return void
	 */
	public function boot( string $plugin_file, string $plugin_slug ): void {
		if ( $this->booted ) {
			return;
		}

		$this->register_hooks();
		$this->booted = true;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Register all domain handlers
		add_action( 'init', [ $this, 'register_fields' ], 10 );
		add_action( 'admin_menu', [ $this, 'register_pages' ], 10 );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ], 10 );
	}

	/**
	 * Register all field types.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		// Temporary - Field registration will be implemented when the Registrar class is ready
		// $this->container->resolve( FieldRegistrar::class )->register_all();
	}

	/**
	 * Register all admin pages.
	 *
	 * @return void
	 */
	public function register_pages(): void {
		// Temporary - Page registration will be implemented when the Registrar class is ready
		// $this->container->resolve( PageRegistrar::class )->register_all();
	}

	/**
	 * Register all metaboxes.
	 *
	 * @return void
	 */
	public function register_metaboxes(): void {
		// Temporary - Metabox registration will be implemented when the Registrar class is ready
		// $this->container->resolve( MetaboxRegistrar::class )->register_all();
	}

	/**
	 * Get the dependency injection container.
	 *
	 * @return Container DI container instance.
	 */
	public function container(): Container {
		return $this->container;
	}
}
