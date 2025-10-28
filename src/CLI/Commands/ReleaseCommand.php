<?php

namespace WPMoo\CLI\Commands;

use WPMoo\CLI\Contracts\CommandInterface;
use WPMoo\CLI\Console;

/**
 * Placeholder for future release automation (tagging/changelog).
 */
class ReleaseCommand implements CommandInterface {
	public function handle( array $args = array() ) {
		Console::warning( 'Release command is not yet implemented.' );
		Console::comment( 'Consider using release-please in CI for automated releases.' );
		return 0;
	}
}
