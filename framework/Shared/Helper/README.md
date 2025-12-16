# WPMoo Helper Classes

This directory contains helper classes that provide common functionality used throughout the WPMoo framework.

## ValidationHelper

The `ValidationHelper` class provides validation functions for various component types to ensure proper format and security. It includes methods for:

- `validate_id_format()` - Validates component ID formats (only lowercase letters, numbers, hyphens, and underscores)
- `validate_plugin_slug()` - Validates plugin slug formats (only lowercase letters, numbers, and hyphens)
- `validate_version_format()` - Validates version string formats (semantic versioning)
- `validate_file_path()` - Validates file path existence and readability

These validation methods are used throughout the framework to enhance security and ensure data integrity.