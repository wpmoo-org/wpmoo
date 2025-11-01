<?php
/**
 * Active record style base model.
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
 * Provides simple CRUD helpers for database tables.
 *
 * @phpstan-consistent-constructor
 */
abstract class Model {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Primary key column.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * Attribute store.
	 *
	 * @var array<string, mixed>
	 */
	protected $attributes = array();

	/**
	 * Shared database connection.
	 *
	 * @var Connection
	 */
	protected static $connection;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $attributes Initial attributes.
	 */
	public function __construct( $attributes = array() ) {
		if ( ! self::$connection ) {
			self::$connection = new Connection();
		}

		$this->fill( $attributes );
	}

	/**
	 * Mass-assign attributes.
	 *
	 * @param array<string, mixed> $attributes Attribute map.
	 * @return void
	 */
	public function fill( $attributes ) {
		foreach ( $attributes as $key => $value ) {
			$this->attributes[ $key ] = $value;
		}
	}

	/**
	 * Magic getter for attributes.
	 *
	 * @param string $key Attribute key.
	 * @return mixed|null
	 */
	public function __get( $key ) {
		return isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : null;
	}

	/**
	 * Magic setter for attributes.
	 *
	 * @param string $key   Attribute key.
	 * @param mixed  $value Value to assign.
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->attributes[ $key ] = $value;
	}

	/**
	 * Retrieve a model by its primary key.
	 *
	 * @param mixed $id Identifier value.
	 * @return static|null
	 */
	public static function find( $id ) {
		$instance = new static();

		$rows = Query::table( $instance->table )
			->where( $instance->primaryKey, $id )
			->limit( 1 )
			->get();

		if ( empty( $rows ) ) {
			return null;
		}

		return new static( (array) $rows[0] );
	}

	/**
	 * Retrieve all rows for the model.
	 *
	 * @return static[]
	 */
	public static function all() {
		$instance = new static();
		$rows     = Query::table( $instance->table )->get();
		$results  = array();

		foreach ( $rows as $row ) {
			$results[] = new static( (array) $row );
		}

		return $results;
	}

	/**
	 * Begin a query against the model table.
	 *
	 * @param string $column   Column name.
	 * @param mixed  $value    Value for comparison.
	 * @param string $operator Comparison operator.
	 * @return Query
	 */
	public static function where( $column, $value, $operator = '=' ) {
		$instance = new static();

		return Query::table( $instance->table )->where( $column, $value, $operator );
	}

	/**
	 * Persist the model to the database.
	 *
	 * @return int|bool Primary key value or success flag.
	 */
	public function save() {
		$primary_key = $this->primaryKey;

		if ( isset( $this->attributes[ $primary_key ] ) ) {
			$id   = $this->attributes[ $primary_key ];
			$data = $this->attributes;

			unset( $data[ $primary_key ] );

			return self::$connection->update( $this->table, $data, array( $primary_key => $id ) );
		}

		$id = self::$connection->insert( $this->table, $this->attributes );

		if ( $id ) {
			$this->attributes[ $primary_key ] = $id;
		}

		return $id;
	}

	/**
	 * Delete the model from the database.
	 *
	 * @return bool
	 */
	public function delete() {
		$primary_key = $this->primaryKey;

		if ( ! isset( $this->attributes[ $primary_key ] ) ) {
			return false;
		}

		return (bool) self::$connection->delete(
			$this->table,
			array( $primary_key => $this->attributes[ $primary_key ] )
		);
	}
}
