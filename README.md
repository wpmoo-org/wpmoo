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
