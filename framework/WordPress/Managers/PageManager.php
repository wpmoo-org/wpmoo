<?php

namespace WPMoo\WordPress\Managers;

use WPMoo\Page\Builders\PageBuilder;
use WPMoo\Core;

/**
 * Page menu manager.
 *
 * @package WPMoo\WordPress\Managers
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
class PageManager {
	/**
	 * The framework manager instance.
	 *
	 * @var FrameworkManager
	 */
	private FrameworkManager $framework_manager;

	/**
	 * Field manager for field rendering.
	 *
	 * @var FieldManager
	 */
	private FieldManager $field_manager;

	/**
	 * Constructor.
	 *
	 * @param FrameworkManager $framework_manager The main framework manager.
	 * @param FieldManager     $field_manager     The field manager.
	 */
	public function __construct( FrameworkManager $framework_manager, FieldManager $field_manager ) {
		$this->framework_manager = $framework_manager;
		$this->field_manager = $field_manager;
	}

	/**
	 * Register all pages with WordPress.
	 *
	 * @throws \Exception If a page registration fails.
	 * @return void
	 */
	public function register_all(): void {
		// Get all pages from the central framework manager.
		$all_pages_by_plugin = $this->framework_manager->get_pages();

		// Process pages by plugin to maintain isolation.
		foreach ( $all_pages_by_plugin as $plugin_slug => $pages ) {
			$this->register_pages_for_plugin( $plugin_slug, $pages );
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
	private function register_pages_for_plugin( string $plugin_slug, array $pages ): void {
		foreach ( $pages as $page ) {
			// Make sure page registration doesn't fail the entire process.
			try {
				$this->register_page( $plugin_slug, $page );
			} catch ( \Exception $e ) {
				throw $e;
			}
		}
	}

	/**
	 * Generates a unique, prefixed slug for a page to prevent conflicts.
	 *
	 * @param  string $plugin_slug The unique slug of the plugin.
	 * @param  string $page_slug   The original slug of the page.
	 * @return string The conflict-safe slug.
	 */
	public static function get_unique_slug( string $plugin_slug, string $page_slug ): string {
		return $plugin_slug . '_' . $page_slug;
	}

	/**
	 * Register a single page with WordPress.
	 *
	 * @param  string      $plugin_slug The plugin's unique slug.
	 * @param  PageBuilder $page        Page builder instance.
	 * @return void
	 */
	private function register_page( string $plugin_slug, PageBuilder $page ): void {
		$self = $this;  // Capture $this context for closure.
		$hook_suffix = '';

		// Create a unique slug prefixed with the plugin's ID to prevent conflicts.
		$unique_slug = self::get_unique_slug( $plugin_slug, $page->get_menu_slug() );

		if ( $page->get_parent_slug() ) {
			$hook_suffix = add_submenu_page(
				$page->get_parent_slug(),
				$page->get_title(),
				$page->get_title(),
				$page->get_capability(),
				$unique_slug,
				function () use ( $page, $self, $unique_slug, $plugin_slug ) {
					$self->render_page( $page, $unique_slug, $plugin_slug );
				}
			);
		} else {
			$hook_suffix = add_menu_page(
				$page->get_title(),
				$page->get_title(),
				$page->get_capability(),
				$unique_slug,
				function () use ( $page, $self, $unique_slug, $plugin_slug ) {
					$self->render_page( $page, $unique_slug, $plugin_slug );
				},
				$page->get_menu_icon(),
				$page->get_menu_position()
			);
		}

		if ( $hook_suffix ) {
			$this->framework_manager->add_page_hook( $hook_suffix );
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
	private function render_page( PageBuilder $page, string $unique_slug, string $plugin_slug ): void {
		// Get all layouts for the current plugin to build the page structure.
		$plugin_layouts = $this->framework_manager->get_layouts( $plugin_slug );

		?>
		<div class="wpmoo wrap" data-theme="light">
			<h1><?php echo esc_html( $page->get_title() ); ?></h1>
		<?php if ( $page->get_description() ) : ?>
				<p><?php echo esc_html( $page->get_description() ); ?></p>
		<?php endif; ?>

			<form method="post" action="options.php" class="pico-settings-form">
		<?php
		// This function prints out all hidden setting fields.
		settings_fields( $unique_slug );

		// If there are layouts for this plugin, render them.
		if ( ! empty( $plugin_layouts ) ) {
			echo '<div class="container-fluid">'; // PicoCSS container class.
			$this->render_layouts( $plugin_layouts, $page->get_id(), $unique_slug );
			echo '</div>';
		} else {
			// Fallback for pages without layouts.
			echo '<div class="container">'; // PicoCSS container class.
			do_settings_sections( $unique_slug );
			echo '</div>';
		}

		echo '<div class="grid">'; // PicoCSS grid class for form buttons.
		submit_button();
		echo '</div>';
		?>
			</form>

			<!-- ### DEBUG: Show saved options ### -->
			<hr>
			<h2><?php esc_html_e( 'Saved Data', 'wpmoo' ); ?></h2>
			<pre><?php echo esc_html( wp_json_encode( get_option( $unique_slug ), JSON_PRETTY_PRINT ) ); ?></pre>
			<!-- ### END DEBUG ### -->

		</div>
		<?php
	}

	/**
	 * Render layouts for the page.
	 *
	 * @param  array<string, mixed> $plugin_layouts All layout components for the current plugin.
	 * @param  string               $page_id        The ID of the current page, used as the top-level parent.
	 * @param  string               $unique_slug    The unique slug for the page, used as the option group name.
	 * @return void
	 */
	private function render_layouts( array $plugin_layouts, string $page_id, string $unique_slug ): void {
		$containers    = array();
		$items_by_parent = array();

		// First, find the top-level containers for the current page.
		foreach ( $plugin_layouts as $layout ) {
			if ( $layout->get_parent() === $page_id && $layout instanceof \WPMoo\Layout\Component\Container ) {
				$containers[ $layout->get_id() ] = $layout;
			}
		}

		// Now, find all the items that belong to those containers.
		foreach ( $plugin_layouts as $layout ) {
			$parent_id = $layout->get_parent();
			if ( $parent_id && isset( $containers[ $parent_id ] ) ) {
				$items_by_parent[ $parent_id ][ $layout->get_id() ] = $layout;
			}
		}

		// Render each container with its pre-categorized items.
		foreach ( $containers as $container_id => $container ) {
			$items         = $items_by_parent[ $container_id ] ?? array();
			$container_type = $container->get_type();

			switch ( $container_type ) {
				case 'tabs':
					$this->render_tabs_from_container( $container, $items, $unique_slug );
					break;
				case 'accordion':
					$this->render_accordion_from_container( $container, $items, $unique_slug );
					break;
				case 'fieldset':
					$this->render_fieldset_from_container( $container, $items, $unique_slug );
					break;
				default:
					// Handle other container types if needed.
					break;
			}
		}
	}

	/**
	 * Render tabs layout from container and item components.
	 *
	 * @param  \WPMoo\Layout\Component\Container $container   Container component.
	 * @param  array<mixed>                      $items       Item components for this container.
	 * @param  string                            $unique_slug The unique slug for the page.
	 * @return void
	 */
	private function render_tabs_from_container( \WPMoo\Layout\Component\Container $container, array $items, string $unique_slug ): void {
		$orientation = $container->get_orientation();

		$tab_class = 'vertical' === $orientation ? 'wpmoo-tabs-vertical' : 'wpmoo-tabs-horizontal';
		?>
		<div class="wpmoo-tabs <?php echo esc_attr( $tab_class ); ?>">
			<div class="wpmoo-tab-nav">
				<ul role="tablist">
		<?php $index = 0; ?>
		<?php foreach ( $items as $item ) : ?>
			<?php if ( $item instanceof \WPMoo\Layout\Component\Tab ) : ?>
					<li role="presentation" class="<?php echo 0 === $index ? 'active' : ''; ?>">
						<a href="#<?php echo esc_attr( $item->get_id() ); ?>"
							role="tab"
							aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>">
				<?php echo esc_html( $item->get_title() ); ?>
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
			<?php if ( $item instanceof \WPMoo\Layout\Component\Tab ) : ?>
					<div id="<?php echo esc_attr( $item->get_id() ); ?>"
						 role="tabpanel"
						 class="tab-pane <?php echo 0 === $index ? 'active' : ''; ?>">
				<?php
				// Render content for this tab.
				$this->render_content( $item->get_content(), $unique_slug );
				?>
					</div>
			<?php endif; ?>
			<?php $index++; ?>
		<?php endforeach; ?>
			</div>
		<?php
	}

	/**
	 * Render accordion layout from container and item components.
	 *
	 * @param  \WPMoo\Layout\Component\Container $container   Container component.
	 * @param  array<mixed>                      $items       Item components for this container.
	 * @param  string                            $unique_slug The unique slug for the page.
	 * @return void
	 */
	private function render_accordion_from_container( \WPMoo\Layout\Component\Container $container, array $items, string $unique_slug ): void {
		?>
		<div class="wpmoo-accordion">
		<?php foreach ( $items as $item ) : ?>
			<?php if ( $item instanceof \WPMoo\Layout\Component\Accordion ) : ?>
				<div class="accordion-item">
					<input type="checkbox" id="<?php echo esc_attr( $item->get_id() ); ?>_checkbox" hidden>
					<label class="wpmoo-accordion-label" for="<?php echo esc_attr( $item->get_id() ); ?>_checkbox"><?php echo esc_html( $item->get_title() ); ?></label>
					<div class="wpmoo-accordion-content">
				<?php
				// Render content for this accordion item.
				$this->render_content( $item->get_content(), $unique_slug );
				?>
					</div>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render fieldset layout from container and item components.
	 *
	 * @param  \WPMoo\Layout\Component\Container $container   Container component.
	 * @param  array<mixed>                      $items       Item components for this container.
	 * @param  string                            $unique_slug The unique slug for the page.
	 * @return void
	 */
	private function render_fieldset_from_container( \WPMoo\Layout\Component\Container $container, array $items, string $unique_slug ): void {
		?>
		<fieldset class="wpmoo-fieldset">
			<legend class="wpmoo-fieldset-legend"><?php echo esc_html( $container->get_title() ); ?></legend>
			<div class="wpmoo-fieldset-content">
		<?php foreach ( $items as $item ) : ?>
			<?php if ( $item instanceof \WPMoo\Layout\Component\Fieldset ) : ?>
				<?php
				// Render content for this fieldset item.
				$this->render_content( $item->get_content(), $unique_slug );
				?>
			<?php endif; ?>
		<?php endforeach; ?>
			</div>
		</fieldset>
		<?php
	}



	/**
	 * Render content (fields or other elements).
	 *
	 * @param  array<mixed> $content     Content to render.
	 * @param  string       $unique_slug The unique slug for the page.
	 * @return void
	 */
	private function render_content( array $content, string $unique_slug ): void {
		if ( empty( $content ) ) {
			return;
		}

		// Get the entire array of options for this page at once.
		$option_values = get_option( $unique_slug );

		// Render each item in the content array.
		// This processes fields and other content elements.
		foreach ( $content as $item ) {
			// If item is a field, render it using the appropriate renderer.
			if ( is_object( $item ) && method_exists( $item, 'get_id' ) ) {
				// This is a field that needs to be rendered.
				$field_id = $item->get_id();

				// Get the saved value from the options array.
				$value = $option_values[ $field_id ] ?? '';

				// Render the field using FieldManager.
				echo $this->field_manager->render_field( $item, $unique_slug, $value );
			} elseif ( is_string( $item ) ) {
				// This could be other content.
				echo wp_kses_post( $item );
			}
		}
	}
}
