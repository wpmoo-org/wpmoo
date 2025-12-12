<?php

namespace WPMoo\WordPress\Managers;

use WPMoo\Page\Builders\PageBuilder;

/**
 * Page menu manager.
 *
 * @package WPMoo\Page
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class PageManager {

	/**
	 * Register all pages with WordPress.
	 *
	 * @throws \Exception If a page registration fails.
	 * @return void
	 */
	public function register_all(): void {
		// Get the registry instance to retrieve all pages.
		$registry = \WPMoo\WordPress\Managers\FrameworkManager::instance();
		$all_pages_by_plugin = $registry->get_pages();

		// Process pages by plugin to maintain isolation.
		foreach ( $all_pages_by_plugin as $plugin_slug => $pages ) {
			$this->register_pages_for_plugin( $plugin_slug, $pages );
		}
	}

	/**
	 * Register pages for a specific plugin.
	 *
	 * @param string                                          $plugin_slug The plugin slug.
	 * @param array<string, \WPMoo\Page\Builders\PageBuilder> $pages The pages to register.
	 * @return void
	 * @throws \Exception If a page registration fails.
	 */
	private function register_pages_for_plugin( string $plugin_slug, array $pages ): void {
		foreach ( $pages as $page ) {
			// Make sure page registration doesn't fail the entire process.
			try {
				$this->register_page( $page );
			} catch ( \Exception $e ) {
				throw $e;
			}
		}
	}

	/**
	 * Register a single page with WordPress.
	 *
	 * @param PageBuilder $page Page builder instance.
	 * @return void
	 */
	private function register_page( PageBuilder $page ): void {
		$self = $this;  // Capture $this context for closure.
		$hook_suffix = '';

		if ( $page->get_parent_slug() ) {
			$hook_suffix = add_submenu_page(
				$page->get_parent_slug(),
				$page->get_title(),
				$page->get_title(),
				$page->get_capability(),
				$page->get_menu_slug(),
				function () use ( $page, $self ) {
					$self->render_page( $page );
				}
			);
		} else {
			$hook_suffix = add_menu_page(
				$page->get_title(),
				$page->get_title(),
				$page->get_capability(),
				$page->get_menu_slug(),
				function () use ( $page, $self ) {
					$self->render_page( $page );
				},
				$page->get_menu_icon(),
				$page->get_menu_position()
			);
		}

		if ( $hook_suffix ) {
			FrameworkManager::instance()->add_page_hook( $hook_suffix );
		}
	}

	/**
	 * Render the page content.
	 *
	 * @param PageBuilder $page Page builder instance.
	 * @return void
	 */
	private function render_page( PageBuilder $page ): void {
		$registry = \WPMoo\WordPress\Managers\FrameworkManager::instance();

		// Get layouts associated with this page.
		$page_layouts = $registry->get_layouts_by_parent( $page->get_id() );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( $page->get_title() ); ?></h1>
			<?php if ( $page->get_description() ) : ?>
				<p><?php echo esc_html( $page->get_description() ); ?></p>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				// If there are layouts for this page, render them.
				if ( ! empty( $page_layouts ) ) {
					$this->render_layouts( $page_layouts );
				} else {
					// Fallback: render standard WordPress settings.
					settings_fields( $page->get_menu_slug() );
					do_settings_sections( $page->get_menu_slug() );
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
	 * @param array<string, \WPMoo\Layout\Component\Tabs|\WPMoo\Layout\Component\Accordion> $layouts Layout components to render.
	 * @return void
	 */
	private function render_layouts( array $layouts ): void {
		foreach ( $layouts as $layout ) {
			if ( $layout instanceof \WPMoo\Layout\Component\Tabs ) {
				$this->render_tabs( $layout );
			} elseif ( $layout instanceof \WPMoo\Layout\Component\Accordion ) {
				$this->render_accordion( $layout );
			}
		}
	}

	/**
	 * Render tabs layout.
	 *
	 * @param \WPMoo\Layout\Component\Tabs $tabs Tabs component.
	 * @return void
	 */
	private function render_tabs( \WPMoo\Layout\Component\Tabs $tabs ): void {
		$items = $tabs->get_items();
		$orientation = $tabs->get_orientation();

		$tab_class = 'vertical' === $orientation ? 'wpmoo-tabs-vertical' : 'wpmoo-tabs-horizontal';
		?>
		<div class="wpmoo-tabs <?php echo esc_attr( $tab_class ); ?>">
			<div class="wpmoo-tab-nav">
				<ul role="tablist">
				<?php foreach ( $items as $index => $item ) : ?>
					<li role="presentation" class="<?php echo 0 === $index ? 'active' : ''; ?>">
						<a href="#<?php echo esc_attr( $item['id'] ); ?>"
							role="tab"
							aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>">
							<?php echo esc_html( $item['title'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
				</ul>
			</div>

			<div class="wpmoo-tab-content">
				<?php foreach ( $items as $index => $item ) : ?>
					<div id="<?php echo esc_attr( $item['id'] ); ?>"
						 role="tabpanel"
						 class="tab-pane <?php echo 0 === $index ? 'active' : ''; ?>">
						<?php
						// Render content for this tab.
						$this->render_content( $item['content'] );
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
	 * @param \WPMoo\Layout\Component\Accordion $accordion Accordion component.
	 * @return void
	 */
	private function render_accordion( \WPMoo\Layout\Component\Accordion $accordion ): void {
		// Placeholder for accordion rendering.
		// This would render the accordion structure similar to tabs.
		echo '<!-- Accordion content to be implemented -->';
	}

	/**
	 * Render content (fields or other elements).
	 *
	 * @param array<mixed> $content Content to render.
	 * @return void
	 */
	private function render_content( array $content ): void {
		if ( empty( $content ) ) {
			return;
		}

		// For now, just render each item in the content array.
		// In the future, this would process fields, nested layouts, etc.
		foreach ( $content as $item ) {
			// If item is a field, render it.
			if ( is_object( $item ) && method_exists( $item, 'get_id' ) ) {
				// This is a field that needs to be rendered.
				// For now, we'll just show a placeholder.
				echo '<div class="field-placeholder" data-field-id="' . esc_attr( $item->get_id() ) . '">';
				if ( method_exists( $item, 'get_label' ) ) {
					echo '<label>' . esc_html( $item->get_label() ) . '</label>';
				}
				echo '<div class="field-content">Field: ' . esc_html( $item->get_id() ) . ' (to be implemented)</div>';
				echo '</div>';
			} elseif ( is_string( $item ) ) {
				// This could be other content.
				echo wp_kses_post( $item );
			}
		}
	}
}
