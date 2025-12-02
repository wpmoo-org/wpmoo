<?php

namespace WPMoo\WordPress\AssetEnqueuers;

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
	 * Enqueue WPMoo assets.
	 */
	public function enqueue_assets(): void {
		// Enqueue main WPMoo styles.
		wp_enqueue_style(
			'wpmoo-styles',
			WPMOO_URL . 'assets/css/wpmoo.css',
			[],
			WPMOO_VERSION
		);

		// Enqueue main WPMoo script.
		wp_enqueue_script(
			'wpmoo-script',
			WPMOO_URL . 'assets/js/wpmoo.js',
			[ 'jquery' ], // Assuming jQuery as a common dependency
			WPMOO_VERSION,
			true // Load in footer
		);
	}
}
