<?php
/**
 * WPMoo Samples aggregator.
 */

namespace WPMoo\Samples;

use WPMoo\Moo;
use WPMoo\Samples\Metabox\Simple as MetaboxSimple;
use WPMoo\Samples\Options\Html\HtmlDemo;
use WPMoo\Samples\Options\Inputs\Button;
use WPMoo\Samples\Options\Inputs\Checkbox;
use WPMoo\Samples\Options\Inputs\Input;
use WPMoo\Samples\Options\Inputs\Preview;
use WPMoo\Samples\Options\Inputs\Radio;
use WPMoo\Samples\Options\Inputs\Range;
use WPMoo\Samples\Options\Inputs\Textarea;
use WPMoo\Samples\Options\Inputs\Toggle;
use WPMoo\Samples\Options\Layout\Accordion;
use WPMoo\Samples\Options\Layout\Fieldset;
use WPMoo\Samples\Options\Layout\Tabs;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Entry point for registering all WPMoo sample demos.
 */
final class Samples {
	public const PAGE_ID           = 'wpmoo_samples';
	public const PAGE_INPUTS       = 'wpmoo_samples_inputs';
	public const PAGE_LAYOUTS      = 'wpmoo_samples_layouts';
	public const PAGE_HTML         = 'wpmoo_samples_html';
	public const MENU_SLUG         = 'wpmoo-samples';
	public const MENU_SLUG_INPUTS  = 'wpmoo-samples-inputs';
	public const MENU_SLUG_LAYOUTS = 'wpmoo-samples-layouts';
	public const MENU_SLUG_HTML    = 'wpmoo-samples-html';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'register_pages' ), 5 );
			add_action( 'wpmoo_init', array( self::class, 'register_modules' ), 6 );
		}
	}

	public static function register_pages(): void {
		self::configure_page(
			self::PAGE_ID,
			__( 'WPMoo Samples', 'wpmoo' ),
			self::MENU_SLUG,
			true,
			null
		);

		self::configure_page(
			self::PAGE_INPUTS,
			__( 'Inputs Demo', 'wpmoo' ),
			self::MENU_SLUG_INPUTS,
			true,
			self::MENU_SLUG
		);

		self::configure_page(
			self::PAGE_LAYOUTS,
			__( 'Layouts Demo', 'wpmoo' ),
			self::MENU_SLUG_LAYOUTS,
			true,
			self::MENU_SLUG
		);

		self::configure_page(
			self::PAGE_HTML,
			__( 'HTML Demo', 'wpmoo' ),
			self::MENU_SLUG_HTML,
			false,
			self::MENU_SLUG
		);
	}

	protected static function configure_page( string $id, string $title, string $slug, bool $options, ?string $parent_slug ): void {
		$page = Moo::page( $id )
			->title( $title )
			->menu_slug( $slug )
			->sidebar_nav();

		if ( $options ) {
			$page->fluid()->ajax_save();

			// Ensure at least one section exists to satisfy Options builder.
			Moo::section( "{$id}_placeholder", $title )
				->description( __( 'Samples placeholder section.', 'wpmoo' ) )
				->parent( $id )
				->html(
					static function () {
						echo '<p>' . esc_html__( 'Placeholder content – actual samples load below.', 'wpmoo' ) . '</p>';
					}
				);
		}

		if ( $parent_slug ) {
			$page->parent_slug( $parent_slug );
		}
	}

	public static function register_modules(): void {
		self::register_input_sections();
		self::register_layout_sections();
		self::register_html_sections();
		MetaboxSimple::register();
		if ( function_exists( 'did_action' ) && did_action( 'wpmoo_init' ) ) {
			MetaboxSimple::define();
		}
	}

	protected static function register_input_sections(): void {
		Preview::register( self::PAGE_INPUTS );
		Input::register( self::PAGE_INPUTS );
		Textarea::register( self::PAGE_INPUTS );
		Button::register( self::PAGE_INPUTS );
		Checkbox::register( self::PAGE_INPUTS );
		Radio::register( self::PAGE_INPUTS );
		Toggle::register( self::PAGE_INPUTS );
		Range::register( self::PAGE_INPUTS );
	}

	protected static function register_layout_sections(): void {
		Accordion::register( self::PAGE_LAYOUTS );
		Fieldset::register( self::PAGE_LAYOUTS );
		Tabs::register( self::PAGE_LAYOUTS );
	}

	protected static function register_html_sections(): void {
		HtmlDemo::register( self::PAGE_HTML );
	}
}
