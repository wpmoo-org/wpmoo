<?php
/**
 * Terminal output helpers for CLI commands.
 *
 * @package WPMoo\Core
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 * Output a stylized title/banner message.
	 *
	 * @param string $message Message text.
	 * @return void
	 */
	public static function banner( $message ) {
		echo "\033[35;1m{$message}\033[0m" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional escape codes.
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
