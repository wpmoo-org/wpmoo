<?php
/**
 * Base section builder shared by Options and Metabox components.
 *
 * @package WPMoo\Sections
 * @since 0.4.4
 */

namespace WPMoo\Sections;

use WPMoo\Support\Concerns\HasColumns;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provides common section properties and layout helpers.
 */
class SectionBuilder {
	use HasColumns;

	/**
	 * Section ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Section title.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Section description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Dashicons icon class.
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Layout config (size/columns), default full width.
	 *
	 * @var array<string, mixed>
	 */
	protected $layout = array(
		'size'    => 12,
		'columns' => array( 'default' => 12 ),
	);

	/**
	 * Constructor.
	 *
	 * @param string $id          Section ID.
	 * @param string $title       Title.
	 * @param string $description Description.
	 */
	public function __construct( string $id, string $title = '', string $description = '' ) {
		$this->id          = $id;
		$this->title       = $title;
		$this->description = $description;
	}

	/**
	 * Set section title.
	 *
	 * @param string $title Title.
	 * @return $this
	 */
	public function title( string $title ): self {
		$this->title = $title;
		return $this;
	}

	/**
	 * Set section description.
	 *
	 * @param string $description Description.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->description = $description;
		return $this;
	}

	/**
	 * Set dashicons icon.
	 *
	 * @param string $icon Icon class.
	 * @return $this
	 */
	public function icon( string $icon ): self {
		$this->icon = $icon;
		return $this;
	}

	/**
	 * Define column spans (alias for size()).
	 *
	 * @param mixed ...$columns Column definitions.
	 * @return $this
	 */
	public function columns( ...$columns ): self {
		return $this->size( ...$columns );
	}

	/**
	 * Define column spans.
	 *
	 * @param mixed ...$columns Column definitions.
	 * @return $this
	 */
	public function size( ...$columns ): self {
		$parsed = $this->parseColumnSpans( $columns );
		$this->layout['columns'] = $parsed;
		$this->layout['size']    = $parsed['default'];
		return $this;
	}

	/**
	 * Get section id.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Get layout.
	 *
	 * @return array<string, mixed>
	 */
	public function get_layout(): array {
		return $this->layout;
	}
}
