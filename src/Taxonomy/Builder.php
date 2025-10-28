<?php
/**
 * Fluent taxonomy builder.
 *
 * @package WPMoo\Taxonomy
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\Taxonomy;

use InvalidArgumentException;
use WPMoo\Columns\Columns;
use WPMoo\Support\Concerns\TranslatesStrings;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Fluent builder for register_taxonomy arguments.
 */
class Builder {
	use TranslatesStrings;

	/**
	 * Taxonomy key.
	 *
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * Arguments passed to register_taxonomy.
	 *
	 * @var array<string, mixed>
	 */
	protected $args = array();

	/**
	 * Collected labels.
	 *
	 * @var array<string, string>
	 */
	protected $labels = array();

	/**
	 * Object types to attach to.
	 *
	 * @var array<int, string>
	 */
	protected $object_types = array();

	/**
	 * Custom slug for rewrite.
	 *
	 * @var string|null
	 */
	protected $slug = null;

	/**
	 * Columns manager instance.
	 *
	 * @var Columns|null
	 */
	protected $columns_manager = null;

	/**
	 * Constructor.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @throws InvalidArgumentException When slug is empty.
	 */
	public function __construct( string $taxonomy ) {
		if ( empty( $taxonomy ) ) {
			/* phpcs:disable WordPress.Security.EscapeOutput */
			throw new InvalidArgumentException( $this->translate( 'Taxonomy slug cannot be empty.' ) );
			/* phpcs:enable WordPress.Security.EscapeOutput */
		}

		$this->taxonomy = $taxonomy;
	}

	/**
	 * Set singular label.
	 *
	 * @param string $label Singular label.
	 * @return $this
	 */
	public function singular( string $label ): self {
		$this->labels['singular_name'] = $label;

		return $this;
	}

	/**
	 * Set plural label.
	 *
	 * @param string $label Plural label.
	 * @return $this
	 */
	public function plural( string $label ): self {
		$this->labels['name'] = $label;

		return $this;
	}

	/**
	 * Set both singular and plural labels.
	 *
	 * @param string $singular Singular label.
	 * @param string $plural   Plural label.
	 * @return $this
	 */
	public function labels( string $singular, string $plural ): self {
		$this->singular( $singular );
		$this->plural( $plural );

		return $this;
	}

	/**
	 * Set taxonomy description.
	 *
	 * @param string $description Description text.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->args['description'] = $description;

		return $this;
	}

	/**
	 * Set rewrite slug.
	 *
	 * @param string $slug Custom slug.
	 * @return $this
	 */
	public function slug( string $slug ): self {
		$this->slug = $slug;

		return $this;
	}

	/**
	 * Show in admin menu.
	 *
	 * @param bool $enabled Whether to show in menu.
	 * @return $this
	 */
	public function showInMenu( bool $enabled = true ): self {
		$this->args['show_in_menu'] = $enabled;

		return $this;
	}

	/**
	 * Show admin column in post type list.
	 *
	 * @param bool $enabled Whether to show admin column.
	 * @return $this
	 */
	public function showAdminColumn( bool $enabled = true ): self {
		$this->args['show_admin_column'] = $enabled;

		return $this;
	}

	/**
	 * Set public visibility.
	 *
	 * @param bool $public Whether taxonomy is public.
	 * @return $this
	 */
	public function public( bool $public = true ): self {
		$this->args['public'] = $public;

		return $this;
	}

	/**
	 * Set hierarchical structure (like categories).
	 *
	 * @param bool $hierarchical Whether taxonomy is hierarchical.
	 * @return $this
	 */
	public function hierarchical( bool $hierarchical = true ): self {
		$this->args['hierarchical'] = $hierarchical;

		return $this;
	}

	/**
	 * Show in REST API.
	 *
	 * @param bool $enabled Whether to show in REST.
	 * @return $this
	 */
	public function showInRest( bool $enabled = true ): self {
		$this->args['show_in_rest'] = $enabled;

		return $this;
	}

	/**
	 * Set rewrite configuration.
	 *
	 * @param array<string, mixed> $rewrite Rewrite configuration.
	 * @return $this
	 */
	public function rewrite( array $rewrite ): self {
		$this->args['rewrite'] = $rewrite;

		return $this;
	}

	/**
	 * Set a single argument.
	 *
	 * @param string $key   Argument key.
	 * @param mixed  $value Argument value.
	 * @return $this
	 */
	public function arg( string $key, $value ): self {
		$this->args[ $key ] = $value;

		return $this;
	}

	/**
	 * Merge multiple arguments.
	 *
	 * @param array<string, mixed> $args Taxonomy arguments.
	 * @return $this
	 */
	public function args( array $args ): self {
		$this->args = array_merge( $this->args, $args );

		return $this;
	}

	/**
	 * Attach taxonomy to post types.
	 *
	 * @param array<int, string> $object_types Array of post type names.
	 * @return $this
	 */
	public function attachTo( array $object_types ): self {
		$this->object_types = $object_types;

		return $this;
	}

	/**
	 * Chainable method to attach a single post type.
	 *
	 * @param string $object_type Post type name.
	 * @return $this
	 */
	public function postType( string $object_type ): self {
		$this->object_types[] = $object_type;

		return $this;
	}

	/**
	 * Get or create a Columns manager instance for custom admin table columns.
	 *
	 * @return Columns
	 */
	public function columns(): Columns {
		if ( is_null( $this->columns_manager ) ) {
			$this->columns_manager = new Columns();
		}

		return $this->columns_manager;
	}

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = $this->normalize_labels();
		$args   = array_merge( array( 'labels' => $labels ), $this->args );

