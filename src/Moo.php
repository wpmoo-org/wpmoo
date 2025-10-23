<?php
/**
 * Procedural-friendly facade for defining WPMoo structures.
 *
 * @package WPMoo
 */

namespace WPMoo;

use InvalidArgumentException;
use WPMoo\Moo\MetaboxHandle;
use WPMoo\Moo\PageHandle;
use WPMoo\Moo\SectionHandle;
use WPMoo\Metabox\Metabox;
use WPMoo\Options\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Entry point for fluent definitions (pages, sections, containers, metaboxes).
 */
class Moo {

	/**
	 * Registered page handles keyed by identifier.
	 *
	 * @var array<string, PageHandle>
	 */
	protected static $pages = array();

	/**
	 * Pending sections waiting for their parent page.
	 *
	 * @var array<string, array<int, SectionHandle>>
	 */
	protected static $pending_sections = array();

	/**
	 * Pending sections waiting for their parent metabox.
	 *
	 * @var array<string, array<int, SectionHandle>>
	 */
	protected static $pending_metabox_sections = array();

	/**
	 * Registered metabox handles keyed by identifier.
	 *
	 * @var array<string, MetaboxHandle>
	 */
	protected static $metaboxes = array();

	/**
	 * Create a fluent definition.
	 *
	 * @param string $type        Definition type (page, container, section, metabox).
	 * @param string $id          Identifier.
	 * @param string $title       Optional title.
	 * @param string $description Optional description.
	 * @return mixed
	 */
	public static function make( string $type, string $id, string $title = '', string $description = '' ) {
		$type = strtolower( trim( $type ) );

		switch ( $type ) {
			case 'page':
			case 'container':
				return self::container( $id, $title, $description );

			case 'section':
				return self::section( $id, $title, $description );

			case 'metabox':
				return self::metabox( $id, $title, $description );

			case 'panel':
				return self::panel( $id, $title, $description );
		}

		throw new InvalidArgumentException(
			sprintf(
				esc_html__( 'Unsupported Moo::make() type "%s".', 'wpmoo' ),
				esc_html( $type )
			)
		);
	}

	/**
	 * Fetch an existing page handle, or create one when metadata is provided.
	 *
	 * @param string      $id          Page identifier.
	 * @param string|null $title       Optional title to create/update.
	 * @param string|null $description Optional description to create/update.
	 * @return PageHandle|null Returns null when the page has not been created yet and no metadata is supplied.
	 */
	public static function page( string $id, ?string $title = null, ?string $description = null ): ?PageHandle {
		if ( null !== $title || null !== $description ) {
			return self::create_page( $id, $title ?? '', $description ?? '' );
		}

		return isset( self::$pages[ $id ] ) ? self::$pages[ $id ] : null;
	}

	/**
	 * Define or retrieve a container (alias of page).
	 *
	 * @param string $id          Identifier.
	 * @param string $title       Title.
	 * @param string $description Description.
	 * @return PageHandle
	 */
	public static function container( string $id, string $title = '', string $description = '' ): PageHandle {
		return self::create_page( $id, $title, $description );
	}

	/**
	 * Create a new section handle.
	 *
	 * @param string $id          Section identifier.
	 * @param string $title       Section title.
	 * @param string $description Section description.
	 * @return SectionHandle
	 */
	public static function section( string $id, string $title = '', string $description = '' ): SectionHandle {
		return self::create_section( $id, $title, $description );
	}

	/**
	 * Create (or fetch existing) metabox handle.
	 *
	 * @param string $id          Metabox identifier.
	 * @param string $title       Optional title.
	 * @param string $description Optional description.
	 * @return MetaboxHandle
	 */
	public static function metabox( string $id, string $title = '', string $description = '' ): MetaboxHandle {
		if ( isset( self::$metaboxes[ $id ] ) ) {
			return self::$metaboxes[ $id ];
		}

		$builder = Metabox::create( $id );
		$handle  = new MetaboxHandle( $id, $builder );

		if ( '' !== $title ) {
			$handle->title( $title );
		}

		if ( '' !== $description ) {
			$handle->description( $description );
		}

		self::$metaboxes[ $id ] = $handle;

		if ( isset( self::$pending_metabox_sections[ $id ] ) ) {
			foreach ( self::$pending_metabox_sections[ $id ] as $section ) {
				$handle->attachSection( $section );
			}

			unset( self::$pending_metabox_sections[ $id ] );
		}

		$handle->registerOnInit();

		return $handle;
	}

	/**
	 * Define a metabox with panel layout enabled.
	 *
	 * @param string $id          Metabox identifier.
	 * @param string $title       Optional title.
	 * @param string $description Optional description.
	 * @return MetaboxHandle
	 */
	public static function panel( string $id, string $title = '', string $description = '' ): MetaboxHandle {
		$handle = self::metabox( $id, $title, $description );
		$handle->panel();

		return $handle;
	}

	/**
	 * Ensure a section is associated with the given page.
	 *
	 * @param SectionHandle $section Section handle.
	 * @param string        $page_id Parent page identifier.
	 * @return void
	 */
	public static function assignSectionToPage( SectionHandle $section, string $page_id ): void {
		$page = self::page( $page_id );

		if ( $page ) {
			$section->attach( $page );
			return;
		}

		if ( ! isset( self::$pending_sections[ $page_id ] ) ) {
			self::$pending_sections[ $page_id ] = array();
		}

		self::$pending_sections[ $page_id ][] = $section;
	}

	/**
	 * Ensure a section is associated with the given metabox.
	 *
	 * @param SectionHandle $section    Section handle.
	 * @param string        $metabox_id Parent metabox identifier.
	 * @return void
	 */
	public static function assignSectionToMetabox( SectionHandle $section, string $metabox_id ): void {
		if ( isset( self::$metaboxes[ $metabox_id ] ) ) {
			self::$metaboxes[ $metabox_id ]->attachSection( $section );
			return;
		}

		if ( ! isset( self::$pending_metabox_sections[ $metabox_id ] ) ) {
			self::$pending_metabox_sections[ $metabox_id ] = array();
		}

		self::$pending_metabox_sections[ $metabox_id ][] = $section;
	}

	/**
	 * Internal: create (or return existing) page handle.
	 *
	 * @param string $id          Identifier.
	 * @param string $title       Title.
	 * @param string $description Description.
	 * @return PageHandle
	 */
	protected static function create_page( string $id, string $title, string $description ): PageHandle {
		if ( isset( self::$pages[ $id ] ) ) {
			return self::$pages[ $id ];
		}

		$builder = Options::create( $id );

		if ( '' !== $title ) {
			$builder->pageTitle( $title )->menuTitle( $title );
		}

		if ( '' !== $description ) {
			$builder->config( 'description', $description );
		}

		$page = new PageHandle( $id, $builder );

		self::$pages[ $id ] = $page;

		// Attach pending sections waiting on this page.
		if ( isset( self::$pending_sections[ $id ] ) ) {
			foreach ( self::$pending_sections[ $id ] as $section ) {
				$page->attachSection( $section );
			}

			unset( self::$pending_sections[ $id ] );
		}

		return $page;
	}

	/**
	 * Internal: create section handle.
	 *
	 * @param string $id          Identifier.
	 * @param string $title       Title.
	 * @param string $description Description.
	 * @return SectionHandle
	 */
	protected static function create_section( string $id, string $title, string $description ): SectionHandle {
		return new SectionHandle( $id, $title, $description );
	}
}
