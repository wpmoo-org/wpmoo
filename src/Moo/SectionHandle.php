<?php
/**
 * Fluent section wrapper used by Moo::make().
 *
 * @package WPMoo\Moo
 */

namespace WPMoo\Moo;

use InvalidArgumentException;
use WPMoo\Moo\PageHandle;
use WPMoo\Options\Field as FieldDefinition;
use WPMoo\Options\FieldBuilder;
use WPMoo\Options\SectionBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a section definition registered through Moo::make().
 */
class SectionHandle {

	/**
	 * Section identifier.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Section title.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Section description.
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Section icon (dashicons).
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Recorded column definitions.
	 *
	 * @var array<int, mixed>
	 */
	protected $columns = array();

	/**
	 * Saved field definitions.
	 *
	 * @var array<int, mixed>
	 */
	protected $pending_fields = array();

	/**
	 * Parent page handle.
	 *
	 * @var PageHandle|null
	 */
	protected $page = null;

	/**
	 * Parent page identifier (when page not yet loaded).
	 *
	 * @var string|null
	 */
	protected $parent_id = null;

	/**
	 * Underlying SectionBuilder once attached.
	 *
	 * @var SectionBuilder|null
	 */
	protected $builder = null;

	/**
	 * Whether the section has been attached.
	 *
	 * @var bool
	 */
	protected $attached = false;

	/**
	 * Constructor.
	 *
	 * @param string $id          Section identifier.
	 * @param string $title       Section title.
	 * @param string $description Section description.
	 */
	public function __construct( string $id, string $title = '', string $description = '' ) {
		$this->id          = $id;
		$this->title       = $title;
		$this->description = $description;
	}

	/**
	 * Retrieve the section identifier.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Assign a description.
	 *
	 * @param string $description Description text.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->description = $description;

		if ( $this->builder ) {
			$this->builder->description( $description );
		}

		return $this;
	}

	/**
	 * Update the section title.
	 *
	 * @param string $title Section title.
	 * @return $this
	 */
	public function title( string $title ): self {
		$this->title = $title;

		if ( $this->builder ) {
			$this->builder->title( $title );
		}

		return $this;
	}

	/**
	 * Set a dashicon icon.
	 *
	 * @param string $icon Icon class.
	 * @return $this
	 */
	public function icon( string $icon ): self {
		$this->icon = $icon;

		if ( $this->builder ) {
			$this->builder->icon( $icon );
		}

		return $this;
	}

	/**
	 * Define column spans for this section.
	 *
	 * @param mixed ...$columns Column definitions.
	 * @return $this
	 */
	public function columns( ...$columns ): self {
		$this->columns = $columns;

		if ( $this->builder ) {
			$this->builder->columns( ...$columns );
		}

		return $this;
	}

	/**
	 * Alias for columns().
	 *
	 * @param mixed ...$columns Column definitions.
	 * @return $this
	 */
	public function size( ...$columns ): self {
		return $this->columns( ...$columns );
	}

	/**
	 * Attach the section to a parent page.
	 *
	 * @param string|PageHandle $page Page identifier or handle.
	 * @return $this
	 */
	public function parent( $page ): self {
		if ( $page instanceof PageHandle ) {
			$this->parent_id = $page->id();
			$page->attachSection( $this );
			return $this;
		}

		$this->parent_id = (string) $page;
		\WPMoo\Moo::assignSectionToPage( $this, $this->parent_id );

		return $this;
	}

	/**
	 * Define multiple fields.
	 *
	 * @param mixed ...$fields Field definitions.
	 * @return $this
	 */
	public function fields( ...$fields ): self {
		if ( 1 === count( $fields ) && is_array( $fields[0] ) && ! $fields[0] instanceof FieldDefinition ) {
			$fields = $fields[0];
		}

		foreach ( $fields as $field ) {
			$this->pending_fields[] = $field;
		}

		$this->flush_fields();

		return $this;
	}

	/**
	 * Helper to add a single field.
	 *
	 * @param mixed $field Field definition.
	 * @return $this
	 */
	public function field( $field ): self {
		return $this->fields( $field );
	}

	/**
	 * Convenience helper for branching Moo definitions.
	 *
	 * @param string $type Type identifier.
	 * @param mixed  ...$arguments Additional arguments.
	 * @return mixed
	 */
	public function make( string $type, ...$arguments ) {
        return \WPMoo\Moo::make( $type, ...$arguments );
	}

	/**
	 * Internal: attach to a page handle.
	 *
	 * @param PageHandle $page Page handle.
	 * @return void
	 */
	public function attach( PageHandle $page ): void {
		if ( $this->attached ) {
			return;
		}

		$this->page    = $page;
		$this->builder = $page->builder()->section( $this->id, $this->title, $this->description );

		if ( '' !== $this->icon ) {
			$this->builder->icon( $this->icon );
		}

		if ( ! empty( $this->columns ) ) {
			$this->builder->columns( ...$this->columns );
		}

		$this->flush_fields();
		$this->attached = true;
	}

	/**
	 * Export configuration snapshot.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'          => $this->id,
			'title'       => $this->title,
			'description' => $this->description,
			'icon'        => $this->icon,
			'parent'      => $this->parent_id,
			'columns'     => $this->columns,
		);
	}

	/**
	 * Normalise and pass stored fields to the section builder.
	 *
	 * @return void
	 */
	protected function flush_fields(): void {
		if ( ! $this->builder || empty( $this->pending_fields ) ) {
			return;
		}

		$prepared = array();

		foreach ( $this->pending_fields as $field ) {
			$prepared[] = $this->normalise_field( $field );
		}

		$this->pending_fields = array();
		$this->builder->fields( $prepared );
	}

	/**
	 * Convert an incoming field definition into an array.
	 *
	 * @param mixed $field Raw field definition.
	 * @return array<string, mixed>
	 */
	protected function normalise_field( $field ): array {
		if ( $field instanceof FieldDefinition ) {
			return $field->toArray();
		}

		if ( $field instanceof FieldBuilder ) {
			return $field->build();
		}

		if ( is_array( $field ) ) {
			if ( empty( $field['id'] ) || empty( $field['type'] ) ) {
				throw new InvalidArgumentException( 'Field arrays require both "id" and "type" keys.' );
			}

			return $field;
		}

		throw new InvalidArgumentException( 'Unsupported field definition supplied to Moo::make() section.' );
	}
}
