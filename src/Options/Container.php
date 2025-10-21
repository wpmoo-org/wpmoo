<?php
/**
 * Carbon Fields inspired container API for WPMoo options.
 *
 * @package WPMoo\Options
 */

namespace WPMoo\Options;

use InvalidArgumentException;
use WPMoo\Support\Str;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fluent container builder mirroring Carbon Fields usage.
 */
class Container {

	/**
	 * Whether the container queue has been bootstrapped.
	 *
	 * @var bool
	 */
	protected static $booted = false;

	/**
	 * Queued containers awaiting registration.
	 *
	 * @var array<string, Container>
	 */
	protected static $queue = array();

	/**
	 * Registered instances keyed by container id.
	 *
	 * @var array<string, Container>
	 */
	protected static $instances = array();

	/**
	 * Container type.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Internal identifier.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Page title.
	 *
	 * @var string
	 */
	protected $page_title;

	/**
	 * Menu title.
	 *
	 * @var string
	 */
	protected $menu_title;

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	protected $menu_slug;

	/**
	 * Stored option key.
	 *
	 * @var string
	 */
	protected $option_key;

	/**
	 * Required capability.
	 *
	 * @var string
	 */
	protected $capability = 'manage_options';

	/**
	 * Parent menu slug.
	 *
	 * @var string
	 */
	protected $parent_slug = '';

	/**
	 * Menu icon.
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Menu position.
	 *
	 * @var int|null
	 */
	protected $position = null;

	/**
	 * Registered sections keyed by id.
	 *
	 * @var array<string, Section>
	 */
	protected $sections = array();

	/**
	 * Registered Page instance after boot.
	 *
	 * @var Page|null
	 */
	protected $page = null;

	/**
	 * Whether the container has been registered.
	 *
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * Default section slug used when add_fields() is called.
	 *
	 * @var string
	 */
	protected $default_section_id = 'general';

	/**
	 * Default section label.
	 *
	 * @var string
	 */
	protected $default_section_title = '';

	/**
	 * Constructor.
	 *
	 * @param string $type  Container type.
	 * @param string $id    Container id.
	 * @param string $title Page title.
	 */
	protected function __construct( string $type, string $id, string $title ) {
		$this->type                  = $type;
		$this->id                    = $id;
		$this->page_title            = $title ?: ucwords( str_replace( array( '-', '_' ), ' ', $id ) );
		$this->menu_title            = $this->page_title;
		$this->menu_slug             = $id;
		$this->option_key            = $id;
		$default_section_title = function_exists( '__' ) ? __( 'General', 'wpmoo' ) : 'General';
		$this->default_section_title = $default_section_title;

		if ( in_array( $type, array( 'theme-options', 'theme_options' ), true ) ) {
			$this->parent_slug = 'themes.php';
		}
	}

	/**
	 * Factory for building a container instance.
	 *
	 * @param string      $type       Container type.
	 * @param string      $id_or_name Identifier or human title.
	 * @param string|null $name       Optional explicit title.
	 * @return static
	 */
	public static function create( string $type, string $id_or_name, ?string $name = null ): self {
		$type = static::normalize_type( $type );

		if ( null === $name || '' === $name ) {
			$title = $id_or_name;
			$id    = static::normalize_id( $id_or_name );
		} else {
			$id    = static::normalize_id( $id_or_name );
			$title = $name;
		}

		if ( '' === $id ) {
			$id = static::normalize_id( $type . '-' . $title );
		}

		if ( isset( static::$instances[ $id ] ) ) {
			return static::$instances[ $id ];
		}

		$container = new self( $type, $id, $title );

		static::$instances[ $id ] = $container;
		static::queue( $container );

		return $container;
	}

	/**
	 * Backwards compatible alias of create().
	 *
	 * @param string      $type       Container type.
	 * @param string      $id_or_name Identifier or human title.
	 * @param string|null $name       Optional explicit title.
	 * @return static
	 */
	public static function make( string $type, string $id_or_name, ?string $name = null ): self {
		return static::create( $type, $id_or_name, $name );
	}

	/**
	 * Retrieve a registered container.
	 *
	 * @param string $id Container id.
	 * @return static|null
	 */
	public static function get( string $id ) {
		return isset( static::$instances[ $id ] ) ? static::$instances[ $id ] : null;
	}

	/**
	 * Queue a container for registration.
	 *
	 * @param Container $container Container instance.
	 * @return void
	 */
	protected static function queue( Container $container ) {
		static::boot();

		static::$queue[ spl_object_hash( $container ) ] = $container;
	}

