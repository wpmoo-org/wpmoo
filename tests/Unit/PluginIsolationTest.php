<?php
/**
 * Plugin Isolation Unit Tests
 *
 * Tests for the WPMoo plugin isolation system.
 *
 * @package WPMoo\Tests\Unit
 * @since 0.1.0
 */

namespace WPMoo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPMoo\Core;
use WPMoo\WordPress\Managers\FrameworkManager as WPMooFrameworkManager;

/**
 * Class PluginIsolationTest
 *
 * Tests the plugin isolation functionality of the WPMoo framework.
 */
class PluginIsolationTest extends TestCase {

	/**
	 * Test that components from different plugins are properly isolated.
	 */
	public function test_plugin_isolation(): void {
		// Create FrameworkManager instance for testing.
		$framework_manager = new WPMooFrameworkManager();

		// Create components for first plugin.
		$page1 = new \WPMoo\Page\Builders\PageBuilder( 'test_page', 'Test Page' );
		$page1->capability( 'manage_options' )
			->menu_slug( 'plugin-one-test' );

		$field1 = new \WPMoo\Field\Type\Input( 'test_field' );
		$field1->label( 'Test Field' );

		// Register components for first plugin.
		$framework_manager->add_page( $page1, 'plugin-one' );
		$framework_manager->add_field( $field1, 'plugin-one' );

		// Create components for second plugin with same IDs.
		$page2 = new \WPMoo\Page\Builders\PageBuilder( 'test_page', 'Test Page' );
		$page2->capability( 'manage_options' )
			->menu_slug( 'plugin-two-test' );

		$field2 = new \WPMoo\Field\Type\Input( 'test_field' );
		$field2->label( 'Test Field' );

		// Register components for second plugin.
		$framework_manager->add_page( $page2, 'plugin-two' );
		$framework_manager->add_field( $field2, 'plugin-two' );

		// Use the framework manager directly since we created it.
		$registry = $framework_manager;

		// Get components for each plugin.
		$plugin_one_pages = $registry->get_pages_by_plugin( 'plugin-one' );
		$plugin_two_pages = $registry->get_pages_by_plugin( 'plugin-two' );

		$plugin_one_fields = $registry->get_fields_by_plugin( 'plugin-one' );
		$plugin_two_fields = $registry->get_fields_by_plugin( 'plugin-two' );

		// Verify that each plugin has its own components.
		$this->assertCount( 1, $plugin_one_pages );
		$this->assertCount( 1, $plugin_two_pages );
		$this->assertCount( 1, $plugin_one_fields );
		$this->assertCount( 1, $plugin_two_fields );

		// Verify that components are not mixed between plugins.
		$this->assertArrayHasKey( 'test_page', $plugin_one_pages );
		$this->assertArrayHasKey( 'test_page', $plugin_two_pages );
		$this->assertArrayHasKey( 'test_field', $plugin_one_fields );
		$this->assertArrayHasKey( 'test_field', $plugin_two_fields );
	}
}
