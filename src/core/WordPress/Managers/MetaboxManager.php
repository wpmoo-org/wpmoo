<?php

namespace WPMoo\WordPress\Managers;

/**
 * Metabox manager.
 *
 * @package WPMoo\Metabox
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class MetaboxManager {
	/**
	 * Register all metaboxes with WordPress.
	 *
	 * @return void
	 */
	public function register_all(): void {
		// This is where we would register all metaboxes with WordPress
		// using the add_meta_box function based on registered metabox instances
	}

	/**
	 * Add a metabox to be registered.
	 *
	 * @param object $metabox Metabox instance.
	 * @return void
	 */
	public function add_metabox( $metabox ): void {
		// Store metabox for later registration
	}
}
