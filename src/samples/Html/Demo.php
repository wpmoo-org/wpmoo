<?php
/**
 * Samples — HTML-only page demo.
 *
 * @package WPMoo\Samples
 */

namespace WPMoo\Samples\Html;

use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

final class Demo {
	public const PAGE_ID = 'wpmoo_html_demo';
	public const MENU_SLUG = 'wpmoo-html-demo';

	public static function register(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wpmoo_init', array( self::class, 'page' ), 5 );
		}
	}

	public static function page(): void {
		Moo::page( self::PAGE_ID )
			->title( __( 'HTML Demo Page', 'wpmoo' ) )
			->menu_slug( self::MENU_SLUG )
			->render_callback( array( self::class, 'render' ) );

		// Register a section placeholder so the page has at least one section.
		Moo::section( 'html_demo_intro', __( 'Overview', 'wpmoo' ) )
			->description( __( 'Pure HTML sample section (no Options API).', 'wpmoo' ) )
			->parent( self::PAGE_ID );
	}

	public static function render(): void {
		echo '<div class="wrap wpmoo-html-demo">';
		echo '<h1>' . esc_html__( 'Custom HTML Content', 'wpmoo' ) . '</h1>';
		echo '<p>' . esc_html__( 'This page renders arbitrary markup via Moo::page without engaging the Options API.', 'wpmoo' ) . '</p>';
		echo '<div class="notice notice-info"><p>' . esc_html__( 'Use this pattern for dashboards, onboarding, or documentation pages.', 'wpmoo' ) . '</p></div>';
		echo '<p><strong>' . esc_html__( 'Tips', 'wpmoo' ) . '</strong></p>';
		echo '<ul>';
		echo '<li>' . esc_html__( 'Call Section::options() inside your sections when you need settings.', 'wpmoo' ) . '</li>';
		echo '<li>' . esc_html__( 'Skip options() to keep the page purely informational.', 'wpmoo' ) . '</li>';
		echo '</ul>';
		echo '</div>';
	}
}
