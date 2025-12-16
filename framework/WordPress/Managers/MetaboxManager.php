<?php

namespace WPMoo\WordPress\Managers;

/**
 * Metabox manager.
 *
 * @package WPMoo\WordPress\Managers
 * @since 0.1.0
 */
class MetaboxManager {
    /**
     * The framework manager instance.
     * @var FrameworkManager
     */
    private FrameworkManager $framework_manager;

    /**
     * Constructor.
     * @param FrameworkManager $framework_manager The main framework manager.
     */
    public function __construct(FrameworkManager $framework_manager) {
        $this->framework_manager = $framework_manager;
    }

	/**
	 * Register all metaboxes with WordPress.
	 *
	 * @return void
	 */
	public function register_all(): void {
		// This is where we would register all metaboxes with WordPress.
		// Example of how it would work:
		// $all_metaboxes = $this->framework_manager->get_metaboxes();
		// foreach ($all_metaboxes as $metabox) {
		//     add_meta_box(...);
		// }
	}

	/**
	 * Add a metabox to be registered.
	 * @deprecated This method is for backward compatibility. Use App::metabox() instead.
	 *
	 * @param object $metabox Metabox instance.
	 * @return void
	 */
	public function add_metabox( $metabox ): void {
		// This logic would be handled by the App instance now.
	}
}
