# WPMoo Framework

[![CI](https://github.com/wpmoo-org/wpmoo/actions/workflows/ci.yml/badge.svg)](https://github.com/wpmoo-org/wpmoo/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%3E%3D7.4-777bb4?logo=php)](https://www.php.net/releases/)
[![WordPress](https://img.shields.io/badge/wordpress-tested%206.5%20+-21759b?logo=wordpress)](https://wordpress.org/news/category/releases/)

Modern, lightweight WordPress framework for rapid plugin development with fluent builder APIs.

CI runs linting and compatibility sniffs across PHP 7.4–8.3 and WordPress 6.5+ so the framework stays aligned with its published requirements.

Run `composer check` locally to execute the same validation, lint, and compatibility sniffs before pushing. For a full WordPress.org coding standard pass, run `vendor/bin/phpcs --standard=WordPress --ignore=vendor,node_modules .` manually when you have time to address the broader styling recommendations. A consolidated checklist lives in `wpmoo-docs/content/05.development/local-qa.md`.

## Example: Fluent Section Layout

Sections group fields automatically and expose helpers for grid rows so you can mirror the Pico‑style previews shown in the Samples plugin:

```php
use WPMoo\Moo;
use WPMoo\Fields\Field;


Moo::section('preview', 'Preview form')
    ->description('Small subscription form rendered inside a grid.')
    ->parent('demo_settings')
    ->grid(
        Field::input('first_name')
            ->label('First name')
            ->placeholder('First name')
            ->required(),
        Field::input('email')
            ->label('Email address')
            ->attributes(array(
                'type' => 'email',
                'autocomplete' => 'email',
                'required' => true,
            )),
        Field::button('subscribe')
            ->label('Subscribe')
            ->attributes(array(
                'class' => 'contrast',
                'type'  => 'submit',
            ))
    )
    ->fields(
        Field::toggle('terms')->label('Agree to the terms?')
    );
```

`->grid()` wraps the arguments in a `<div class="grid">` so the framework renders the same responsive layout you see in `src/samples`. Call `->fluid()` on the page definition when you need Pico’s `container-fluid` class instead of the default fixed-width container.

### Accordion layout field

`use WPMoo\Layout\Accordion;` diyerek `Accordion::make()` kullanın. `->items()` ile her paneli ve içerisine giren alanları tanımlayın:

```php
Accordion::make('faq')
    ->label('Frequently asked questions')
    ->items([
        [
            'title'  => 'Accordion 1',
            'open'   => true,
            'fields' => [
                Field::input('faq_text')->label('Text'),
                Field::toggle('faq_switch')->label('Switcher'),
                Field::textarea('faq_details')->label('Textarea'),
            ],
        ],
        [
            'title'  => 'Accordion 2',
            'fields' => [
                Field::select('faq_select')
                    ->label('Select option')
                    ->options([
                        'a' => 'Option A',
                        'b' => 'Option B',
                    ]),
            ],
        ],
    ]);
```

Her iç alan kendi `id` değeriyle kaydedilir ve üst alanın değeri dizi olarak saklanır (`faq[faq_text]`, `faq[faq_switch]` gibi).

### Fieldset layout field

`use WPMoo\Layout\Fieldset;` ile `Fieldset::make()` çağırarak kart benzeri bölümler oluşturabilirsiniz:

```php
Fieldset::make('profile')
    ->label('Profile sections')
    ->items([
        [
            'title'       => 'Basic info',
            'description' => 'Contact details.',
            'fields'      => [
                Field::input('display_name')->label('Display name'),
                Field::input('email')->label('Email')->attributes(['type' => 'email']),
            ],
        ],
        [
            'title'  => 'Preferences',
            'fields' => [
                Field::toggle('newsletter')->label('Receive newsletter'),
                Field::select('language')
                    ->label('Language')
                    ->options([
                        'en' => 'English',
                        'tr' => 'Türkçe',
                    ]),
            ],
        ],
    ]);
```

### Tabs layout field

Tabs artık çekirdek `WPMoo\Layout\Tabs` isim alanında bulunuyor. Builder'ı import edip doğrudan kullanın:

```php
use WPMoo\Layout\Tabs;

Tabs::make('settings_tabs')
    ->items([
        [
            'title'     => 'Account',
            'id'        => 'tab-account',
            'type'      => 'tab',
            'icon_type' => 'dashicons',
            'icon'      => 'dashicons-admin-users',
            'fields'    => [
                Field::input('username')->label('Username'),
                Field::toggle('two_factor')->label('Enable 2FA'),
            ],
        ],
        [
            'title'     => 'Notifications',
            'id'        => 'tab-notifications',
            'icon_type' => 'fontawesome',
            'icon'      => 'fas fa-bell',
            'fields'    => [
                Field::checkbox('email_alerts')->label('Email alerts'),
                Field::checkbox('sms_alerts')->label('SMS alerts'),
            ],
        ],
    ]);
```

Tab item arguments you can pass to `items()`:

- `title` (`label` alias) – tab text.
- `id` – unique fragment/slug (auto-generated when omitted).
- `type` – should remain `tab` for compatibility.
- `description` – optional helper text rendered above the panel.
- `fields` – nested field definitions (required).
- `icon_type` – `dashicons` (default), `fontawesome`, or `url`.
- `icon` – clasname/slug/URL matching the chosen `icon_type`.

Panels are rendered with pure CSS; each panel’s values are stored under `settings_tabs[account][username]`.

### Sidebar navigation layout

Codestar benzeri sol menü istiyorsanız `->sidebar_nav()` çağrısı yapın; tüm `Moo::section` başlıkları otomatik nav öğesi olarak listelenir:

```php
Moo::page('ayarlar')
    ->title('Ayarlar')
    ->sidebar_nav();

Moo::section('genel', 'Genel')
    ->parent('ayarlar')
    ->fields(Field::input('site_title')->label('Site başlığı'));

Moo::section('api', 'API')
    ->parent('ayarlar')
    ->fields(Field::input('api_key')->label('API anahtarı'));

Moo::section('tasarim', 'Tasarım')
    ->parent('ayarlar')
    ->fields(Field::select('tema')->label('Tema'));
```

Sidebar, PicoCSS grid’i ile iki sütuna ayrılır; küçük ekranlarda otomatik olarak üstte nav, altta içerik şeklinde yığılır.

## Using Builders Directly

Prefer working with explicit builders? Compose sections by instantiating `SectionBuilder` / `FieldBuilder` manually and passing them to the Options container:

```php
use WPMoo\Options\Options;
use WPMoo\Fields\FieldBuilder;
use WPMoo\Sections\SectionBuilder;

$options = Options::create('demo_settings')
    ->page_title('Demo Settings')
    ->menu_title('Demo Settings');

$section = ( new SectionBuilder('advanced_inputs', 'Advanced Inputs') )
    ->fields(
        array(
            ( new FieldBuilder('api_token', 'input') )
                ->label('API Token')
                ->attributes( array( 'type' => 'password' ) ),
            ( new FieldBuilder('slug', 'input') )
                ->label('Slug')
                ->default('demo'),
            ( new FieldBuilder('beta_features', 'toggle') )
                ->label('Enable beta features?'),
        )
    );

$options->sections( array( $section ) );
$options->register();
```

Use `Moo::page()` (alias `Moo::container()`) to define option pages, `Moo::section()` to attach reusable sections, and `Moo::metabox()` for post edit screens. Sections can be chained anywhere in your codebase—attach them to pages with `->parent('page_id')` or to metaboxes with `->metabox('metabox_id')`.
