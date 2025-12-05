<?php

namespace WPMoo\Metabox\Interfaces;

/**
 * Metabox contract.
 *
 * Defines the contract for Metabox functionality.
 *
 * @package WPMoo\Metabox
 * @since 0.1.0
 * @link  https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link  https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
interface MetaboxInterface
{
    /**
     * Set the ID of the metabox.
     *
     * @param string $id
     * @return self
     */
    public function id(string $id): self;

    /**
     * Set the title of the metabox.
     *
     * @param string $title
     * @return self
     */
    public function title(string $title): self;

    /**
     * Set the screen(s) on which to show the metabox.
     *
     * @param string|string[] $screen
     * @return self
     */
    public function screen($screen): self;

    /**
     * Set the context within the screen where the metabox should display.
     *
     * @param string $context
     * @return self
     */
    public function context(string $context): self;

    /**
     * Set the priority within the context where the metabox should display.
     *
     * @param string $priority
     * @return self
     */
    public function priority(string $priority): self;

    /**
     * Add content (fields or other components) to the metabox.
     *
     * @param array<mixed> $content
     * @return self
     */
    public function content(array $content): self;

    /**
     * Get the ID of the metabox.
     *
     * @return string
     */
    public function get_id(): string;

    /**
     * Get the title of the metabox.
     *
     * @return string
     */
    public function get_title(): string;

    /**
     * Get the screen(s) of the metabox.
     *
     * @return string|string[]
     */
    public function get_screen();

    /**
     * Get the context of the metabox.
     *
     * @return string
     */
    public function get_context(): string;

    /**
     * Get the priority of the metabox.
     *
     * @return string
     */
    public function get_priority(): string;

    /**
     * Get the content (fields or other components) of the metabox.
     *
     * @return array<mixed>
     */
    public function get_content(): array;
}
