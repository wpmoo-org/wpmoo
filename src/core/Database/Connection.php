<?php
/**
 * Thin wrapper around the global $wpdb object.
 *
 * @package WPMoo\Database
 * @since 0.1.0
 * @link https://wpmoo.org WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html GPL-2.0-or-later
 */

namespace WPMoo\Database;

if ( ! defined( 'ABSPATH' ) ) {
	wp_die();
}

/**
 * Provides helper methods for WordPress database access.
 */
class Connection {

	/**
	 * WordPress database abstraction.
	 *
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	/**
	 * Prepare an SQL query.
	 *
	 * @param string            $query SQL query with placeholders.
	 * @param array<int, mixed> $args  Values to replace placeholders.
	 * @return string
	 */
	public function prepare( $query, $args = array() ) {
		if ( empty( $args ) ) {
			return $query;
		}

        // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder,WordPress.DB.PreparedSQL.NotPrepared -- Input queries are constructed by callers; passing bound values to wpdb->prepare.
		return $this->wpdb->prepare( $query, $args );
	}

	/**
	 * Execute an SQL query.
	 *
	 * @param string            $query SQL query.
	 * @param array<int, mixed> $args  Values to replace placeholders.
	 * @return int|false
	 */
	public function query( $query, $args = array() ) {
		$query = $this->prepare( $query, $args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Queries are routed through $this->prepare(), which proxies to $wpdb->prepare().
		return $this->wpdb->query( $query );
	}

	/**
	 * Retrieve multiple rows from the database.
	 *
	 * @param string            $query SQL query.
	 * @param array<int, mixed> $args  Values to replace placeholders.
	 * @return array<int, object>|null
	 */
	public function get_results( $query, $args = array() ) {
		$query = $this->prepare( $query, $args );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Queries are routed through $this->prepare(), which proxies to $wpdb->prepare().
		return $this->wpdb->get_results( $query );
	}

	/**
	 * Insert a record into the database.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $data  Column data.
	 * @return int|false
	 */
	public function insert( $table, $data ) {
		$result = $this->wpdb->insert( $table, $data );

		return $result ? $this->wpdb->insert_id : false;
	}

	/**
	 * Update a record in the database.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $data  Column data.
	 * @param array<string, mixed> $where Where clause.
	 * @return int|false
	 */
	public function update( $table, $data, $where ) {
		return $this->wpdb->update( $table, $data, $where );
	}

	/**
	 * Delete records from the database.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $where Where clause.
	 * @return int|false
	 */
	public function delete( $table, $where ) {
		return $this->wpdb->delete( $table, $where );
	}

	/**
	 * Retrieve the WordPress table prefix.
	 *
	 * @return string
	 */
	public function prefix() {
		return $this->wpdb->prefix;
	}

	/**
	 * Retrieve the last database error.
	 *
	 * @return string
	 */
	public function last_error() {
		return $this->wpdb->last_error;
	}
}
