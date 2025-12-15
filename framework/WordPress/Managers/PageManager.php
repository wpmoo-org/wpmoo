<?php

namespace WPMoo\WordPress\Managers;

use WPMoo\Page\Builders\PageBuilder;

/**
 * Page menu manager.
 *
 * @package WPMoo\WordPress\Managers
 * @since   0.2.0
 */
class PageManager
{
    /**
     * The framework manager instance.
     *
     * @var FrameworkManager
     */
    private FrameworkManager $framework_manager;

    /**
     * Constructor.
     *
     * @param FrameworkManager $framework_manager The main framework manager.
     */
    public function __construct(FrameworkManager $framework_manager)
    {
        $this->framework_manager = $framework_manager;
    }

    /**
     * Register all pages with WordPress.
     *
     * @throws \Exception If a page registration fails.
     * @return void
     */
    public function register_all(): void
    {
        // Get all pages from the central framework manager.
        $all_pages_by_plugin = $this->framework_manager->get_pages();

        // Process pages by plugin to maintain isolation.
        foreach ( $all_pages_by_plugin as $plugin_slug => $pages ) {
            $this->register_pages_for_plugin($plugin_slug, $pages);
        }
    }

    /**
     * Register pages for a specific plugin.
     *
     * @param  string                                          $plugin_slug The plugin slug.
     * @param  array<string, \WPMoo\Page\Builders\PageBuilder> $pages       The pages to register.
     * @return void
     * @throws \Exception If a page registration fails.
     */
    private function register_pages_for_plugin( string $plugin_slug, array $pages ): void
    {
        foreach ( $pages as $page ) {
            // Make sure page registration doesn't fail the entire process.
            try {
                $this->register_page($plugin_slug, $page);
            } catch ( \Exception $e ) {
                throw $e;
            }
        }
    }

    /**
     * Register a single page with WordPress.
     *
     * @param  string      $plugin_slug The plugin's unique slug.
     * @param  PageBuilder $page        Page builder instance.
     * @return void
     */
    private function register_page( string $plugin_slug, PageBuilder $page ): void
    {
        $self = $this;  // Capture $this context for closure.
        $hook_suffix = '';

        // Create a unique slug prefixed with the plugin's ID to prevent conflicts.
        $unique_slug = $plugin_slug . '_' . $page->get_menu_slug();

        if ($page->get_parent_slug() ) {
            $hook_suffix = add_submenu_page(
                $page->get_parent_slug(),
                $page->get_title(),
                $page->get_title(),
                $page->get_capability(),
                $unique_slug,
                function () use ( $page, $self, $unique_slug, $plugin_slug ) {
                    $self->render_page($page, $unique_slug, $plugin_slug);
                }
            );
        } else {
            $hook_suffix = add_menu_page(
                $page->get_title(),
                $page->get_title(),
                $page->get_capability(),
                $unique_slug,
                function () use ( $page, $self, $unique_slug, $plugin_slug ) {
                    $self->render_page($page, $unique_slug, $plugin_slug);
                },
                $page->get_menu_icon(),
                $page->get_menu_position()
            );
        }

        if ($hook_suffix ) {
            $this->framework_manager->add_page_hook($hook_suffix);
        }
    }

