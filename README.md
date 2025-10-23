# WPMoo Framework

Modern, lightweight WordPress framework for rapid plugin development with fluent builder APIs.

## Field Widths

Every option field can request a share of the row using `width()` (percentage) or the shorthand `size()` helper. Fields lay out in a flex row and stack automatically on smaller screens:

```php
use WPMoo\Options\Container;
use WPMoo\Options\Field;

Container::create('options', 'demo_settings', 'Demo Settings')
    ->section('layout_examples', 'Layout Examples')
    ->add_fields(array(
        Field::text('first_name', 'First Name')->width(50),
        Field::text('last_name', 'Last Name')->width(50),
        Field::fieldset('profile_card', 'Profile Card')
            ->width(50)
            ->fields(array(
                Field::text('company', 'Company'),
                Field::text('role', 'Role'),
                Field::textarea('notes', 'Notes'),
            )),
        Field::textarea('bio', 'Biography'),
    ));
```

Fieldsets honour the same width rules so you can build card-style layouts without extra markup.

## Moo Facade (DSL)

You can also register pages and sections procedurally without instantiating builders manually:

```php
use WPMoo\Moo;
use WPMoo\Options\Field;

Moo::page('demo_settings', 'Demo Settings');

Moo::section('basic_details', 'Basic Details')
    ->parent('demo_settings')
    ->fields(
        Field::text('first_name', 'First Name')->width(50),
        Field::text('last_name', 'Last Name')->placeholder('Surname')->width(50),
    );

Moo::section('advanced_inputs', 'Advanced Inputs')
    ->parent('demo_settings')
    ->fields(
        Field::fieldset('account_secondary', 'Secondary')
            ->width(50)
            ->fields(
                Field::text('api_token', 'API Token'),
                Field::text('slug', 'Slug')->default('demo'),
            )
    );
```

Use `Moo::page()` (alias `Moo::container()`) to define option pages, `Moo::section()` to attach reusable sections, and `Moo::metabox()` / `Moo::panel()` for post edit screens. Sections can be chained anywhere in your codebase—attach them to pages with `->parent('page_id')` or to metaboxes with `->metabox('metabox_id')`.