		// Set rewrite slug if specified.
		if ( ! is_null( $this->slug ) ) {
			$args['rewrite'] = isset( $args['rewrite'] ) && is_array( $args['rewrite'] )
				? array_merge( $args['rewrite'], array( 'slug' => $this->slug ) )
				: array( 'slug' => $this->slug );
		}

		$taxonomy        = $this->taxonomy;
		$object_types    = $this->object_types;
		$has_columns     = ! is_null( $this->columns_manager );
		$columns_manager = $this->columns_manager;

		$callback = function () use ( $taxonomy, $object_types, $args, $has_columns, $columns_manager ) {
			register_taxonomy( $taxonomy, $object_types, $args );

			if ( ! empty( $object_types ) ) {
				foreach ( $object_types as $object_type ) {
					register_taxonomy_for_object_type( $taxonomy, $object_type );
				}
			}

			if ( $has_columns ) {
				$this->register_columns_hooks( $taxonomy, $columns_manager );
			}
		};

		if ( did_action( 'init' ) ) {
			$callback();
		} else {
			add_action( 'init', $callback );
		}
	}

	/**
	 * Register hooks for column management.
	 *
	 * @param string  $taxonomy        Taxonomy name.
	 * @param Columns $columns_manager Column manager instance.
	 * @return void
	 */
	protected function register_columns_hooks( string $taxonomy, Columns $columns_manager ): void {
		// Modify columns.
		add_filter(
			"manage_edit-{$taxonomy}_columns",
			function ( $columns ) use ( $columns_manager ) {
				return $columns_manager->modifyColumns( $columns );
			}
		);

		// Populate custom columns.
		add_filter(
			"manage_{$taxonomy}_custom_column",
			function ( $content, $column, $term_id ) use ( $columns_manager ) {
				$callbacks = $columns_manager->getPopulateCallbacks();
				if ( isset( $callbacks[ $column ] ) ) {
					return call_user_func( $callbacks[ $column ], $content, $column, $term_id );
				}
				return $content;
			},
			10,
			3
		);

		// Set sortable columns.
		$sortable = $columns_manager->getSortable();
		if ( ! empty( $sortable ) ) {
			add_filter(
				"manage_edit-{$taxonomy}_sortable_columns",
				function ( $columns ) use ( $sortable ) {
					return array_merge( $columns, $sortable );
				}
			);

			// Handle sorting.
			add_action(
				'parse_term_query',
				function ( $query ) use ( $taxonomy, $columns_manager ) {
					if ( ! is_admin() || ! in_array( $taxonomy, $query->query_vars['taxonomy'] ?? array(), true ) ) {
						return;
					}

					if ( ! isset( $_GET['orderby'] ) || ! $columns_manager->isSortable( sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) ) ) {
						return;
					}

					$meta = $columns_manager->sortableMeta( sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) );

					if ( is_string( $meta ) ) {
						$meta_key = $meta;
						$orderby  = 'meta_value';
					} else {
						$meta_key = $meta[0];
						$orderby  = 'meta_value_num';
					}

					$query->query_vars['orderby']  = $orderby;
					$query->query_vars['meta_key'] = $meta_key;
				}
			);
		}
	}

	/**
	 * Normalize labels with smart defaults.
	 *
	 * @return array<string, string>
	 */
	protected function normalize_labels(): array {
		$singular = isset( $this->labels['singular_name'] )
			? $this->labels['singular_name']
			: ucwords( str_replace( array( '-', '_' ), ' ', $this->taxonomy ) );

		$plural = isset( $this->labels['name'] )
			? $this->labels['name']
			: $singular . 's';

		$defaults = array(
			'name'                       => $plural,
			'singular_name'              => $singular,
			'menu_name'                  => $plural,
			'all_items'                  => sprintf( $this->translate( 'All %s' ), $plural ),
			'edit_item'                  => sprintf( $this->translate( 'Edit %s' ), $singular ),
			'view_item'                  => sprintf( $this->translate( 'View %s' ), $singular ),
			'update_item'                => sprintf( $this->translate( 'Update %s' ), $singular ),
			'add_new_item'               => sprintf( $this->translate( 'Add New %s' ), $singular ),
			'new_item_name'              => sprintf( $this->translate( 'New %s Name' ), $singular ),
			'parent_item'                => sprintf( $this->translate( 'Parent %s' ), $singular ),
			'parent_item_colon'          => sprintf( $this->translate( 'Parent %s:' ), $singular ),
			'search_items'               => sprintf( $this->translate( 'Search %s' ), $plural ),
			'popular_items'              => sprintf( $this->translate( 'Popular %s' ), $plural ),
			'separate_items_with_commas' => sprintf( $this->translate( 'Separate %s with commas' ), $plural ),
			'add_or_remove_items'        => sprintf( $this->translate( 'Add or remove %s' ), $plural ),
			'choose_from_most_used'      => sprintf( $this->translate( 'Choose from most used %s' ), $plural ),
			'not_found'                  => sprintf( $this->translate( 'No %s found' ), $plural ),
		);

		return array_merge( $defaults, $this->labels );
	}

	/**
	 * Translate strings while remaining compatible with non-WordPress contexts.
	 *
	 * @param string $text Text to translate.
	 * @return string
	 */
}
