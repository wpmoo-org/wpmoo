<?php
/**
 * Fluent taxonomy builder.
 *
 * @package WPMoo\Taxonomy
 * @since 0.2.0
 */

namespace WPMoo\Taxonomy;

use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fluent builder for register_taxonomy arguments.
 */
class Builder {
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
	 * Constructor.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function __construct( string $taxonomy ) {
		if ( empty( $taxonomy ) ) {
			throw new InvalidArgumentException( 'Taxonomy slug cannot be empty.' );
		}

		$this->taxonomy = $taxonomy;
	}

	public function singular( string $label ): self {
		$this->labels['singular_name'] = $label;

		return $this;
	}

	public function plural( string $label ): self {
		$this->labels['name'] = $label;

		return $this;
	}

	public function description( string $description ): self {
		$this->args['description'] = $description;

		return $this;
	}

	public function hierarchical( bool $hierarchical = true ): self {
		$this->args['hierarchical'] = $hierarchical;

		return $this;
	}

	public function public( bool $public = true ): self {
		$this->args['public'] = $public;

		return $this;
	}

	public function showInRest( bool $enabled = true ): self {
		$this->args['show_in_rest'] = $enabled;

		return $this;
	}

	public function rewrite( array $rewrite ): self {
		$this->args['rewrite'] = $rewrite;

		return $this;
	}

	public function arg( string $key, $value ): self {
		$this->args[ $key ] = $value;

		return $this;
	}

	public function args( array $args ): self {
		$this->args = array_merge( $this->args, $args );

		return $this;
	}

	public function attachTo( array $object_types ): self {
		$this->object_types = $object_types;

		return $this;
	}

	public function register(): void {
		$labels = $this->normalize_labels();
		$args   = array_merge( array( 'labels' => $labels ), $this->args );

		$taxonomy     = $this->taxonomy;
		$object_types = $this->object_types;

		$callback = static function () use ( $taxonomy, $object_types, $args ) {
			register_taxonomy( $taxonomy, $object_types, $args );

			if ( ! empty( $object_types ) ) {
				foreach ( $object_types as $object_type ) {
					register_taxonomy_for_object_type( $taxonomy, $object_type );
				}
			}
		};

		if ( did_action( 'init' ) ) {
			$callback();
		} else {
			add_action( 'init', $callback );
		}
	}

	protected function normalize_labels(): array {
		$defaults = array(
			'singular_name' => ucfirst( $this->taxonomy ),
			'name'          => ucfirst( $this->taxonomy ) . 's',
		);

		return array_merge( $defaults, $this->labels );
	}
}
