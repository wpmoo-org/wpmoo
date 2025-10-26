<?php

namespace WPMoo\CLI\Contracts;

/**
 * Minimal contract for CLI commands.
 */
interface CommandInterface {
    /**
     * Handle the command.
     *
     * @param array<int, mixed> $args Command arguments (argv slice).
     * @return int Process exit code (0 on success).
     */
    public function handle(array $args = array());
}