    /**
     * Render the page content.
     *
     * @param  PageBuilder $page        Page builder instance.
     * @param  string      $unique_slug The unique, prefixed slug for the page.
     * @param  string      $plugin_slug The slug of the plugin being rendered.
     * @return void
     */
    private function render_page( PageBuilder $page, string $unique_slug, string $plugin_slug ): void
    {
        // Get ALL layouts from the framework manager to process containers and their items.
        $all_layouts = $this->framework_manager->get_layouts();
        // Filter to get only those layouts that are directly associated with this page AND this plugin.
        $page_layouts = $this->framework_manager->get_layouts_by_parent($page->get_id(), $plugin_slug);

        // DEBUG: Show what page layouts were found
        // Uncomment the following lines for debugging
        /*
        error_log('DEBUG: Page ID: ' . $page->get_id());
        error_log('DEBUG: Plugin Slug: ' . $plugin_slug);
        error_log('DEBUG: Found page layouts count: ' . count($page_layouts));
        foreach ($page_layouts as $id => $layout) {
        error_log('DEBUG: Page layout ID: ' . $id . ', Type: ' . get_class($layout) . ', Parent: ' . ($layout->get_parent() ?? 'none'));
        }
        */

        ?>
        <div class="wrap">
            <h1><?php echo esc_html($page->get_title()); ?></h1>
        <?php if ($page->get_description() ) : ?>
                <p><?php echo esc_html($page->get_description()); ?></p>
        <?php endif; ?>

            <form method="post" action="options.php">
        <?php
        // If there are layouts for this page, render them.
        if (! empty($page_layouts) ) {
            $this->render_layouts($page_layouts); // Pass only the scoped layouts
        } else {
            // Fallback: render standard WordPress settings.
            settings_fields($unique_slug);
            do_settings_sections($unique_slug);
            submit_button();
        }
        ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render layouts for the page.
     *
     * @param  array<string, mixed> $pageLayouts Layout components directly associated with the page.
     * @return void
     */
    private function render_layouts( array $pageLayouts ): void
    {
        $containers    = [];
        $itemsByParent = [];

        // First, categorize all given layouts into containers and their child items.
        foreach ( $pageLayouts as $layout ) {
            $parent_id = $layout->get_parent();

            if ( $layout instanceof \WPMoo\Layout\Component\Container ) {
                $containers[ $layout->get_id() ] = $layout;
            } elseif ( $parent_id ) {
                $itemsByParent[ $parent_id ][ $layout->get_id() ] = $layout;
            }
        }

        // Render each container with its pre-categorized items.
        foreach ( $containers as $containerId => $container ) {
            $items         = $itemsByParent[ $containerId ] ?? [];
            $containerType = $container->get_type();

            switch ( $containerType ) {
                case 'tabs':
                    $this->render_tabs_from_container( $container, $items );
                    break;
                case 'accordion':
                    $this->render_accordion_from_container( $container, $items );
                    break;
                default:
                    // Handle other container types if needed.
                    break;
            }
        }

        // Also render any legacy tabs/accordions that still use the old structure.
        foreach ( $pageLayouts as $layout ) {
            if ( $layout instanceof \WPMoo\Layout\Component\Tabs ) {
                $this->render_tabs( $layout );
            } elseif ( $layout instanceof \WPMoo\Layout\Component\Accordion ) {
                $this->render_accordion( $layout );
            }
        }
    }

    /**
     * Render tabs layout from container and item components.
     *
     * @param  \WPMoo\Layout\Component\Container $container Container component.
     * @param  array                             $items     Item components for this container.
     * @return void
     */
    private function render_tabs_from_container( \WPMoo\Layout\Component\Container $container, array $items ): void
    {
        $orientation = 'horizontal'; // Default orientation, could be stored in Container properties if needed

        $tab_class = 'vertical' === $orientation ? 'wpmoo-tabs-vertical' : 'wpmoo-tabs-horizontal';
        ?>
        <div class="wpmoo-tabs <?php echo esc_attr($tab_class); ?>">
            <div class="wpmoo-tab-nav">
                <ul role="tablist">
        <?php $index = 0; ?>
        <?php foreach ( $items as $item ) : ?>
            <?php if ($item instanceof \WPMoo\Layout\Component\Tab ) : ?>
                    <li role="presentation" class="<?php echo 0 === $index ? 'active' : ''; ?>">
                        <a href="#<?php echo esc_attr($item->get_id()); ?>"
                            role="tab"
                            aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>">
                <?php echo esc_html($item->get_title()); ?>
                        </a>
                    </li>
            <?php endif; ?>
            <?php $index++; ?>
        <?php endforeach; ?>
                </ul>
            </div>

            <div class="wpmoo-tab-content">
        <?php $index = 0; ?>
        <?php foreach ( $items as $item ) : ?>
            <?php if ($item instanceof \WPMoo\Layout\Component\Tab ) : ?>
                    <div id="<?php echo esc_attr($item->get_id()); ?>"
                         role="tabpanel"
                         class="tab-pane <?php echo 0 === $index ? 'active' : ''; ?>">
                <?php
                // Render content for this tab.
                $this->render_content($item->get_content());
                ?>
                    </div>
            <?php endif; ?>
            <?php $index++; ?>
        <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render accordion layout from container and item components.
     *
     * @param  \WPMoo\Layout\Component\Container $container Container component.
     * @param  array                             $items     Item components for this container.
     * @return void
     */
    private function render_accordion_from_container( \WPMoo\Layout\Component\Container $container, array $items ): void
    {
        ?>
        <div class="wpmoo-accordion">
        <?php foreach ( $items as $item ) : ?>
            <?php if ($item instanceof \WPMoo\Layout\Component\Accordion ) : ?>
                <div class="accordion-item">
                    <input type="checkbox" id="<?php echo esc_attr($item->get_id()); ?>_checkbox" hidden>
                    <label class="wpmoo-accordion-label" for="<?php echo esc_attr($item->get_id()); ?>_checkbox"><?php echo esc_html($item->get_title()); ?></label>
                    <div class="wpmoo-accordion-content">
                <?php
                // Render content for this accordion item.
                $this->render_content($item->get_content());
                ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render tabs layout (legacy structure).
     *
     * @param  \WPMoo\Layout\Component\Tabs $tabs Tabs component.
     * @return void
     */
    private function render_tabs( \WPMoo\Layout\Component\Tabs $tabs ): void
    {
        $items = $tabs->get_items();
        $orientation = $tabs->get_orientation();

        $tab_class = 'vertical' === $orientation ? 'wpmoo-tabs-vertical' : 'wpmoo-tabs-horizontal';
        ?>
        <div class="wpmoo-tabs <?php echo esc_attr($tab_class); ?>">
            <div class="wpmoo-tab-nav">
                <ul role="tablist">
        <?php foreach ( $items as $index => $item ) : ?>
                    <li role="presentation" class="<?php echo 0 === $index ? 'active' : ''; ?>">
                        <a href="#<?php echo esc_attr($item['id']); ?>"
                            role="tab"
                            aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>">
            <?php echo esc_html($item['title']); ?>
                        </a>
                    </li>
        <?php endforeach; ?>
                </ul>
            </div>

            <div class="wpmoo-tab-content">
        <?php foreach ( $items as $index => $item ) : ?>
                    <div id="<?php echo esc_attr($item['id']); ?>"
                         role="tabpanel"
                         class="tab-pane <?php echo 0 === $index ? 'active' : ''; ?>">
            <?php
            // Render content for this tab.
            $this->render_content($item['content']);
            ?>
                    </div>
        <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render accordion layout.
     *
     * @param  \WPMoo\Layout\Component\Accordion $accordion Accordion component.
     * @return void
     */
    private function render_accordion( \WPMoo\Layout\Component\Accordion $accordion ): void
    {
        // Placeholder for accordion rendering.
        // This would render the accordion structure similar to tabs.
        echo '<!-- Accordion content to be implemented -->';
    }

    /**
     * Render content (fields or other elements).
     *
     * @param  array<mixed> $content Content to render.
     * @return void
     */
    private function render_content( array $content ): void
    {
        if (empty($content) ) {
            return;
        }

        // Render each item in the content array.
        // This processes fields and other content elements.
        foreach ( $content as $item ) {
            // If item is a field, render it.
            if (is_object($item) && method_exists($item, 'get_id') ) {
                // This is a field that needs to be rendered.
                $field_id = $item->get_id();
                // Determine field type from the class name
                $field_class = get_class($item);
                $field_type = strtolower(pathinfo($field_class, PATHINFO_FILENAME)); // Extract type from class name like 'Input', 'Toggle', etc.

                // As a fallback, we can check the class name directly
                if (strpos($field_class, 'Input') !== false) {
                    $field_type = 'text';
                } elseif (strpos($field_class, 'Textarea') !== false) {
                    $field_type = 'textarea';
                } elseif (strpos($field_class, 'Toggle') !== false) {
                    $field_type = 'toggle';
                } else {
                    $field_type = 'text'; // Default to text
                }

                $field_name = $field_id; // In a real implementation, this would be prefixed with the option group

                // Get field properties
                $label = method_exists($item, 'get_label') ? $item->get_label() : '';
                $placeholder = method_exists($item, 'get_placeholder') ? $item->get_placeholder() : '';

                // Output the field based on its type
                echo '<div class="field-wrapper" data-field-id="' . esc_attr($field_id) . '">';
                if (! empty($label) ) {
                    echo '<label for="' . esc_attr($field_id) . '">' . esc_html($label) . '</label>';
                }

                // Render different field types
                switch ( $field_type ) {
                case 'toggle':
                    $checked = get_option($field_id, false) ? 'checked' : '';
                    echo '<div class="form-group"><input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" ' . $checked . ' class="wpmoo-toggle form-switch"></div>';
                    break;
                case 'textarea':
                    $value = get_option($field_id, '');
                    echo '<div class="form-group"><textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" placeholder="' . esc_attr($placeholder) . '" class="wpmoo-textarea input-group">' . esc_textarea($value) . '</textarea></div>';
                    break;
                default: // Includes text, number, etc.
                    $value = get_option($field_id, '');
                    echo '<div class="form-group"><input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" class="wpmoo-input input-group"></div>';
                }
                echo '</div>';
            } elseif (is_string($item) ) {
                // This could be other content.
                echo wp_kses_post($item);
            }
        }
    }
}
