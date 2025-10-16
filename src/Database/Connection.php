<?php
/**
 * Thin wrapper around the global $wpdb object.
 *
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Database
 * @since 0.1.0
 */

namespace WPMoo\Database;

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
	 * @param string               $query SQL query with placeholders.
	 * @param array<int, mixed>    $args  Values to replace placeholders.
	 * @return string
	 */
	public function prepare( $query, $args = array() ) {
		if ( empty( $args ) ) {
			return $query;
		}

		return $this->wpdb->prepare( $query, $args );
	}

	/**
	 * Execute an SQL query.
	 *
	 * @param string $query SQL query.
	 * @return int|false
	 */
	public function query( $query ) {
		return $this->wpdb->query( $query );
	}

	/**
	 * Retrieve multiple rows from the database.
	 *
	 * @param string $query SQL query.
	 * @return array<int, object>|null
	 */
	public function get_results( $query ) {
		return $this->wpdb->get_results( $query );
	}

	/**
	 * Insert a record into the database.
	 *
	 * @param string              $table Table name.
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
