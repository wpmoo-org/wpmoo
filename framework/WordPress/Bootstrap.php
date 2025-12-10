<?php

namespace WPMoo\WordPress;

use WPMoo\App\Container;
use WPMoo\WordPress\Managers\FieldManager;
use WPMoo\WordPress\Managers\PageManager;
use WPMoo\WordPress\Managers\MetaboxManager;
use WPMoo\WordPress\Managers\LayoutManager;
use WPMoo\WordPress\Managers\FrameworkManager;

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
	 * Flag to ensure the loader is hooked only once.
	 *
	 * @var bool
	 */
	private static bool $loader_initialized = false;

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
	 * Initializes the framework loader.
	 * This method should be called by each plugin using the framework.
	 */
	public static function initialize( string $plugin_file, string $plugin_slug, string $version ): void {
		FrameworkManager::instance()->register_plugin( $plugin_slug, $version, $plugin_file );

		if ( ! self::$loader_initialized ) {
			add_action( 'plugins_loaded', [ __CLASS__, 'boot_latest_stable' ], 9 );
			self::$loader_initialized = true;
		}
	}

	/**
	 * Boots the latest stable version of the framework.
	 * This method is hooked to 'plugins_loaded'.
	 */
	public static function boot_latest_stable(): void {
		$latest_stable_plugin = FrameworkManager::instance()->get_highest_version_plugin();

		if ( $latest_stable_plugin ) {
			// Now that we have the winner, boot the framework using its data.
			self::instance()->boot( $latest_stable_plugin['path'], $latest_stable_plugin['slug'] );
		}
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
		// Register core services.
		$this->container->singleton( FrameworkManager::class );
		$this->container->singleton( FieldManager::class );
		$this->container->singleton( PageManager::class );
		$this->container->singleton( MetaboxManager::class );
		$this->container->singleton( LayoutManager::class );

		// Bind aliases.
		$this->container->bind( 'framework_manager', FrameworkManager::class );
		$this->container->bind( 'field_manager', FieldManager::class );
		$this->container->bind( 'page_manager', PageManager::class );
		$this->container->bind( 'metabox_manager', MetaboxManager::class );
		$this->container->bind( 'layout_manager', LayoutManager::class );
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

		// Load framework textdomain.
		load_plugin_textdomain(
			'wpmoo',
			false,
			plugin_basename( dirname( __DIR__, 2 ) . '/languages' )
		);

		// Only register the main WordPress hooks once, by the winning instance.
		$this->register_hooks();
		$this->booted = true;

		// Initialize this specific plugin instance.
		$this->init_plugin_instance( $plugin_slug );
	}

	/**
	 * Initialize a specific plugin instance.
	 *
	 * @param string $plugin_slug Unique plugin identifier.
	 * @return void
	 */
	private function init_plugin_instance( string $plugin_slug ): void {
		// Load sample configurations only when loaded as plugin directly (not as dependency).
		if ( $this->is_directly_loaded() && $plugin_slug === 'wpmoo' ) {
			add_action( 'init', [ $this, 'load_samples' ], 5 );
		}
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Register all domain handlers - only once regardless of how many plugins use the framework.
		add_action( 'init', [ $this, 'register_fields' ], 10 );
		add_action( 'init', [ $this, 'register_layouts' ], 15 );
		add_action( 'admin_menu', [ $this, 'register_pages' ], 10 );
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ], 10 );

		// Initialize asset enqueuer for pages.
		\WPMoo\WordPress\AssetEnqueuers\PageAssetEnqueuer::instance();
	}

	/**
	 * Register all field types.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		$this->container->resolve( FieldManager::class )->register_all();
	}

	/**
	 * Register all admin pages.
	 *
	 * @return void
	 */
	public function register_pages(): void {
		$this->container->resolve( PageManager::class )->register_all();
	}

	/**
	 * Register all metaboxes.
	 *
	 * @return void
	 */
	public function register_metaboxes(): void {
		$this->container->resolve( MetaboxManager::class )->register_all();
	}

	/**
	 * Register all layouts.
	 *
	 * @return void
	 */
	public function register_layouts(): void {
		$this->container->resolve( LayoutManager::class )->register_all();
	}

	/**
	 * Load sample configurations from the samples directory.
	 *
	 * @throws \Exception If a sample file cannot be loaded.
	 * @return void
	 */
	public function load_samples(): void {
		if ( ! defined( 'WPMOO_PATH' ) ) {
			return;
		}

		$samples_dir = WPMOO_PATH . '/src/samples';
		if ( is_dir( $samples_dir ) ) {
			$files = glob( $samples_dir . '/*.php' );
			if ( is_array( $files ) && ! empty( $files ) ) {
				foreach ( $files as $file ) {
					if ( is_readable( $file ) ) {
						try {
							require_once $file;
						} catch ( \Exception $e ) {
							throw $e;
						}
					}
				}
			}
		}
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
		if ( ! defined( 'WPMOO_PLUGIN_LOADED' ) ) {
			return false;
		}

		return (bool) WPMOO_PLUGIN_LOADED;
	}
}
