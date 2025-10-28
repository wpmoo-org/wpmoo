<?php
/**
 * Fluent section wrapper used by the Moo facade.
 *
 * @package WPMoo\Moo
 */

namespace WPMoo\Moo;

use InvalidArgumentException;
use WPMoo\Moo\PageHandle;
use WPMoo\Metabox\FieldBuilder as MetaboxFieldBuilder;
use WPMoo\Options\Field as FieldDefinition;
use WPMoo\Options\FieldBuilder;
use WPMoo\Options\SectionBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Represents a section definition registered through the Moo facade.
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
	 * Parent metabox handle.
	 *
	 * @var MetaboxHandle|null
	 */
	protected $metabox = null;

	/**
	 * Parent metabox identifier (when not yet loaded).
	 *
	 * @var string|null
	 */
	protected $metabox_id = null;

	/**
	 * Attachment context ('page' or 'metabox').
	 *
	 * @var string|null
	 */
	protected $context = null;

	/**
	 * Underlying SectionBuilder once attached.
	 *
	 * @var SectionBuilder|\WPMoo\Metabox\SectionBuilder|null
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

		if ( $this->builder && method_exists( $this->builder, 'columns' ) ) {
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
	 * @throws InvalidArgumentException When the section is already attached to a metabox.
	 */
	public function parent( $page ): self {
		if ( null !== $this->context && 'page' !== $this->context ) {
			throw new InvalidArgumentException( 'Section already assigned to a metabox. Create a new section handle instead.' );
		}

		$this->context = 'page';

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
	 * Attach the section to a metabox.
	 *
	 * @param string|MetaboxHandle $metabox Metabox identifier or handle.
	 * @return $this
	 * @throws InvalidArgumentException When the section is already attached to a page.
	 */
	public function metabox( $metabox ): self {
		if ( null !== $this->context && 'metabox' !== $this->context ) {
			throw new InvalidArgumentException( 'Section already assigned to a page. Create a new section handle for metabox usage.' );
		}

		$this->context = 'metabox';

		if ( $metabox instanceof MetaboxHandle ) {
			$this->metabox    = $metabox;
			$this->metabox_id = $metabox->id();
			$metabox->attachSection( $this );

			return $this;
		}

		$this->metabox_id = (string) $metabox;
		\WPMoo\Moo::assignSectionToMetabox( $this, $this->metabox_id );

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
	 * @throws InvalidArgumentException When attempting to attach a metabox section to a page.
	 */
	public function attach( PageHandle $page ): void {
		if ( $this->attached ) {
			return;
		}

		if ( null === $this->context ) {
			$this->context = 'page';
		}

		if ( 'metabox' === $this->context ) {
			throw new InvalidArgumentException( 'Section configured for a metabox cannot be attached to a page.' );
		}

		$this->page    = $page;
		$this->builder = $page->builder()->section( $this->id, $this->title, $this->description );

		$this->apply_icon();
		$this->apply_columns();
		$this->flush_fields();
		$this->attached = true;
	}

	/**
	 * Internal: attach to a metabox handle.
	 *
	 * @param MetaboxHandle $metabox Metabox handle.
	 * @return void
	 * @throws InvalidArgumentException When attempting to attach a page section to a metabox.
	 */
	public function attachToMetabox( MetaboxHandle $metabox ): void {
		if ( $this->attached ) {
			return;
		}

		if ( null === $this->context ) {
			$this->context = 'metabox';
		}

		if ( 'page' === $this->context ) {
			throw new InvalidArgumentException( 'Section configured for a page cannot be attached to a metabox.' );
		}

		$this->metabox    = $metabox;
		$this->metabox_id = $metabox->id();
		$this->builder    = $metabox->builder()->section( $this->id, $this->title, $this->description );

		$this->apply_icon();
		$this->apply_columns();
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
			'metabox'     => $this->metabox_id,
			'columns'     => $this->columns,
			'context'     => $this->context,
		);
	}

	/**
	 * Apply stored icon to the builder when available.
	 *
	 * @return void
	 */
	protected function apply_icon(): void {
		if ( '' === $this->icon || ! $this->builder || ! method_exists( $this->builder, 'icon' ) ) {
			return;
		}

		$this->builder->icon( $this->icon );
	}

	/**
	 * Apply stored column configuration when supported.
	 *
	 * @return void
	 */
	protected function apply_columns(): void {
		if ( empty( $this->columns ) || ! $this->builder || ! method_exists( $this->builder, 'columns' ) ) {
			return;
		}

		$this->builder->columns( ...$this->columns );
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
	 * @throws InvalidArgumentException When the field definition is invalid.
	 */
	protected function normalise_field( $field ): array {
		if ( $field instanceof FieldDefinition ) {
			return $field->toArray();
		}

		if ( $field instanceof FieldBuilder ) {
			return $field->build();
		}

		if ( $field instanceof MetaboxFieldBuilder ) {
			return $field->build();
		}

		if ( is_array( $field ) ) {
			if ( empty( $field['id'] ) || empty( $field['type'] ) ) {
				throw new InvalidArgumentException( 'Field arrays require both "id" and "type" keys.' );
			}

			return $field;
		}

		throw new InvalidArgumentException( 'Unsupported field definition supplied to Moo section.' );
	}
}