	/**
	 * Boot the registration hooks.
	 *
	 * @return void
	 */
	protected static function boot() {
		if ( static::$booted ) {
			return;
		}

		static::$booted = true;

		if ( function_exists( 'add_action' ) ) {
			add_action( 'init', array( static::class, 'register_queued' ), 12 );
			return;
		}

		static::register_queued();
	}

	/**
	 * Register queued containers.
	 *
	 * @return void
	 */
	public static function register_queued() {
		if ( empty( static::$queue ) ) {
			return;
		}

		$queued         = static::$queue;
		static::$queue = array();

		foreach ( $queued as $hash => $container ) {
			unset( static::$queue[ $hash ] );
			$container->register();
		}
	}

	/**
	 * Register the container via the Options facade.
	 *
	 * @return Page
	 */
	public function register(): Page {
		if ( $this->registered && $this->page ) {
			return $this->page;
		}

		$config          = $this->to_config();
		$this->page      = Options::register( $config );
		$this->registered = true;

		return $this->page;
	}

	/**
	 * Assign a different option key.
	 *
	 * @param string $key Option key.
	 * @return $this
	 */
	public function set_option_key( string $key ): self {
		$key = $this->sanitize_key( $key );

		if ( '' === $key ) {
			throw new InvalidArgumentException( 'Option key cannot be empty.' );
		}

		$this->option_key = $key;

		return $this;
	}

	/**
	 * Alias for set_option_key().
	 *
	 * @param string $key Option key.
	 * @return $this
	 */
	public function optionKey( string $key ): self {
		return $this->set_option_key( $key );
	}

	/**
	 * Update the page title.
	 *
	 * @param string $title Page title.
	 * @return $this
	 */
	public function set_page_title( string $title ): self {
		$this->page_title            = $title;
		$this->default_section_title = $title;

		return $this;
	}

	/**
	 * Alias for set_page_title().
	 *
	 * @param string $title Page title.
	 * @return $this
	 */
	public function pageTitle( string $title ): self {
		return $this->set_page_title( $title );
	}

	/**
	 * Update the menu title.
	 *
	 * @param string $title Menu title.
	 * @return $this
	 */
	public function set_page_menu_title( string $title ): self {
		$this->menu_title = $title;

		return $this;
	}

	/**
	 * Alias for set_page_menu_title().
	 *
	 * @param string $title Title.
	 * @return $this
	 */
	public function menuTitle( string $title ): self {
		return $this->set_page_menu_title( $title );
	}

	/**
	 * Update the menu slug.
	 *
	 * @param string $slug Menu slug.
	 * @return $this
	 */
	public function set_page_slug( string $slug ): self {
		$slug = static::normalize_id( $slug );

		if ( '' === $slug ) {
			throw new InvalidArgumentException( 'Menu slug cannot be empty.' );
		}

		$this->menu_slug = $slug;

		return $this;
	}

	/**
	 * Alias for set_page_slug().
	 *
	 * @param string $slug Slug.
	 * @return $this
	 */
	public function menuSlug( string $slug ): self {
		return $this->set_page_slug( $slug );
	}

	/**
	 * Set parent menu slug.
	 *
	 * @param string $parent Parent slug.
	 * @return $this
	 */
	public function set_page_parent( string $parent ): self {
		$this->parent_slug = $parent;

		return $this;
	}

	/**
	 * Alias for set_page_parent().
	 *
	 * @param string $parent Parent slug.
	 * @return $this
	 */
	public function parentSlug( string $parent ): self {
		return $this->set_page_parent( $parent );
	}

	/**
	 * Update capability requirement.
	 *
	 * @param string $capability Capability.
	 * @return $this
	 */
	public function set_page_capability( string $capability ): self {
		$this->capability = $capability;

		return $this;
	}

	/**
	 * Alias for set_page_capability().
	 *
	 * @param string $capability Capability.
	 * @return $this
	 */
	public function capability( string $capability ): self {
		return $this->set_page_capability( $capability );
	}

	/**
	 * Set menu icon.
	 *
	 * @param string $icon Icon identifier.
	 * @return $this
	 */
	public function set_page_icon( string $icon ): self {
		$this->icon = $icon;

		return $this;
	}

	/**
	 * Alias for set_page_icon().
	 *
	 * @param string $icon Icon identifier.
	 * @return $this
	 */
	public function icon( string $icon ): self {
		return $this->set_page_icon( $icon );
	}

	/**
	 * Update menu position.
	 *
	 * @param int $position Menu position.
	 * @return $this
	 */
	public function set_page_position( int $position ): self {
		$this->position = $position;

		return $this;
	}

	/**
	 * Alias for set_page_position().
	 *
	 * @param int $position Position.
	 * @return $this
	 */
	public function position( int $position ): self {
		return $this->set_page_position( $position );
	}

