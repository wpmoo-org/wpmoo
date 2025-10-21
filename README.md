# WPMoo Framework

Modern, lightweight WordPress framework for rapid plugin development with fluent builder APIs.

## Features

- 🐮 **Modern PHP** - Built with PHP 7.4+ features
- 🎯 **Type-Safe** - Full type hints and return types
- 🚀 **Fast** - Optimized performance with minimal overhead
- 🎨 **Modern UI** - Pines UI/Tailwind CSS admin interface
- 🔧 **Developer Friendly** - Intuitive, fluent builder APIs for all components
- 🔄 **Backward Compatible** - Works with both builder and array-based syntax

## Installation

```bash
composer require wpmoo-org/wpmoo
```

## Bootstrap in a plugin

```php
use WPMoo\Core\App;
require __DIR__ . '/vendor/autoload.php';
App::instance()->boot(__FILE__, 'your-plugin-textdomain');
```

## Quick Examples

### Post Types with Builder

```php
use WPMoo\PostType\Builder;

Builder::create('book')
    ->labels('Book', 'Books')
    ->icon('book')
    ->supports(['title', 'editor', 'thumbnail'])
    ->hierarchical()
    ->hasArchive()
    ->menuPosition(5)
    ->public()
    ->showInMenu()
    ->register();
```

### Taxonomies with Builder

```php
use WPMoo\Taxonomy\Builder;

Builder::create('genre', 'book')
    ->labels('Genre', 'Genres')
    ->hierarchical()
    ->showInMenu()
    ->showInRest()
    ->register();
```

### Options Pages with Builder

```php
use WPMoo\Options\Builder;

Builder::create('my_settings')
    ->pageTitle('My Settings')
    ->menuTitle('Settings')
    ->capability('manage_options')
    ->icon('dashicons-admin-generic')
    ->section('general', 'General Settings')
        ->field('site_title', 'text')
            ->label('Site Title')
            ->description('Your website title')
            ->default('My Awesome Site')
            ->end()
        ->field('enabled', 'checkbox')
            ->label('Enable Feature')
            ->default(true)
            ->end()
        ->endSection()
    ->register();
```

### Metaboxes with Builder

```php
use WPMoo\Metabox\Builder;

Builder::create('product_details')
    ->title('Product Details')
    ->postType('product')
    ->normal()
    ->high()
    ->field('price', 'text')
        ->label('Price')
        ->description('Product price in USD')
        ->default('0.00')
        ->end()
    ->field('sku', 'text')
        ->label('SKU')
        ->end()
    ->register();
```

## Documentation

📚 **Full documentation available at:** [wpmoo-docs](https://github.com/wpmoo-org/wpmoo-docs)

- [Getting Started](https://github.com/wpmoo-org/wpmoo-docs/blob/main/docs/getting-started.md)
- [PostType Features & Builder](https://github.com/wpmoo-org/wpmoo-docs/blob/main/docs/post-types/features.md)
- [Options Builder API](https://github.com/wpmoo-org/wpmoo-docs/blob/main/docs/options/builder.md)
- [Metabox Builder API](https://github.com/wpmoo-org/wpmoo-docs/blob/main/docs/metabox/builder.md)

## CLI Utilities

The framework ships with a small CLI at `bin/moo` to automate common tasks:

- `php bin/moo build [--pm=<manager>] [--install|--no-install] [--script=<name>]`  
  Detects (or uses the provided) package manager, installs dependencies when needed, and runs the specified npm script (defaults to `build`).
- `php bin/moo deploy [destination] [--pm=<manager>] [--no-build] [--zip[=<path>]] [--script=<name>]`  
  Creates a production-ready copy under `destination` (defaults to `dist/<plugin-slug>`), optionally archives it as a `.zip`, and rebuilds assets unless `--no-build` is supplied. Use `--zip` to generate `dist/<plugin-slug>.zip` or pass a custom archive path with `--zip=out.zip`.
- The deploy task automatically strips the local CLI shim (`moo`), runs `composer install --no-dev` when Composer is available, and removes Node/Composer manifests from the package unless filters opt back in.
- `php bin/moo version [--patch|--minor|--major|<version>] [--dry-run]`  
  Bumps the framework version across manifests and queued asset loaders. Defaults to patch increments (e.g., `0.2.0 → 0.2.1`).

Both commands respect `--pm=<npm|yarn|pnpm|bun>` to force a package manager choice and will fall back to lockfile detection otherwise.

When WPMoo is installed as a Composer dependency (e.g., inside a plugin like `wpmoo-starter`), run the same commands via `php vendor/wpmoo-org/wpmoo/bin/moo …` from your project root.

## Requirements

- PHP 7.4 or higher
- WordPress 5.9 or higher

## License

MIT License
