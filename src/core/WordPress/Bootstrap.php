<?php

namespace WPMoo\WordPress;

use WPMoo\App\Container;
use WPMoo\WordPress\Managers\FieldManager;
use WPMoo\WordPress\Managers\PageManager;
use WPMoo\WordPress\Managers\MetaboxManager;
use WPMoo\WordPress\Managers\LayoutManager;

/**
 * Plugin bootstrap handler.
 *
 * @package WPMoo
 * @since 0.1.0
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
		$this->container->singleton( \WPMoo\WordPress\Managers\FieldManager::class );
		$this->container->singleton( \WPMoo\WordPress\Managers\PageManager::class );
		$this->container->singleton( \WPMoo\WordPress\Managers\MetaboxManager::class );
		$this->container->singleton( \WPMoo\WordPress\Managers\LayoutManager::class );

		// Bind aliases
		$this->container->bind( 'field_manager', \WPMoo\WordPress\Managers\FieldManager::class );
		$this->container->bind( 'page_manager', \WPMoo\WordPress\Managers\PageManager::class );
		$this->container->bind( 'metabox_manager', \WPMoo\WordPress\Managers\MetaboxManager::class );
		$this->container->bind( 'layout_manager', \WPMoo\WordPress\Managers\LayoutManager::class );
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
		add_action( 'init', [ $this, 'register_layouts' ], 15 );
		add_action( 'admin_menu', [ $this, 'register_pages' ], 10 );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ], 10 );

		// Load textdomain for the framework if loaded as plugin
		// Commenting out to prevent early loading warnings until we find the source of the problem
		// if ( defined( 'WPMOO_PLUGIN_LOADED' ) && WPMOO_PLUGIN_LOADED ) {
		//  add_action( 'init', [ $this, 'load_textdomain' ], 2 );
		// }

		// Load sample configurations only when loaded as plugin directly (not as dependency)
		$is_directly_loaded = $this->is_directly_loaded();
		if ( $is_directly_loaded ) {
			add_action( 'init', [ $this, 'load_samples' ], 5 );
		}
	}

	/**
	 * Register all field types.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		$this->container->resolve( \WPMoo\WordPress\Managers\FieldManager::class )->register_all();
	}

	/**
	 * Register all admin pages.
	 *
	 * @return void
	 */
	public function register_pages(): void {
		$this->container->resolve( \WPMoo\WordPress\Managers\PageManager::class )->register_all();
	}

	/**
	 * Register all metaboxes.
	 *
	 * @return void
	 */
	public function register_metaboxes(): void {
		$this->container->resolve( \WPMoo\WordPress\Managers\MetaboxManager::class )->register_all();
	}

	/**
	 * Register all layouts.
	 *
	 * @return void
	 */
	public function register_layouts(): void {
		$this->container->resolve( \WPMoo\WordPress\Managers\LayoutManager::class )->register_all();
	}

	/**
	 * Load sample configurations from the samples directory.
	 *
	 * @return void
	 */
	public function load_samples(): void {
		$samples_dir = dirname( dirname( __DIR__ ) ) . '/samples';
		if ( is_dir( $samples_dir ) ) {
			$files = glob( $samples_dir . '/*.php' );
			if ( is_array( $files ) && ! empty( $files ) ) {
				foreach ( $files as $file ) {
					if ( is_readable( $file ) ) {
						// Add error handling to make sure sample loading doesn't break everything
						try {
							require_once $file;
						} catch ( \Exception $e ) {
							error_log( 'WPMoo Sample Loading Error: ' . $e->getMessage() );
						}
					}
				}
			}
		}
	}

	/**
	 * Load textdomain for the WPMoo framework.
	 */
	public function load_textdomain(): void {
		// Get the directory of the main framework file
		$framework_file = WPMOO_FILE;
		$plugin_dir = dirname( $framework_file );

		// Load the textdomain for the framework
		// This is intentionally commented out to prevent early loading errors
		// Should only be loaded via init hook which is handled by the register_hooks method when appropriate
	}

	/**
	 * Get the dependency injection container.
	 *
	 * @return Container DI container instance.
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Check if the framework is loaded directly as a plugin (not as a dependency).
	 *
	 * @return bool True if loaded directly as a plugin, false otherwise.
	 */
	private function is_directly_loaded(): bool {
		// This check verifies if the framework is running as a plugin rather than a dependency
		if ( ! defined( 'WPMOO_PLUGIN_LOADED' ) ) {
			return false;
		}

		return (bool) WPMOO_PLUGIN_LOADED;
	}
}
