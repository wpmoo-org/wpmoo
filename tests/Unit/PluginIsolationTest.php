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
use WPMoo\Moo;
use WPMoo\WordPress\Managers\FrameworkManager;

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
		// Create components for first plugin
		$page1 = Moo::page( 'test_page', 'Test Page', 'plugin-one' )
			->capability( 'manage_options' )
			->menu_slug( 'plugin-one-test' );

		$field1 = Moo::field( 'input', 'test_field', 'plugin-one' )
			->label( 'Test Field' );

		// Create components for second plugin with same IDs
		$page2 = Moo::page( 'test_page', 'Test Page', 'plugin-two' )
			->capability( 'manage_options' )
			->menu_slug( 'plugin-two-test' );

		$field2 = Moo::field( 'input', 'test_field', 'plugin-two' )
			->label( 'Test Field' );

		// Verify that these are properly isolated in the registry
		$registry = FrameworkManager::instance();

		// Get components for each plugin
		$plugin_one_pages = $registry->get_pages_by_plugin( 'plugin-one' );
		$plugin_two_pages = $registry->get_pages_by_plugin( 'plugin-two' );

		$plugin_one_fields = $registry->get_fields_by_plugin( 'plugin-one' );
		$plugin_two_fields = $registry->get_fields_by_plugin( 'plugin-two' );

		// Verify that each plugin has its own components
		$this->assertCount( 1, $plugin_one_pages );
		$this->assertCount( 1, $plugin_two_pages );
		$this->assertCount( 1, $plugin_one_fields );
		$this->assertCount( 1, $plugin_two_fields );

		// Verify that components are not mixed between plugins
		$this->assertArrayHasKey( 'test_page', $plugin_one_pages );
		$this->assertArrayHasKey( 'test_page', $plugin_two_pages );
		$this->assertArrayHasKey( 'test_field', $plugin_one_fields );
		$this->assertArrayHasKey( 'test_field', $plugin_two_fields );
	}
}
