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

		$post_type = $this->type;

		$callback = static function () use ( $post_type, $args ) {
			register_post_type( $post_type, $args );
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
