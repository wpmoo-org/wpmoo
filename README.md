# WPMoo – WordPress Micro Object-Oriented Framework

[![CI](https://github.com/wpmoo-org/wpmoo/actions/workflows/ci.yml/badge.svg)](https://github.com/wpmoo-org/wpmoo/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%3E%3D7.4-777bb4?logo=php)](https://www.php.net/releases/)
[![WordPress](https://img.shields.io/badge/wordpress-tested%206.5%20+-21759b?logo=wordpress)](https://wordpress.org/news/category/releases/)

A modern, lightweight WordPress development framework for building plugins with **fluent, expressive APIs**, **PicoCSS-first design**, and **strict architectural boundaries**.

> ⚠️ **Pre-stable release (`v0.1.0`)** — Breaking changes expected before `v1.0.0`. Not yet recommended for production.

---

## 🚀 Features

- **Fluent builders** for options pages, metaboxes, and layout components:
  ```php
  use WPMoo\Moo;
  use WPMoo\Fields\Field;

  Moo::page('site_settings', __('Site Settings', 'wpmoo'))
      ->addField(Field::input('site_title')->label(__('Site Title', 'wpmoo')))
      ->addLayout(
          Moo::layout('tabs')
              ->add_tab('general', __('General', 'wpmoo'), [
                  Field::toggle('dark_mode')->label(__('Dark Mode', 'wpmoo')),
              ])
      );

- [x] PicoCSS-first UI – Clean, responsive admin interfaces with no custom WP overrides.
- [x] Domain-isolated architecture – Fields, Layouts, Pages are fully decoupled.
- [x] WordPress-decoupled core – Business logic is testable without WordPress.
- [x] GPL-2.0-or-later licensed – Fully compliant with WordPress plugin guidelines.
- [x] Complete tooling:
PHPCS + WPCS + custom standards
PHPStan static analysis
PHPUnit unit & integration tests
GitHub Actions CI (PHP 7.4–8.3, WP 6.5+)

## Installation
```bash
composer require wpmoo/wpmoo:dev-dev
```

## Development
- Run all checks: `composer check`
- Watch assets: `composer run watch`
- Run tests: `vendor/bin/phpunit`
- Generate code: `composer moo -- generate:field toggle`

## License

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License v2.0 or later.

See the full license at: https://spdx.org/licenses/GPL-2.0-or-later.html

Copyright © 2025 WPMoo.org
Built with ❤️ for the WordPress community.
