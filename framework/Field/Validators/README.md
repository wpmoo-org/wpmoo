# WPMoo Field Validators

This directory contains validator classes that provide validation functionality for different field types in the WPMoo framework.

## Available Validators

- `BaseValidator` - Base validator implementation
- `RequiredValidator` - Validates that required fields have values
- `EmailValidator` - Validates email format
- `UrlValidator` - Validates URL format
- `NumberValidator` - Validates numeric values with optional min/max constraints

## Creating Custom Validators

To create a custom validator:

1. Create a new class that extends `BaseValidator`
2. Implement the `validate` method
3. Return an array with the format: `['valid' => bool, 'error' => string|null]`

Example:
```php
class CustomValidator extends BaseValidator implements FieldValidatorInterface {
    public function validate(mixed $value, array $field_options = []): array {
        if ($value === 'invalid') {
            return ['valid' => false, 'error' => 'Value cannot be "invalid".'];
        }
        
        return ['valid' => true, 'error' => null];
    }
}
```

## Integration

Validators are used by field types during form submission to ensure data validity before sanitization and storage.