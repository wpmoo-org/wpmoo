<?php

namespace WPMoo\Field\Abstracts;

use WPMoo\Field\Interfaces\FieldInterface;
use WPMoo\Field\Interfaces\FieldSanitizerInterface;
use WPMoo\Field\Interfaces\FieldValidatorInterface;
use WPMoo\Field\Validators\BaseValidator;

/**
 * Base field implementation.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */
abstract class AbstractField implements FieldInterface {
	/**
	 * Field ID.
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Field name.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Field label.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * Field placeholder.
	 *
	 * @var string
	 */
	protected string $placeholder = '';

	/**
	 * Sanitizer instance.
	 *
	 * @var FieldSanitizerInterface|null
	 */
	protected ?FieldSanitizerInterface $sanitizer = null;

	/**
	 * Validator instance.
	 *
	 * @var FieldValidatorInterface
	 */
	protected FieldValidatorInterface $validator;

	/**
	 * Field options for validation.
	 *
	 * @var array<mixed>
	 */
	protected array $validation_options = array();

	/**
	 * Field options (for fields like select, radio, etc.).
	 *
	 * @var array<mixed>
	 */
	protected array $options = array();

	/**
	 * Default value.
	 *
	 * @var mixed
	 */
	protected $default_value = null;

	/**
	 * Constructor.
	 *
	 * @param string $id Field ID.
	 */
	public function __construct( string $id ) {
		$this->id = $id;
		$this->name = $id; // Default name to ID.
		// Initialize with a default validator that handles 'required'.
		$this->validator = new class() extends \WPMoo\Field\Validators\BaseValidator {
			/**
			 * Validate the field value.
			 *
			 * @param mixed        $value The value to validate.
			 * @param array<mixed> $field_options Field options for validation.
			 * @return array{valid:bool, error:string|null} Array with validation result ['valid' => bool, 'error' => string|null].
			 */
			public function validate( mixed $value, array $field_options = array() ): array {
				if ( ! empty( $field_options['required'] ) && empty( $value ) && '0' !== $value ) {
					return array(
						'valid' => false,
						'error' => 'This field is required.',
					);
				}
				return array(
					'valid' => true,
					'error' => null,
				);
			}
		};
	}

	/**
	 * Set field name.
	 *
	 * @param string $name Field name.
	 * @return self
	 */
	public function name( string $name ): self {
		$this->name = $name;
		return $this;
	}

	/**
	 * Set field label.
	 *
	 * @param string $label Field label.
	 * @return self
	 */
	public function label( string $label ): self {
		$this->label = $label;
		return $this;
	}

	/**
	 * Set field placeholder.
	 *
	 * @param string $placeholder Field placeholder.
	 * @return self
	 */
	public function placeholder( string $placeholder ): self {
		$this->placeholder = $placeholder;
		return $this;
	}

	/**
	 * Set field as required.
	 *
	 * @param bool $required Whether the field is required.
	 * @return self
	 */
	public function required( bool $required = true ): self {
		$this->validation_options['required'] = $required;
		return $this;
	}

	/**
	 * Set default value.
	 *
	 * @param mixed $value Default value.
	 * @return self
	 */
	public function default( $value ): self {
		$this->default_value = $value;
		return $this;
	}

	/**
	 * Get default value.
	 *
	 * @return mixed
	 */
	public function get_default() {
		return $this->default_value;
	}

	/**
	 * Set sanitizer.
	 *
	 * @param FieldSanitizerInterface $sanitizer Sanitizer instance.
	 * @return self
	 */
	public function set_sanitizer( FieldSanitizerInterface $sanitizer ): self {
		$this->sanitizer = $sanitizer;
		return $this;
	}

	/**
	 * Set validator.
	 *
	 * @param FieldValidatorInterface $validator Validator instance.
	 * @return self
	 */
	public function set_validator( FieldValidatorInterface $validator ): self {
		$this->validator = $validator;
		return $this;
	}

	/**
	 * Set validation options.
	 *
	 * @param array<mixed> $options Validation options.
	 * @return self
	 */
	public function validation_options( array $options ): self {
		$this->validation_options = $options;
		return $this;
	}

	/**
	 * Set field options.
	 *
	 * @param array<mixed> $options Field options.
	 * @return self
	 */
	public function options( array $options ): self {
		$this->options = $options;
		return $this;
	}

	/**
	 * Get field options.
	 *
	 * @return array<mixed>
	 */
	public function get_options(): array {
		return $this->options;
	}

	/**
	 * Get field ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get field name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Get field placeholder.
	 *
	 * @return string
	 */
	public function get_placeholder(): string {
		return $this->placeholder;
	}

	/**
	 * Sanitize a value using the field's sanitizer.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( mixed $value ): mixed {
		if ( $this->sanitizer ) {
			return $this->sanitizer->sanitize( $value );
		}
		return $value;
	}

	/**
	 * Validate a value using the field's validator.
	 *
	 * @param mixed $value The value to validate.
	 * @return array{valid:bool, error:string|null} Array containing validation result ['valid' => bool, 'error' => string|null].
	 */
	public function validate( mixed $value ): array {
		return $this->validator->validate( $value, $this->validation_options );
	}
}
