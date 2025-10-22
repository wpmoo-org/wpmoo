# WPMoo Framework

Modern, lightweight WordPress framework for rapid plugin development with fluent builder APIs.

## Field Layout Sizes

Every option field now understands a `size()` hint that maps to a 12-column responsive grid:

```php
use WPMoo\Options\Container;
use WPMoo\Options\Field;

Container::create('options', 'demo_settings', 'Demo Settings')
    ->section('layout_examples', 'Layout Examples')
    ->add_fields(array(
        Field::fieldset('account_card', 'Account Card')
            ->description('Combine related controls inside a responsive block.')
            ->fields(array(
                Field::text('first_name', 'First Name'),
                Field::text('last_name', 'Last Name'),
                Field::text('email', 'Email Address')
                    ->attributes(array('type' => 'email')),
            ))
            ->size('lg-6'),

        Field::fieldset('profile_card', 'Profile Card')
            ->fields(array(
                Field::text('company', 'Company'),
                Field::text('role', 'Role'),
                Field::textarea('notes', 'Notes'),
            ))
            ->size('lg-6'),

        Field::textarea('full', 'Standalone Field'),
    ));
```

Fieldsets behave like cards that can be placed on the 12-column grid, while every other field automatically spans the full width. In narrow viewports the grid collapses and each fieldset stacks vertically.

## Moo Facade (DSL)

You can also register pages and sections procedurally without instantiating builders manually:

```php
use WPMoo\Moo;
use WPMoo\Options\Field;

Moo::make('page', 'demo_settings', 'Demo Settings');

Moo::make('section', 'basic_details', 'Basic Details')
    ->parent('demo_settings')
    ->fields(
        Field::text('first_name', 'First Name'),
        Field::text('last_name', 'Last Name')->placeholder('Surname'),
    );

Moo::make('section', 'advanced_inputs', 'Advanced Inputs')
    ->parent('demo_settings')
    ->fields(
        Field::fieldset('account_secondary', 'Secondary')
            ->gutter('md')
            ->fields(
                Field::text('api_token', 'API Token'),
                Field::text('slug', 'Slug')->default('demo'),
            )
    );
```

`Moo::make()` accepts `page` (alias `container`) and `section` definitions. Sections can be chained anywhere in your codebase as long as they call `->parent('page_id')` to attach themselves to an existing page.

## Grid Utility Classes

A lightweight utility layer is also available for general layouts:

```html
<div class="wpmoo-grid wpmoo-grid--fields wpmoo-grid--guttered gutter-md">
  <div class="wpmoo-col wpmoo-col-6">…</div>
  <div class="wpmoo-col wpmoo-col-6">…</div>
  <div class="wpmoo-col wpmoo-col-4">…</div>
  <div class="wpmoo-col wpmoo-col-8">…</div>
</div>
```

- `.wpmoo-grid` creates a 12-column responsive grid (6 columns on medium screens, single column on mobile).
- Add `.wpmoo-col` to any grid item to opt into responsive spans.
- `.wpmoo-col-{n}` spans `n` columns (1–12). Missing classes default to full width.
- `.wpmoo-col-{breakpoint}-{n}` applies the span from the specified breakpoint upwards (e.g. `.wpmoo-col-md-6`).
- Optional gap helpers (`gap-sm`, `gap-md`, `gap-lg`, `gap-xl`) adjust the spacing between items.
- Add `.wpmoo-grid--guttered` together with `gutter-sm|md|lg|xl` to introduce padded gutters between columns without breaking alignment.
- Grids collapse to a single column automatically under 782px; you do not need to add custom media queries.

Use the same classes inside custom views, metaboxes, or other admin utilities to align panels with the options UI.
