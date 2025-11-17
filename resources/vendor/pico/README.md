Place Pico CSS here for scoped build.

Expected file tree:

- assets/vendor/pico/css/pico.scoped.css
- assets/vendor/pico/LICENSE (optional, MIT)

Gulp task `pico:scope` will read `pico.scoped.css`, rename the scope to `:where(.wpmoo)` and write `assets/css/pico-wpmoo.css`.

