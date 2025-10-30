<?php
/**
 * Shared fluent field builder used across components (Options/Metabox).
 *
 * @package WPMoo\Fields
 * @since 0.4.4
 */

namespace WPMoo\Fields;

use WPMoo\Support\Concerns\HasColumns;

if ( ! defined( 'ABSPATH' ) ) {
    wp_die();
}

/**
 * Component-agnostic field builder.
 *
 * Provides a consistent fluent API that Options and Metabox builders can extend.
 */
class FieldBuilder {
    use HasColumns;

    /**
     * Field configuration.
     *
     * @var array<string, mixed>
     */
    protected $config = array();

    /**
     * Constructor.
     *
     * @param string $id   Field ID.
     * @param string $type Field type.
     */
    public function __construct( string $id, string $type ) {
        $this->config = array(
            'id'   => $id,
            'type' => $type,
        );
    }

    /**
     * Set field label.
     *
     * @param string $label Label.
     * @return $this
     */
    public function label( string $label ): self {
        $this->config['label'] = $label;
        return $this;
    }

    /**
     * Set field description.
     *
     * @param string $description Description.
     * @return $this
     */
    public function description( string $description ): self {
        $this->config['description'] = $description;
        return $this;
    }

    /**
     * Set default value.
     *
     * @param mixed $default Default value.
     * @return $this
     */
    public function default( $default ): self {
        $this->config['default'] = $default;
        return $this;
    }

    /**
     * Merge additional HTML attributes (Options) or args (Metabox).
     *
     * @param array<string, mixed> $attributes Attributes to merge.
     * @return $this
     */
    public function attributes( array $attributes ): self {
        if ( ! isset( $this->config['attributes'] ) ) {
            $this->config['attributes'] = array();
        }
        $this->config['attributes'] = array_merge( $this->config['attributes'], $attributes );

        // Maintain backwards-compatible alias used by Metabox builder.
        if ( ! isset( $this->config['args'] ) ) {
            $this->config['args'] = array();
        }
        $this->config['args'] = array_merge( $this->config['args'], $attributes );

        return $this;
    }

    /**
     * Back-compat setter for Metabox builder signature.
     *
     * @param array<string, mixed> $args Arguments.
     * @return $this
     */
    public function args( array $args ): self { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
        return $this->attributes( $args );
    }

    /**
     * Set placeholder.
     *
     * @param string $placeholder Placeholder text.
     * @return $this
     */
    public function placeholder( string $placeholder ): self {
        if ( ! isset( $this->config['attributes'] ) ) {
            $this->config['attributes'] = array();
        }
        $this->config['attributes']['placeholder'] = $placeholder;

        // Mirror for args if consumed by a metabox renderer.
        if ( ! isset( $this->config['args'] ) ) {
            $this->config['args'] = array();
        }
        $this->config['args']['placeholder'] = $placeholder;

        return $this;
    }

    /**
     * Set options for select/radio fields.
     *
     * @param array<string, string> $options Options array.
     * @return $this
     */
    public function options( array $options ): self {
        $this->config['options'] = $options;
        return $this;
    }

    /**
     * Generic config setter.
     *
     * @param string $key   Config key.
     * @param mixed  $value Config value.
     * @return $this
     */
    public function set( string $key, $value ): self {
        $this->config[ $key ] = $value;
        return $this;
    }

    /**
     * Markup displayed before the field control.
     *
     * @param string $markup HTML markup.
     * @return $this
     */
    public function before( string $markup ): self {
        $this->config['before'] = $markup;
        return $this;
    }

    /**
     * Markup displayed after the field control.
     *
     * @param string $markup HTML markup.
     * @return $this
     */
    public function after( string $markup ): self {
        $this->config['after'] = $markup;
        return $this;
    }

    /**
     * Define layout configuration (Options grid helpers).
     *
     * @param array<string, mixed> $layout Layout settings.
     * @return $this
     */
    public function layout( array $layout ): self {
        if ( ! isset( $this->config['layout'] ) ) {
            $this->config['layout'] = array();
        }

        if ( isset( $layout['size'] ) && ! isset( $layout['columns'] ) ) {
            $layout['columns'] = array(
                'default' => $this->clampColumnSpan( $layout['size'] ),
            );
        }

        $this->config['layout'] = array_merge( $this->config['layout'], $layout );

        if ( isset( $this->config['layout']['columns']['default'] ) ) {
            $span = $this->clampColumnSpan( $this->config['layout']['columns']['default'] );
            if ( null !== $span ) {
                $this->config['width'] = (int) round( ( $span / 12 ) * 100 );
            }
        } elseif ( isset( $this->config['layout']['size'] ) ) {
            $span = $this->clampColumnSpan( $this->config['layout']['size'] );
            if ( null !== $span ) {
                $this->config['width'] = (int) round( ( $span / 12 ) * 100 );
            }
        }

        return $this;
    }

    /**
     * Set explicit width percentage (0-100).
     *
     * @param int $percentage Width percentage.
     * @return $this
     */
    public function width( int $percentage ): self {
        $percentage            = max( 0, min( 100, $percentage ) );
        $this->config['width'] = $percentage;
        return $this;
    }

    /**
     * Set grid column span(s).
     *
     * @param mixed ...$columns Column definitions (int, string, array).
     * @return $this
     */
    public function size( ...$columns ): self {
        $parsed = $this->parseColumnSpans( $columns );
        $this->layout(
            array(
                'size'    => $parsed['default'],
                'columns' => $parsed,
            )
        );
        $width = (int) round( ( $parsed['default'] / 12 ) * 100 );
        return $this->width( $width );
    }

    /**
     * Alias for size().
     *
     * @param mixed ...$columns Column definitions.
     * @return $this
     */
    public function columns( ...$columns ): self {
        return $this->size( ...$columns );
    }

    /**
     * Set preferred gutter size for grid-based controls.
     *
     * @param string $gutter Gutter keyword (sm, md, lg, xl, none).
     * @return $this
     */
    public function gutter( string $gutter ): self {
        return $this->layout( array( 'gutter' => $gutter ) );
    }

    /**
     * Define nested fields (used by composite controls).
     *
     * @param array<int, mixed> $fields Field definitions.
     * @return $this
     */
    public function fields( array $fields ): self {
        return $this->set( 'fields', $fields );
    }

    /**
     * Build the field configuration.
     *
     * @return array<string, mixed>
     */
    public function build(): array {
        return $this->config;
    }
}

