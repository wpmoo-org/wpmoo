# WPMoo

WordPress Micro Object-Oriented Framework (PHP 7.4+).

## Quick start

```bash
composer install
php bin/moo info
```

## Bootstrap in a plugin

```php
use WPMoo\Core\App;
require __DIR__ . '/vendor/autoload.php';
App::instance()->boot(__FILE__, 'your-plugin-textdomain');
```

## Fields: ColorGroup

Render a group of color pickers and store values as an associative array.

```php
use WPMoo\Fields\Manager;

$manager = new Manager();

// Auto-resolves class `WPMoo\\Fields\\ColorGroup\\ColorGroup` from type `color_group`.
$field = $manager->make([
	'id'          => 'theme_colors',
	'type'        => 'color_group',
	'label'       => 'Theme Colors',
	'description' => 'Configure brand colors',
	'default'     => [
		'primary'   => '#0055ff',
		'secondary' => '#111111',
		'accent'    => '#ffcc00',
	],
	'args'        => [
		// You can pass items as associative array or as a list with `key`/`label`.
		'items' => [
			'primary'   => 'Primary',
			'secondary' => 'Secondary',
			'accent'    => 'Accent',
		],
	],
]);

// In your settings/metabox renderer:
echo $field->render('settings[theme_colors]', $saved_values['theme_colors'] ?? null);

// On save:
$sanitized = $field->sanitize($_POST['settings']['theme_colors'] ?? []);
```

## Roadmap

- [ ] Options builder
  - [x] Register pages via [`WPMoo\Options\Options::register`](src/Options/Options.php)
  - [ ] JSON-driven page definitions
  - [ ] Import/export helpers
- [ ] Field library
  - [x] Core inputs (`text`, `textarea`, `checkbox`, `color`)
  - [ ] Repeatable/complex field groups
  - [ ] Async field asset loader
- [ ] Metabox engine
  - [x] Declarative metabox registration with [`WPMoo\Metabox\Metabox::register`](src/Metabox/Metabox.php)
  - [ ] Context-aware autosave safeguards
  - [ ] Gutenberg block bindings
- [ ] Post type & taxonomy DSL
  - [ ] Fluent `PostType::register()` API
  - [ ] Taxonomy registration with relationship mapping
  - [ ] Relationship resolver between post types
- [ ] CLI tooling
  - [x] Bootstrap command router in [`WPMoo\Core\CLI`](src/Core/CLI.php)
  - [ ] Scaffold generators for providers, fields, post types
  - [ ] Task runner integration (cache flush, translations)
- [ ] Developer experience
  - [ ] Configuration caching
  - [ ] Type-safe config schema
  - [ ] Comprehensive unit test suite

## Post types

Register custom post types with the fluent builder:

```php
use WPMoo\PostType\PostType;

PostType::register( 'event' )
    ->singular( 'Event' )
    ->plural( 'Events' )
    ->public()
    ->showInRest()
    ->supports( [ 'title', 'editor', 'excerpt', 'thumbnail' ] )
    ->arg( 'rewrite', [ 'slug' => 'events' ] )
    ->register();
```

Calling `register()` automatically hooks into `init` when necessary, so you can declare types during plugin bootstrap without managing additional actions.
