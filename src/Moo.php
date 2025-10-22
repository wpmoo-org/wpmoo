<?php
/**
 * Procedural-friendly facade for defining WPMoo structures.
 *
 * @package WPMoo
 */

namespace WPMoo;

use InvalidArgumentException;
use WPMoo\Moo\PageHandle;
use WPMoo\Moo\SectionHandle;
use WPMoo\Options\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Entry point for fluent definitions (pages, sections, containers).
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
	 * Create a fluent definition.
	 *
	 * @param string $type        Definition type (page, container, section).
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
				return self::create_page( $id, $title, $description );

			case 'section':
				return self::create_section( $id, $title, $description );
		}

		throw new InvalidArgumentException(
			sprintf( 'Unsupported Moo::make() type "%s".', $type )
		);
	}

	/**
	 * Fetch an existing page handle.
	 *
	 * @param string $id Page identifier.
	 * @return PageHandle|null
	 */
	public static function page( string $id ): ?PageHandle {
		return isset( self::$pages[ $id ] ) ? self::$pages[ $id ] : null;
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

