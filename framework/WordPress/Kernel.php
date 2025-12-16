<?php

namespace WPMoo\WordPress;

use WPMoo\Core;
use WPMoo\WordPress\Managers\FrameworkManager;
use WPMoo\WordPress\Managers\PageManager;
use WPMoo\WordPress\Compatibility\VersionCompatibilityChecker;

/**
 * WPMoo WordPress Kernel.
 *
 * This class is the bridge between the decoupled Core and the WordPress environment.
 * It is responsible for registering all necessary WordPress hooks to make the
 * framework function.
 *
 * @package WPMoo\WordPress
 * @since 0.1.0
 */
final class Kernel {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Boot status flag.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Get singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		// Services are now registered in WPMoo\Core.
	}

	/**
	 * Main boot method. Registers all WordPress hooks.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->register_hooks();
		$this->booted = true;
	}

	/**
	 * Register WordPress hooks.
	 */
	private function register_hooks(): void {
		$container = Core::instance()->get_container();

		add_action(
			'init',
			function () use ( $container ) {
				$container->resolve( \WPMoo\WordPress\Managers\FieldManager::class )->register_all();
				$container->resolve( \WPMoo\WordPress\Managers\LayoutManager::class )->register_all();
			},
			15
		);

		// Register settings groups on admin_init.
		add_action( 'admin_init', array( $this, 'register_settings_groups' ) );

		// Register the renderer registry in the container
		$container->singleton( \WPMoo\WordPress\Renderers\RendererRegistry::class );

		// Register PageManager and MetaboxManager hooks on wpmoo_loaded
		// This ensures pages are registered after all plugins have declared them.
		add_action( 'wpmoo_loaded', array( $this, 'register_admin_page_related_hooks' ), 20 );

		// Resolve the PageAssetEnqueuer from the container. Since it's a singleton,
		// this will create it on the first call and retrieve it on subsequent calls.
		// Its constructor registers the necessary 'admin_enqueue_scripts' hook.
		$container->resolve( \WPMoo\WordPress\AssetEnqueuers\PageAssetEnqueuer::class );
	}

	/**
	 * Registers all unique settings groups with WordPress.
	 * This is necessary to whitelist the options so they can be saved.
	 */
	public function register_settings_groups(): void {
		$container = Core::instance()->get_container();
		$frameworkManager = $container->resolve( FrameworkManager::class );
		$all_pages_by_plugin = $frameworkManager->get_pages();

		foreach ( $all_pages_by_plugin as $plugin_slug => $pages ) {
			foreach ( $pages as $page ) {
				$unique_slug = PageManager::get_unique_slug( $plugin_slug, $page->get_menu_slug() );
				register_setting( $unique_slug, $unique_slug );
			}
		}
	}

	/**
	 * Registers WordPress admin menu-related hooks.
	 * This method is hooked to `wpmoo_loaded` to ensure pages are registered after all plugins have declared them.
	 */
	public function register_admin_page_related_hooks(): void {
		$container = Core::instance()->get_container();

		// Resolve managers here to ensure they are the same singletons.
		$pageManager = $container->resolve( PageManager::class );

		$metaboxManager = $container->resolve( \WPMoo\WordPress\Managers\MetaboxManager::class );

		add_action(
			'admin_menu',
			function () use ( $pageManager ) {
				$pageManager->register_all();
			}
		);

		add_action(
			'add_meta_boxes',
			function () use ( $metaboxManager ) {
				$metaboxManager->register_all();
			}
		);

		// Log version compatibility information after all plugins have registered
		add_action( 'wpmoo_loaded', array( $this, 'log_version_compatibility' ), 30 );
	}

	/**
	 * Log version compatibility information for all registered plugins.
	 *
	 * @return void
	 */
	public function log_version_compatibility(): void {
		$container = Core::instance()->get_container();
		$frameworkManager = $container->resolve( FrameworkManager::class );

		$all_plugins = $frameworkManager->get_all_registered_plugins();
		$incompatible_plugins = $frameworkManager->get_incompatible_plugins();

		// Log all plugins and their compatibility status
		foreach ( $all_plugins as $slug => $plugin ) {
			if ( isset( $plugin['compatibility'] ) ) {
				$status = $plugin['compatibility']['compatible'] ? 'COMPATIBLE' : 'INCOMPATIBLE';
				error_log( "WPMoo: Plugin {$slug} (v{$plugin['version']}) - Framework v" . WPMOO_VERSION . " - Status: {$status} - " . $plugin['compatibility']['message'] );
			}
		}

		// If there are incompatible plugins, log them specifically
		if ( ! empty( $incompatible_plugins ) ) {
			$incompatible_list = array_keys( $incompatible_plugins );
			error_log( 'WPMoo: Found ' . count( $incompatible_plugins ) . ' plugin(s) with version compatibility issues: ' . implode( ', ', $incompatible_list ) );
		}
	}
}
