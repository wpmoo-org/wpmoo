<?php
/**
 * Command line entry point for WPMoo.
 *
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Core
 * @since 0.1.0
 */

namespace WPMoo\Core;

/**
 * Provides routing for CLI commands.
 */
class CLI {

	/**
	 * Run the command router.
	 *
	 * @param array<int, mixed> $argv Command line arguments.
	 * @return void
	 */
	public static function run( $argv ) {
		$command = isset( $argv[1] ) ? $argv[1] : 'help';

		switch ( $command ) {
			case 'info':
				self::cmd_info();
				break;
			default:
				self::help();
				break;
		}
	}

	/**
	 * Output generic help text.
	 *
	 * @return void
	 */
	protected static function help() {
		Console::line();
		Console::comment( '🐮  WPMoo CLI' );
		Console::line( 'Usage:' );
		Console::line( '  php bin/moo info        Show framework info' );
		Console::line( '  php bin/moo help        Show this help' );
		Console::line();
	}

	/**
	 * Display framework information.
	 *
	 * @return void
	 */
	protected static function cmd_info() {
		$php = PHP_VERSION;
		$wp  = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : 'n/a (CLI)';

		Console::info( 'WPMoo — WordPress Micro OOP Framework' );
		Console::comment( 'PHP: ' . $php );
		Console::comment( 'WP : ' . $wp );
		Console::line();
	}
}
