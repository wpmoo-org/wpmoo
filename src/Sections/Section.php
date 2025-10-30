<?php
/**
 * Fluent section builder used by the Container API.
 *
 * @package WPMoo\Sections
 */

namespace WPMoo\Sections;

use InvalidArgumentException;
use WPMoo\Fields\FieldBuilder;
use WPMoo\Support\Concerns\HasColumns;
use WPMoo\Support\Concerns\TranslatesStrings;
use WPMoo\Support\Str;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Represents a section/panel.
 */
class Section {
	use HasColumns;
	use TranslatesStrings;

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
	 * Section icon (dashicon class).
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Registered field definitions.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $fields = array();

	/**
	 * Layout settings.
	 *
	 * @var array<string, mixed>
	 */
	protected $layout = array(
		'size'    => 12,
		'columns' => array(
			'default' => 12,
		),
	);

	/**
	 * Constructor.
	 *
	 * @param string $id          Section identifier.
	 * @param string $title       Section title.
	 * @param string $description Section description.
	 */
	public function __construct( string $id, string $title = '', string $description = '' ) {
		if ( '' === $title ) {
			$title = ucwords( str_replace( array( '-', '_' ), ' ', $id ) );
		}

		if ( '' === $id ) {
			$id = $title;
		}

		$this->id          = $this->normalize_id( $id );
		$this->title       = $title;
		$this->description = $description;
	}

	/**
	 * Static factory.
	 *
	 * @param string      $id_or_title Identifier or title.
	 * @param string|null $title       Optional explicit title.
	 * @param string      $description Optional description.
	 * @return static
	 */
    public static function make( string $id_or_title, ?string $title = null, string $description = '' ): static {
        if ( null === $title || '' === $title ) {
            return new static( $id_or_title, $id_or_title, $description );
        }

        return new static( $id_or_title, $title, $description );
    }

	/**
	 * Set section title.
	 *
	 * @param string $title Section title.
	 * @return $this
	 */
	public function title( string $title ): self {
		$this->title = $title;

		return $this;
	}

	/**
	 * Set section description.
	 *
	 * @param string $description Description text.
	 * @return $this
	 */
	public function description( string $description ): self {
		$this->description = $description;

		return $this;
	}

	/**
	 * Set section icon (dashicons class).
	 *
	 * @param string $icon Icon identifier.
	 * @return $this
	 */
	public function icon( string $icon ): self {
		$this->icon = $icon;

		return $this;
	}

	public function set_title( string $title ): self {
		return $this->title( $title );
	}

	public function set_description( string $description ): self {
		return $this->description( $description );
	}

	public function set_icon( string $icon ): self {
		return $this->icon( $icon );
	}

	/**
	 * Retrieve the section layout configuration.
	 *
	 * @param string|null $key Optional key.
	 * @return mixed
	 */
	public function layout( ?string $key = null ) {
		if ( null === $key ) {
			return $this->layout;
		}

		return isset( $this->layout[ $key ] ) ? $this->layout[ $key ] : null;
	}

	/**
	 * Define responsive column spans for the section.
	 *
	 * @param mixed ...$columns Column definitions.
	 * @return $this
	 */
	public function size( ...$columns ): self {
		$parsed       = $this->parseColumnSpans( $columns );
		$this->layout = array(
			'size'    => $parsed['default'],
			'columns' => $parsed,
		);

		return $this;
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
	 * Add a field to the section.
	 *
	 * @param FieldBuilder|array<string, mixed> $field Field definition.
	 * @return $this
	 */
	public function add_field( $field ): self {
		$this->fields[] = $this->normalize_field( $field );

		return $this;
	}

	/**
	 * Add multiple fields.
	 *
	 * @param array<int, mixed> $fields List of field definitions.
	 * @return $this
	 */
	public function add_fields( array $fields ): self {
		foreach ( $fields as $field ) {
			$this->add_field( $field );
		}

		return $this;
	}

	/**
	 * Determine whether the section has fields configured.
	 *
	 * @return bool
	 */
	public function has_fields(): bool {
		return ! empty( $this->fields );
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
	 * Export the section configuration.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'id'          => $this->id,
			'title'       => $this->title,
			'description' => $this->description,
			'icon'        => $this->icon,
			'fields'      => $this->fields,
			'layout'      => $this->layout,
		);
	}

	/**
	 * Normalize the section identifier.
	 *
	 * @param string $value Raw identifier.
	 * @return string
	 * @throws InvalidArgumentException When id is empty.
	 */
	protected function normalize_id( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			throw new InvalidArgumentException( 'Section id cannot be empty.' );
		}

		$slug = Str::slug( $value );

		return '' !== $slug ? $slug : $value;
	}

	/**
	 * Normalize a field definition into an array.
	 *
	 * @param mixed $field Raw field definition.
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException When field definition is invalid.
	 */
    protected function normalize_field( $field ): array {
        if ( $field instanceof FieldBuilder ) {
            return $field->build();
        }

		if ( is_array( $field ) ) {
			if ( empty( $field['id'] ) ) {
				throw new InvalidArgumentException( 'Field configuration requires an "id" key.' );
			}

			if ( empty( $field['type'] ) ) {
                /* phpcs:disable WordPress.Security.EscapeOutput */
				throw new InvalidArgumentException(
					sprintf(
						$this->translate( 'Field "%s" configuration requires a "type" key.' ),
						(string) $field['id']
					)
				);
                /* phpcs:enable WordPress.Security.EscapeOutput */
			}

			return $field;
		}

		$type = is_object( $field ) ? get_class( $field ) : gettype( $field );

        /* phpcs:disable WordPress.Security.EscapeOutput */
		throw new InvalidArgumentException(
			sprintf(
				$this->translate( 'Unsupported field definition of type %s.' ),
				(string) $type
			)
		);
        /* phpcs:enable WordPress.Security.EscapeOutput */
	}
}
