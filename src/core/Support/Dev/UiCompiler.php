<?php
/* phpcs:ignoreFile */
/**
 * Development-time SCSS compiler for wpmoo-ui.
 *
 * @package WPMoo\Support\Dev
 * @since 0.1.0
 */

namespace WPMoo\Support\Dev;

use WPMoo\Core\App;

if ( ! defined( 'ABSPATH' ) ) {
    wp_die();
}

/**
 * Compiles wpmoo-ui SCSS on the fly in development and serves from uploads.
 */
class UiCompiler {
    /**
     * Register filter hook if in a suitable dev context.
     *
     * @return void
     */
    public static function register(): void {
        if ( ! self::is_dev() ) {
            return;
        }

        if ( function_exists( 'add_filter' ) ) {
            add_filter( 'wpmoo_ui_css_url', array( self::class, 'maybe_override' ), 10, 1 );
        }
    }

    /**
     * Determine whether dev compilation should be enabled.
     *
     * @return bool
     */
    protected static function is_dev(): bool {
        $dev = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );

        if ( ! $dev ) {
            return false;
        }

        if ( function_exists( 'is_admin' ) && ! is_admin() ) {
            return false;
        }

        if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Filter callback: compile SCSS if needed and return uploads URL.
     *
     * @param string $url Original UI CSS URL.
     * @return string
     */
    public static function maybe_override( string $url ): string {
        // Avoid fatal if scssphp is not installed.
        if ( ! class_exists( '\\ScssPhp\\ScssPhp\\Compiler' ) ) {
            return $url;
        }

        $plugin_path = App::instance()->path();
        if ( '' === $plugin_path ) {
            return $url;
        }

        $base = rtrim( str_replace( '\\', '/', $plugin_path ), '/' ) . '/vendor/wpmoo/wpmoo-ui/';

        if ( ! is_dir( $base ) ) {
            return $url;
        }

        $src    = $base . 'scss/wpmoo.scss';
        $outdir = trailingslashit( wp_upload_dir()['basedir'] ) . 'wpmoo-ui';
        $outfile = $outdir . '/wpmoo.css';

        if ( ! file_exists( $src ) ) {
            return $url;
        }

        if ( ! is_dir( $outdir ) ) {
            if ( function_exists( 'wp_mkdir_p' ) ) {
                wp_mkdir_p( $outdir );
            } else {
                @mkdir( $outdir, 0755, true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            }
        }

        $need = ! file_exists( $outfile ) || ( filemtime( $src ) > filemtime( $outfile ) );
        $pico_index = $base . 'node_modules/@picocss/pico/scss/index.scss';
        if ( file_exists( $pico_index ) && file_exists( $outfile ) && filemtime( $pico_index ) > filemtime( $outfile ) ) {
            $need = true;
        }

        if ( $need ) {
            try {
                $scss = new \ScssPhp\ScssPhp\Compiler();
                // Compact for dev; use COMPRESSED to simulate prod output.
                $scss->setOutputStyle( \ScssPhp\ScssPhp\OutputStyle::COMPRESSED );
                $scss->setImportPaths( array(
                    $base . 'scss',
                    $base . 'node_modules',
                    $base . 'node_modules/@picocss/pico/scss',
                ) );

                $result = $scss->compileString( (string) file_get_contents( $src ), 'wpmoo-ui/wpmoo.scss' );
                file_put_contents( $outfile, '/* wpmoo-ui dev-compiled */' . "\n" . $result->getCss() );
            } catch ( \Throwable $e ) {
                // Compilation failed; log and fall back to original URL.
                if ( function_exists( 'error_log' ) ) {
                    error_log( 'WPMoo UI dev compile failed: ' . $e->getMessage() );
                }
                return $url;
            }
        }

        $uploads = wp_upload_dir();
        $version = file_exists( $outfile ) ? (string) filemtime( $outfile ) : (string) time();
        $dev_url = trailingslashit( $uploads['baseurl'] ) . 'wpmoo-ui/wpmoo.css?v=' . rawurlencode( $version );

        return $dev_url;
    }
}
