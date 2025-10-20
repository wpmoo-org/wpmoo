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

## Requirements

- PHP 7.4 or higher
- WordPress 5.9 or higher

## License

MIT License
