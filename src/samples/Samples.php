<?php
/**
 * WPMoo Samples — aggregator/registrar for sample demos.
 *
 * @package WPMoo\Samples
 */

namespace WPMoo\Samples;

use WPMoo\Moo;
use WPMoo\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Central registrar that wires up all sample demos in one place.
 */
final class Samples {
	/**
	 * Root page identifier for all Samples sections.
	 */
	public const PAGE_ID = 'wpmoo_samples';

	/**
	 * Root page menu slug.
	 */
	public const MENU_SLUG = 'wpmoo-samples';
	/**
	 * Register all sample modules.
	 *
	 * Intended to be called once from the framework loader in dev/admin.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Ensure the root Samples page exists before children define sections.
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'create_root' ), 5 );
		}
		// Defer to each sample class; they hook into `wpmoo_init` for definition.
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Input' ) ) {
			\WPMoo\Samples\Fields\Input::register();
		}
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Button' ) ) {
			\WPMoo\Samples\Fields\Button::register();
		}
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Textarea' ) ) {
			\WPMoo\Samples\Fields\Textarea::register();
		}
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Select' ) ) {
			\WPMoo\Samples\Fields\Select::register();
		}
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Checkbox' ) ) {
			\WPMoo\Samples\Fields\Checkbox::register();
		}
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Radio' ) ) {
			\WPMoo\Samples\Fields\Radio::register();
		}
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Toggle' ) ) {
			\WPMoo\Samples\Fields\Toggle::register();
		}
		if ( class_exists( '\\WPMoo\\Samples\\Fields\\Range' ) ) {
			\WPMoo\Samples\Fields\Range::register();
		}
		if ( class_exists( '\\WPMoo\\Samples\\Metabox\\Simple' ) ) {
			\WPMoo\Samples\Metabox\Simple::register();
		}
		// Optional: a tiny built-in layout demo.
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'add_layout_demo' ), 15 );
		}
	}

	/**
	 * Create the root Samples page container once.
	 */
	public static function create_root(): void {
		Moo::page( self::PAGE_ID )
			->title( __( 'WPMoo Samples', 'wpmoo' ) )
			->menuSlug( self::MENU_SLUG )
			->sticky_header()
			->ajax_save();
	}

	/**
	 * Add a minimal layout/UX demo section under the root page.
	 */
	public static function add_layout_demo(): void {
		Moo::section( 'layout_sticky', __( 'Layout', 'wpmoo' ), __( 'Sticky header is enabled on this page. Scroll to see it in action.', 'wpmoo' ) )
			->parent( self::PAGE_ID )
			->fields(
				Field::textarea( 'layout_notes' )
					->label( __( 'Notes', 'wpmoo' ) )
					->attributes( array( 'placeholder' => __( 'Add some notes…', 'wpmoo' ) ) )
					->description( __( 'This is a demo field. Saving uses the current page settings.', 'wpmoo' ) )
			);
	}
}
