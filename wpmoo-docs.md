## Developer Notes

- `Fields\Manager::instance()` provides a shared singleton seeded with the core field types (`text`, `textarea`, `checkbox`, `color`, `accordion`, `fieldset`). Extend via the `wpmoo_default_field_types` filter to swap defaults or the `wpmoo_register_field_types` action to add custom types:

  ```php
  add_action( 'wpmoo_register_field_types', function ( WPMoo\Fields\Manager $manager ) {
      $manager->register( 'my-field', \Vendor\Plugin\Fields\MyField::class );
  } );
  ```

- Components that build nested fields (`Options`, `Metabox`, `Fieldset`, `Accordion`) reuse the shared manager, so third-party registrations are available regardless of load order.

- Exception messages should rely on `TranslatesStrings::translate()` (or the static helper patterns in `Options\Field`/`Moo`) rather than `esc_*` functions, ensuring errors remain readable when WordPress localization utilities are not bootstrapped.

- The CLI POT generator now recognises `translate_string()` in addition to the standard WordPress helpers, so strings wrapped in the fallback-friendly helper are still extracted during `php moo update`.

- When introducing additional translation wrappers (plural/context variants), add them to `PotGenerator::$function_meta` so the built-in scanner keeps harvesting those strings.

- `TranslatesStrings` also exposes helpers for contextual and plural translations (`translate_with_context()`, `translate_plural()`, `translate_plural_with_context()`). Prefer these wrappers over direct `_x`, `_n`, `_nx` calls so CLI/CLI tests without WordPress still render meaningful text.
