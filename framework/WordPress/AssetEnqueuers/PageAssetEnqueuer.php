<?php

namespace WPMoo\WordPress\AssetEnqueuers;

use WPMoo\WordPress\Managers\FrameworkManager;

/**
 * Enqueues PicoCSS and JS assets.
 *
 * @package WPMoo\WordPress
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class PageAssetEnqueuer {

	/**
	 * The single instance of the class.
	 *
	 * @var PageAssetEnqueuer|null
	 */
	private static ?PageAssetEnqueuer $instance = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @return PageAssetEnqueuer
	 */
	public static function instance(): PageAssetEnqueuer {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue WPMoo assets only on WPMoo admin pages.
	 *
	 * @param string $hook_suffix The hook suffix of the current admin page.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$wpm_pages = FrameworkManager::instance()->get_all_page_hooks();

		if ( ! in_array( $hook_suffix, $wpm_pages, true ) ) {
			return;
		}

		// Dynamically compute the WPMoo URL
		$wpmoo_url = plugin_dir_url(dirname(dirname(__DIR__)) . '/wpmoo.php');

		// Use a fallback version in case constants are not defined
		$wpmoo_version = defined('WPMOO_VERSION') ? WPMOO_VERSION : '0.1.0';

		// Enqueue main WPMoo styles.
		wp_enqueue_style(
			'wpm-main-styles',
			$wpmoo_url . 'assets/css/wpmoo.css',
			[],
			$wpmoo_version
		);

		// Enqueue main WPMoo script.
		wp_enqueue_script(
			'wpm-main-script',
			$wpmoo_url . 'assets/js/wpmoo.js',
			[ 'jquery' ], // Assuming jQuery as a common dependency
			$wpmoo_version,
			true // Load in footer
		);
	}
}