	/**
	 * Retrieve (or create) a section for fluent configuration.
	 *
	 * @param string $id Section identifier.
	 * @param string $title Optional title.
	 * @param string $description Optional description.
	 * @return Section
	 */
	public function section( string $id, string $title = '', string $description = '' ): Section {
		$section = Section::make( $id, '' !== $title ? $title : $id, $description );
		$this->sections[ $section->id() ] = $section;

		return $section;
	}

	/**
	 * Add one or multiple fields to the default section.
	 *
	 * @param array<int, mixed>|mixed $fields Field definitions.
	 * @return $this
	 */
	public function add_fields( $fields ): self {
		$section = $this->ensure_section( $this->default_section_id, $this->default_section_title );

		if ( is_array( $fields ) ) {
			foreach ( $fields as $field ) {
				$section->add_field( $field );
			}

			return $this;
		}

		$section->add_field( $fields );

		return $this;
	}

	/**
	 * Add a single field to the default section.
	 *
	 * @param mixed $field Field definition.
	 * @return $this
	 */
	public function add_field( $field ): self {
		return $this->add_fields( $field );
	}

	/**
	 * Add a new section definition.
	 *
	 * @param Section $section Section instance.
	 * @return $this
	 */
	public function add_section( Section $section ): self {
		$this->sections[ $section->id() ] = $section;

		return $this;
	}

	/**
	 * Add multiple sections.
	 *
	 * @param array<int, Section> $sections List of sections.
	 * @return $this
	 */
	public function add_sections( array $sections ): self {
		foreach ( $sections as $section ) {
			$this->add_section( $section );
		}

		return $this;
	}

	/**
	 * Convenience alias mirroring Carbon Fields' add_tab().
	 *
	 * @param string         $title  Tab title.
	 * @param array<int,mixed> $fields Field definitions.
	 * @param string         $id     Optional identifier.
	 * @return $this
	 */
	public function add_tab( string $title, array $fields, string $id = '' ): self {
		$section = Section::make( '' !== $id ? $id : $title, $title );
		$section->add_fields( $fields );

		return $this->add_section( $section );
	}

	/**
	 * Retrieve the registered page.
	 *
	 * @return Page|null
	 */
	public function page(): ?Page {
		return $this->page;
	}

	/**
	 * Build a configuration array for Options::register().
	 *
	 * @return array<string, mixed>
	 */
	protected function to_config(): array {
		$sections = array();

		foreach ( $this->sections as $section ) {
			if ( $section->has_fields() ) {
				$sections[] = $section->toArray();
			}
		}

		if ( empty( $sections ) ) {
			$default_section = $this->ensure_section( $this->default_section_id, $this->default_section_title );

			if ( $default_section->has_fields() ) {
				$sections[] = $default_section->toArray();
			}
		}

		return array(
			'page_title'  => $this->page_title,
			'menu_title'  => $this->menu_title,
			'menu_slug'   => $this->menu_slug,
			'option_key'  => $this->option_key,
			'capability'  => $this->capability,
			'parent_slug' => $this->parent_slug,
			'position'    => $this->position,
			'icon'        => $this->icon,
			'sections'    => $sections,
		);
	}

	/**
	 * Ensure a section exists (creating as needed).
	 *
	 * @param string $id    Section id.
	 * @param string $title Section title.
	 * @return Section
	 */
	protected function ensure_section( string $id, string $title ): Section {
		$id = static::normalize_id( $id );

		if ( isset( $this->sections[ $id ] ) ) {
			return $this->sections[ $id ];
		}

		$section = Section::make( $id, $title );
		$this->sections[ $id ] = $section;

		return $section;
	}

	/**
	 * Normalize a container type.
	 *
	 * @param string $type Raw type.
	 * @return string
	 */
	protected static function normalize_type( string $type ): string {
		$type = preg_replace( '/([a-z])([A-Z])/', '$1-$2', $type );
		$type = strtolower( $type );
		$type = str_replace( '_', '-', $type );

		return $type;
	}

	/**
	 * Normalize identifiers into slugs.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected static function normalize_id( string $value ): string {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$slug = Str::slug( $value );

		return '' !== $slug ? $slug : $value;
	}

	/**
	 * Sanitize option keys.
	 *
	 * @param string $value Raw key.
	 * @return string
	 */
	protected function sanitize_key( string $value ): string {
		$value = trim( (string) $value );

		if ( function_exists( 'sanitize_key' ) ) {
			return sanitize_key( $value );
		}

		$value = strtolower( $value );
		$value = preg_replace( '/[^a-z0-9_\-]/', '', $value );

		return $value;
	}
}
