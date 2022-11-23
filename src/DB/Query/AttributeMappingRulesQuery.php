<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\AttributeMappingRulesTable;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class defining the queries for the Attribute Mapping Rules Table
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Query
 */
class AttributeMappingRulesQuery extends Query {

	/**
	 * Query constructor.
	 *
	 * @param wpdb                       $wpdb
	 * @param AttributeMappingRulesTable $table
	 */
	public function __construct( wpdb $wpdb, AttributeMappingRulesTable $table ) {
		parent::__construct( $wpdb, $table );
	}

	/**
	 * Sanitize a value for a given column before inserting it into the DB.
	 *
	 * @param string $column The column name.
	 * @param mixed  $value  The value to sanitize.
	 *
	 * @return mixed The sanitized value.
	 */
	protected function sanitize_value( string $column, $value ) {
		if ( $column === 'attribute' || $column === 'source' ) {
			return sanitize_text_field( $value );
		}

		if ( $column === 'categories' && is_null( $value ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Gets a specific rule from Database
	 *
	 * @param int $rule_id The rule ID to get from Database
	 * @return array The rule from database
	 */
	public function get_rule( int $rule_id ): array {
		return $this->where( 'id', $rule_id )->get_row();
	}
}
