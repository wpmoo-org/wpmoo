<?php
/**
 * Lightweight query builder for WPMoo models.
 *
 * WPMoo — WordPress Micro Object-Oriented Framework.
 * Github: https://github.com/wpmoo/wpmoo
 * Website: https://wpmoo.org
 * License: GNU General Public License v3.0
 *
 * @package WPMoo\Database
 * @since 0.1.0
 */

namespace WPMoo\Database;

/**
 * Builds simple SELECT queries.
 */
class Query {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Selected columns.
	 *
	 * @var array<int, string>
	 */
	protected $select = array( '*' );

	/**
	 * Where clauses.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	protected $wheres = array();

	/**
	 * Order clauses.
	 *
	 * @var array<int, string>
	 */
	protected $orders = array();

	/**
	 * Limit value.
	 *
	 * @var int|null
	 */
	protected $limit = null;

	/**
	 * Database connection.
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->connection = new Connection();
	}

	/**
	 * Create a new query instance for a given table.
	 *
	 * @param string $table Table name.
	 * @return static
	 */
	public static function table( $table ) {
		$instance        = new self();
		$instance->table = $table;

		return $instance;
	}

	/**
	 * Set the columns to select.
	 *
	 * @param array<int, string>|string $columns Column list.
	 * @return $this
	 */
	public function select( $columns = array( '*' ) ) {
		$this->select = (array) $columns;

		return $this;
	}

	/**
	 * Add a where clause.
	 *
	 * @param string $column   Column name.
	 * @param mixed  $value    Value for comparison.
	 * @param string $operator Comparison operator.
	 * @return $this
	 */
	public function where( $column, $value, $operator = '=' ) {
		$this->wheres[] = array( $column, $operator, $value );

		return $this;
	}

	/**
	 * Add an order by clause.
	 *
	 * @param string $column    Column name.
	 * @param string $direction Sort direction.
	 * @return $this
	 */
	public function orderBy( $column, $direction = 'ASC' ) {
		$this->orders[] = $column . ' ' . $direction;

		return $this;
	}

	/**
	 * Set a limit on the query.
	 *
	 * @param int $limit Maximum number of rows.
	 * @return $this
	 */
	public function limit( $limit ) {
		$this->limit = (int) $limit;

		return $this;
	}

	/**
	 * Execute the query and return the results.
	 *
	 * @return array<int, object>|null
	 */
	public function get() {
		$sql = $this->to_sql();

		return $this->connection->get_results( $sql );
	}

	/**
	 * Generate the SQL statement.
	 *
	 * @return string
	 */
	protected function to_sql() {
		$sql = 'SELECT ' . implode( ', ', $this->select ) . ' FROM ' . $this->table;

		if ( ! empty( $this->wheres ) ) {
			$parts = array();

			foreach ( $this->wheres as $where ) {
				list( $column, $operator, $value ) = $where;

				if ( function_exists( 'esc_sql' ) ) {
					$column   = esc_sql( $column );
					$operator = esc_sql( $operator );
					$value    = esc_sql( $value );
				} else {
					$column   = addslashes( $column );
					$operator = addslashes( $operator );
					$value    = addslashes( (string) $value );
				}

				$parts[] = "{$column} {$operator} '{$value}'";
			}

			$sql .= ' WHERE ' . implode( ' AND ', $parts );
		}

		if ( ! empty( $this->orders ) ) {
			$sql .= ' ORDER BY ' . implode( ', ', $this->orders );
		}

		if ( $this->limit ) {
			$sql .= ' LIMIT ' . (int) $this->limit;
		}

		return $sql;
	}
}
