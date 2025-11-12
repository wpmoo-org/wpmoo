<?php
namespace WPMoo\Samples\Options\Html;

use WPMoo\Moo;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

final class HtmlDemo {
	public static function register( string $page_id ): void {
		Moo::section( 'html_demo_page', __( 'HTML Demo Page', 'wpmoo' ) )
			->parent( $page_id )
			->html( array( self::class, 'render' ) );
	}

	public static function render( $page = null ): void {
		echo '<div class="wpmoo-html-demo">';
		echo '<h3>' . esc_html__( 'Custom HTML Content', 'wpmoo' ) . '</h3>';
		echo '<p>' . esc_html__( 'This section renders arbitrary markup without Options API bindings.', 'wpmoo' ) . '</p>';
		echo '<div class="notice notice-info"><p>' . esc_html__( 'Use this pattern for dashboards, docs, or onboarding flows.', 'wpmoo' ) . '</p></div>';
		echo '</div>';
	}
}
