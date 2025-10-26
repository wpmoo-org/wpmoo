<?php

namespace WPMoo\CLI\Commands;

use WPMoo\CLI\Contracts\CommandInterface;
use WPMoo\CLI\Support\Base;
use WPMoo\CLI\Console;

class UpdateCommand extends Base implements CommandInterface {
    public function handle(array $args = array()) {
        $options = self::parse_options($args);

        Console::line();
        Console::comment('Running WPMoo maintenance tasks…');

        $pot_path = self::refresh_translations($options);

        if ($pot_path) {
            Console::info('Translations refreshed at ' . self::relative_path($pot_path));
        }

        Console::line();
        return 0;
    }
}

