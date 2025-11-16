<?php
/**
 * Fluent section wrapper used by the Moo facade.
 *
 * @package WPMoo\Moo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
namespace WPMoo\Moo;

use InvalidArgumentException;
use WPMoo\Moo\PageHandle;
use WPMoo\Fields\Builders\FieldBuilder;
use WPMoo\Layout\Builders\LayoutBuilder;
use WPMoo\Sections\Builder as SectionBuilder;

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
	 * Saved field definitions.
	 *
	 * @var array<int, mixed>
	 */
	protected $pending_fields = array();

	/**
	 * Pending HTML content before the builder attaches.
	 *
	 * @var callable|string|null
	 */
	protected $pending_html = null;

	/**
	 * Indicates whether this section declares option fields.
	 *
	 * @var bool
	 */
	protected $options_enabled = false;

	/**
	 * Pending layout groups (grid wrappers etc).
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $pending_layout_groups = array();

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
	 * @var SectionBuilder|null
	 */
	protected $section_builder = null;

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

		if ( $this->section_builder ) {
			$this->section_builder->description( $description );
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

		if ( $this->section_builder ) {
			$this->section_builder->title( $title );
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

		if ( $this->section_builder ) {
			$this->section_builder->icon( $icon );
		}

		return $this;
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
	 * @param (FieldBuilder|LayoutBuilder|array<string,mixed>) ...$fields Field definitions or an array of definitions.
	 * @return $this
	 */
	public function fields( ...$fields ): self {
		// Back-compat: direct fields() usage now maps to options().
		if ( function_exists( '_doing_it_wrong' ) ) {
			_doing_it_wrong( __METHOD__, 'Use Section::options() to define option fields.', '0.1.x' );
		}
		return $this->options( ...$fields );
	}

	/**
	 * Declare option fields for this section.
	 *
	 * @param mixed ...$fields Field definitions or arrays of definitions.
	 * @return $this
	 */
	public function options( ...$fields ): self {
		if ( 1 === count( $fields ) && is_array( $fields[0] ) ) {
			$fields = $fields[0];
		}

		$this->options_enabled = true;

		foreach ( $fields as $field ) {
			$this->pending_fields[] = $field;
		}

		$this->flush_fields();

		return $this;
	}

	/**
	 * Attach raw HTML or render callback to the section.
	 *
	 * @param callable|string $content Content definition.
	 * @return $this
	 */
	public function html( $content ): self {
		if ( $this->section_builder ) {
			$this->section_builder->html( $content );
		} else {
			$this->pending_html = $content;
		}

		return $this;
	}

	/**
	 * Register a grid wrapper for the provided fields.
	 *
	 * @param (\WPMoo\Fields\Builders\FieldBuilder|array<string,mixed>) ...$fields Field definitions assigned to the grid.
	 * @return $this
	 */
	public function grid( ...$fields ): self {
		if ( empty( $fields ) ) {
			return $this;
		}

		if ( 1 === count( $fields ) && is_array( $fields[0] ) ) {
			$fields = $fields[0];
		}

		$field_ids = array();

		foreach ( $fields as $field ) {
			$field_ids[] = $this->extract_field_id( $field );
		}

		$this->options( ...$fields );
		$this->queue_layout_group( 'grid', $field_ids );

		return $this;
	}

	/**
	 * Helper to add a single field.
	 *
	 * @param mixed $field Field definition.
	 * @return $this
	 */
	public function field( $field ): self {
		return $this->options( $field );
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
		$this->section_builder = $page->builder()->section( $this->id, $this->title, $this->description );

		$this->apply_icon();
		$this->flush_fields();
		$this->apply_html();
		$this->flush_layout_groups();
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
		$this->section_builder    = $metabox->builder()->section( $this->id, $this->title, $this->description );

		$this->apply_icon();
		$this->flush_fields();
		$this->apply_html();
		$this->flush_layout_groups();
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
			'context'     => $this->context,
		);
	}

	/**
	 * Apply stored icon to the builder when available.
	 *
	 * @return void
	 */
	protected function apply_icon(): void {
		if ( '' === $this->icon || ! $this->section_builder || ! method_exists( $this->section_builder, 'icon' ) ) {
			return;
		}

		$this->section_builder->icon( $this->icon );
	}

	/**
	 * Normalise and pass stored fields to the section builder.
	 *
	 * @return void
	 */
	protected function flush_fields(): void {
		if ( ! $this->section_builder || empty( $this->pending_fields ) ) {
			return;
		}

		$prepared = array();

		foreach ( $this->pending_fields as $field ) {
			$prepared[] = $this->normalise_field( $field );
		}

		if ( $this->options_enabled && method_exists( $this->section_builder, 'enable_options' ) ) {
			$this->section_builder->enable_options();
		}

		$this->pending_fields = array();
		$this->section_builder->fields( $prepared );
	}

	/**
	 * Apply pending HTML if present.
	 *
	 * @return void
	 */
	protected function apply_html(): void {
		if ( ! $this->section_builder || null === $this->pending_html ) {
			return;
		}

		$this->section_builder->html( $this->pending_html );
		$this->pending_html = null;
	}

	/**
	 * Apply any queued layout groups to the builder.
	 *
	 * @return void
	 */
	protected function flush_layout_groups(): void {
		if ( ! $this->section_builder || empty( $this->pending_layout_groups ) ) {
			return;
		}

		foreach ( $this->pending_layout_groups as $group ) {
			if ( ! isset( $group['type'], $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}

			if ( method_exists( $this->section_builder, 'add_layout_group' ) ) {
				$this->section_builder->add_layout_group( (string) $group['type'], $group['fields'] );
			}
		}

		$this->pending_layout_groups = array();
	}

	/**
	 * Convert an incoming field definition into an array.
	 *
	 * @param \WPMoo\Fields\Builders\FieldBuilder|array<string,mixed> $field Raw field definition.
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException When the field definition is invalid.
	 */
	protected function normalise_field( $field ): array {
		if ( $field instanceof FieldBuilder ) {
			return $field->build();
		}

		if ( $field instanceof LayoutBuilder ) {
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

	/**
	 * Extract the field identifier from a definition.
	 *
	 * @param \WPMoo\Fields\Builders\FieldBuilder|array<string,mixed> $field Field definition.
	 * @return string
	 * @throws InvalidArgumentException When the id cannot be determined.
	 */
	protected function extract_field_id( $field ): string {
		if ( $field instanceof FieldBuilder || $field instanceof LayoutBuilder ) {
			$field_id = $field->id();

			if ( '' !== $field_id ) {
				return $field_id;
			}
		} elseif ( is_array( $field ) && isset( $field['id'] ) ) {
			$field_id = (string) $field['id'];

			if ( '' !== $field_id ) {
				return $field_id;
			}
		}

		throw new InvalidArgumentException( 'Grid layout groups require valid field identifiers.' );
	}

	/**
	 * Queue or apply a layout group definition.
	 *
	 * @param string             $type      Group type identifier.
	 * @param array<int, string> $field_ids Field identifiers.
	 * @return void
	 */
	protected function queue_layout_group( string $type, array $field_ids ): void {
		$type = strtolower( trim( $type ) );

		if ( '' === $type ) {
			return;
		}

		$field_ids = array_values(
			array_filter(
				array_map(
					static function ( $field_id ) {
						$normalized = trim( (string) $field_id );
						return '' === $normalized ? null : $normalized;
					},
					$field_ids
				)
			)
		);

		if ( empty( $field_ids ) ) {
			return;
		}

		if ( $this->section_builder && method_exists( $this->section_builder, 'add_layout_group' ) ) {
			$this->section_builder->add_layout_group( $type, $field_ids );
			return;
		}

		$this->pending_layout_groups[] = array(
			'type'   => $type,
			'fields' => $field_ids,
		);
	}
}
