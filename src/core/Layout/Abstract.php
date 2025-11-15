<?php
/**
 * Abstract layout implementation used across WPMoo components.
 *
 * @package WPMoo\Layout
 * @since 0.2.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Layout;

use WPMoo\Layout\LayoutInterface;
use WPMoo\Support\Concerns\EscapesOutput;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provides shared functionality for layout components.
 */
abstract class AbstractLayout implements LayoutInterface {
	use EscapesOutput;

	/**
	 * Layout identifier.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Component slug (tabs, accordion, etc.).
	 *
	 * @var string
	 */
	protected $component = '';

	/**
	 * Visible label/title.
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Description text.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Helper text under the label.
	 *
	 * @var string
	 */
	protected $label_description = '';

	/**
	 * Default structured value.
	 *
	 * @var mixed
	 */
	protected $default;

	/**
	 * Raw markup before component.
	 *
	 * @var string
	 */
	protected $before = '';

	/**
	 * Raw markup after component.
	 *
	 * @var string
	 */
	protected $after = '';

	/**
	 * Helper/footnote markup.
	 *
	 * @var string
	 */
	protected $help = '';

	/**
	 * Additional wrapper class.
	 *
	 * @var string
	 */
	protected $css_class = '';

	/**
	 * Additional HTML attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected $attributes = array();

	/**
	 * Back-compat args bag.
	 *
	 * @var array<string, mixed>
	 */
	protected $args = array();

	/**
	 * Custom sanitize callable.
	 *
	 * @var callable|string|null
	 */
	protected $sanitize_callback = null;

	/**
	 * Field manager (used for nested fields).
	 *
	 * @var \WPMoo\Fields\Manager|null
	 */
	protected $field_manager = null;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $config Component configuration.
	 */
	public function __construct( array $config ) {
		$this->component          = isset( $config['component'] ) ? (string) $config['component'] : '';
		$this->id                 = isset( $config['id'] ) ? (string) $config['id'] : '';
		$this->label              = isset( $config['label'] ) ? (string) $config['label'] : '';
		$this->description        = isset( $config['description'] ) ? (string) $config['description'] : '';
		$this->label_description  = isset( $config['label_description'] ) ? (string) $config['label_description'] : '';
		$this->default            = array_key_exists( 'default', $config ) ? $config['default'] : array();
		$this->before             = isset( $config['before'] ) ? (string) $config['before'] : '';
		$this->after              = isset( $config['after'] ) ? (string) $config['after'] : '';
		$this->help               = isset( $config['help'] ) ? (string) $config['help'] : '';
		$this->css_class          = isset( $config['css_class'] ) ? (string) $config['css_class'] : '';
		$this->attributes         = isset( $config['attributes'] ) && is_array( $config['attributes'] ) ? $config['attributes'] : array();
		$this->sanitize_callback  = isset( $config['sanitize'] ) ? $config['sanitize'] : null;
		$this->field_manager      = isset( $config['field_manager'] ) && $config['field_manager'] instanceof \WPMoo\Fields\Manager ? $config['field_manager'] : null;

		if ( '' === $this->label && isset( $config['title'] ) ) {
			$this->label = (string) $config['title'];
		}
	}

	/**
	 * Retrieve the identifier.
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Retrieve component slug.
	 */
	public function component(): string {
		return $this->component;
	}

	/**
	 * Retrieve visible label.
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * Retrieve description.
	 */
	public function description(): string {
		return $this->description;
	}

	/**
	 * Retrieve label helper text.
	 */
	public function label_description(): string {
		return $this->label_description;
	}

	/**
	 * Retrieve default structured value.
	 *
	 * @return mixed
	 */
	public function default() {
		return $this->default;
	}

	/**
	 * Additional attributes.
	 *
	 * @return array<string, mixed>
	 */
	public function attributes(): array {
		return $this->attributes;
	}

	/**
	 * Wrapper class string.
	 */
	public function css_class(): string {
		return $this->css_class;
	}

	/**
	 * Raw before markup.
	 */
	public function before(): string {
		return $this->before;
	}

	/**
	 * Raw after markup.
	 */
	public function after(): string {
		return $this->after;
	}

	/**
	 * Helper text markup.
	 */
	public function help(): string {
		return $this->help;
	}

	/**
	 * Layout components persist their structured values.
	 */
	public function should_save(): bool {
		return true;
	}

	/**
	 * Sanitized before markup.
	 */
	public function before_html(): string {
		return $this->sanitize_markup( $this->before );
	}

	/**
	 * Sanitized after markup.
	 */
	public function after_html(): string {
		return $this->sanitize_markup( $this->after );
	}

	/**
	 * Sanitized help markup.
	 */
	public function help_html(): string {
		return $this->sanitize_markup( $this->help );
	}

	/**
	 * Plain-text helper summary.
	 */
	public function help_text(): string {
		$help = $this->help_html();

		if ( '' === $help ) {
			return '';
		}

		if ( function_exists( 'wp_strip_all_tags' ) ) {
			$help = wp_strip_all_tags( $help );
		} else {
			$help = (string) preg_replace( '/<[^>]*>/', '', (string) $help );
		}

		$help = preg_replace( '/\s+/u', ' ', (string) $help );

		return trim( (string) $help );
	}

	/**
	 * Retrieve the field manager (fallback to singleton).
	 */
	protected function field_manager(): \WPMoo\Fields\Manager {
		return $this->field_manager instanceof \WPMoo\Fields\Manager ? $this->field_manager : \WPMoo\Fields\Manager::instance();
	}

	/**
	 * Render component markup.
	 *
	 * @param string $name  Input name root.
	 * @param mixed  $value Stored value.
	 * @return string
	 */
	abstract public function render( $name, $value );

	/**
	 * Sanitize structured value.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	public function sanitize( $value ) {
		if ( is_string( $this->sanitize_callback ) && 'none' === $this->sanitize_callback ) {
			return $value;
		}

		if ( is_callable( $this->sanitize_callback ) ) {
			return call_user_func( $this->sanitize_callback, $value, $this );
		}

		return is_array( $value ) ? $value : array();
	}

	/**
	 * Build the layout configuration.
	 *
	 * @return array Configuration array.
	 */
	public function build() {
		return array(
			'id'                  => $this->id,
			'component'           => $this->component,
			'label'               => $this->label,
			'description'         => $this->description,
			'label_description'   => $this->label_description,
			'default'             => $this->default,
			'before'              => $this->before,
			'after'               => $this->after,
			'help'                => $this->help,
			'css_class'           => $this->css_class,
			'attributes'          => $this->attributes,
			'sanitize_callback'   => $this->sanitize_callback,
			'field_manager'       => $this->field_manager,
		);
	}

	/**
	 * Sanitize markup helper.
	 *
	 * @param string $value Raw markup.
	 * @return string
	 */
	protected function sanitize_markup( $value ) {
		if ( '' === $value || null === $value ) {
			return '';
		}

		if ( function_exists( 'wp_kses_post' ) ) {
			return wp_kses_post( $value );
		}

		return $this->esc_html( $value );
	}
}
