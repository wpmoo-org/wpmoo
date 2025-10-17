<?php
/**
 * Terminal output helpers for CLI commands.
 *
 * WPMoo — WordPress Micro Object-Oriented Framework.
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Core
 * @since 0.1.0
 */

namespace WPMoo\Core;

/**
 * Provides colorized output for terminal usage.
 */
class Console {

	/**
	 * Output an informational message.
	 *
	 * @param string $message Message text.
	 * @return void
	 */
	public static function info( $message ) {
		echo "\033[32m{$message}\033[0m" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Color codes are intentional.
	}

	/**
	 * Output an error message.
	 *
	 * @param string $message Message text.
	 * @return void
	 */
	public static function error( $message ) {
		echo "\033[31m{$message}\033[0m" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Color codes are intentional.
	}

	/**
	 * Output a warning message.
	 *
	 * @param string $message Message text.
	 * @return void
	 */
	public static function warning( $message ) {
		echo "\033[33m{$message}\033[0m" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Color codes are intentional.
	}

	/**
	 * Output a comment message.
	 *
	 * @param string $message Message text.
	 * @return void
	 */
	public static function comment( $message ) {
		echo "\033[36m{$message}\033[0m" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Color codes are intentional.
	}

	/**
	 * Output a plain line.
	 *
	 * @param string $message Message text.
	 * @return void
	 */
	public static function line( $message = '' ) {
		echo $message . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI utility method.
	}
}
