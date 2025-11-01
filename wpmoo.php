<?php
// Back-compat loader: allow including the legacy path when the repository root
// is referenced directly (e.g., old active_plugins entries or custom loaders).
// New plugin entrypoint lives under src/wpmoo.php and the plugin symlink points
// to the src/ directory.

$entry = __DIR__ . '/src/wpmoo.php';
if ( file_exists( $entry ) ) {
    include_once $entry;
}

