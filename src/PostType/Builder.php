<?php
/**
 * Fluent post type builder.
 *
 * @package WPMoo\PostType
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

namespace WPMoo\PostType;

use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fluent builder for register_post_type arguments.
 */
class Builder {
	/**
	 * Post type key.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Arguments passed to register_post_type.
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
	 * Taxonomies to register.
	 *
	 * @var array<int, string>
	 */
	protected $taxonomies = array();

	/**
	 * Rewrite slug.
	 *
	 * @var string|null
	 */
	protected $slug = null;

	/**
	 * Constructor.
	 *
	 * @param string $type Post type slug.
	 */
	public function __construct( string $type ) {
		if ( empty( $type ) ) {
			throw new InvalidArgumentException( 'Post type slug cannot be empty.' );
		}

		$this->type = $type;
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
	 * Mark as public.
	 *
	 * @param bool $public Public visibility.
	 * @return $this
	 */
	public function public( bool $public = true ): self {
		$this->args['public'] = $public;

		return $this;
	}

	/**
	 * Set description.
	 *
	 * @param string $description Description.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->args['description'] = $description;

		return $this;
	}

	/**
	 * Choose supported features.
	 *
	 * @param array<int, string> $features Supported features.
	 * @return $this
	 */
	public function supports( array $features ): self {
		$this->args['supports'] = $features;

		return $this;
	}

	/**
	 * Menu icon slug.
	 *
	 * @param string $icon Menu icon.
	 * @return $this
	 */
	public function menuIcon( string $icon ): self {
		$this->args['menu_icon'] = $icon;

		return $this;
	}

	/**
	 * REST API visibility.
	 *
	 * @param bool $enabled Whether to expose in REST.
	 * @return $this
	 */
	public function showInRest( bool $enabled = true ): self {
		$this->args['show_in_rest'] = $enabled;

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
	 * Add taxonomy support.
	 *
	 * @param string|array<int, string> $taxonomies Taxonomy name(s).
	 * @return $this
	 */
	public function taxonomy( $taxonomies ): self {
		$taxonomies = is_string( $taxonomies ) ? array( $taxonomies ) : $taxonomies;

		foreach ( $taxonomies as $taxonomy ) {
			$this->taxonomies[] = $taxonomy;
		}

		return $this;
	}

	/**
	 * Set menu position.
	 *
	 * @param int $position Menu position.
	 * @return $this
	 */
	public function menuPosition( int $position ): self {
		$this->args['menu_position'] = $position;

		return $this;
	}

	/**
	 * Set hierarchical.
	 *
	 * @param bool $hierarchical Whether hierarchical.
	 * @return $this
	 */
	public function hierarchical( bool $hierarchical = true ): self {
		$this->args['hierarchical'] = $hierarchical;

		return $this;
	}

	/**
	 * Set has archive.
	 *
	 * @param bool|string $archive Archive setting.
	 * @return $this
	 */
	public function hasArchive( $archive = true ): self {
		$this->args['has_archive'] = $archive;

		return $this;
	}

	/**
	 * Show in menu.
	 *
	 * @param bool|string $show Show in menu.
	 * @return $this
	 */
	public function showInMenu( $show = true ): self {
		$this->args['show_in_menu'] = $show;

		return $this;
	}

	/**
	 * Set capability type.
	 *
	 * @param string $capability Capability type.
	 * @return $this
	 */
	public function capabilityType( string $capability ): self {
		$this->args['capability_type'] = $capability;

		return $this;
	}

	/**
	 * Flush rewrite rules.
	 *
	 * @param bool $hard Hard flush.
	 * @return void
	 */
	public function flush( bool $hard = true ): void {
		if ( function_exists( 'flush_rewrite_rules' ) ) {
			flush_rewrite_rules( $hard );
		}
	}

	/**
	 * Generic argument setter.
	 *
	 * @param string $key   Argument key.
	 * @param mixed  $value Value to assign.
	 * @return $this
	 */
	public function arg( string $key, $value ): self {
		$this->args[ $key ] = $value;

		return $this;
	}

	/**
	 * Merge many arguments at once.
	 *
	 * @param array<string, mixed> $args Arguments.
	 * @return $this
	 */
	public function args( array $args ): self {
		$this->args = array_merge( $this->args, $args );

		return $this;
	}

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = $this->normalize_labels();
		$args   = array_merge( array( 'labels' => $labels ), $this->args );

		// Add rewrite slug if set.
		if ( ! is_null( $this->slug ) && ! isset( $args['rewrite'] ) ) {
			$args['rewrite'] = array( 'slug' => $this->slug );
		}

		$post_type  = $this->type;
		$taxonomies = $this->taxonomies;

		$callback = static function () use ( $post_type, $args, $taxonomies ) {
			// Register post type.
			register_post_type( $post_type, $args );

			// Register taxonomies.
			if ( ! empty( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					register_taxonomy_for_object_type( $taxonomy, $post_type );
				}
			}
		};

		if ( did_action( 'init' ) ) {
			$callback();
		} else {
			add_action( 'init', $callback );
		}
	}

	/**
	 * Ensure labels include reasonable defaults.
	 *
	 * @return array<string, string>
	 */
	protected function normalize_labels(): array {
		$defaults = array(
			'singular_name' => ucfirst( $this->type ),
			'name'          => ucfirst( $this->type ) . 's',
		);

		return array_merge( $defaults, $this->labels );
	}
}
