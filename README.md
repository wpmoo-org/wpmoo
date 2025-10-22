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
        Field::text('half_left', 'Half Left')->size(6),
        Field::text('half_right', 'Half Right')->size(6),
        Field::color('quarter', 'Quarter Width')->size(3),
        Field::textarea('full', 'Full Width'),
    ));
```

On large screens the fields above render next to each other (6 + 6, then 3) and automatically stack on tablets/phones.

## Grid Utility Classes

A lightweight utility layer is also available for general layouts:

```html
<div class="wpmoo-grid wpmoo-grid--fields">
  <div class="wpmoo-col-span-6">…</div>
  <div class="wpmoo-col-span-6">…</div>
  <div class="wpmoo-col-span-4">…</div>
  <div class="wpmoo-col-span-8">…</div>
</div>
```

- `.wpmoo-grid` creates a 12-column responsive grid (6 columns on medium screens, single column on mobile).
- `.wpmoo-col-span-{n}` spans `n` columns (1–12). Missing classes default to full width.
- Optional gap helpers (`gap-sm`, `gap-md`, `gap-lg`, `gap-xl`) adjust the spacing between items.

Use the same classes inside custom views, metaboxes, or other admin utilities to align panels with the options UI.
