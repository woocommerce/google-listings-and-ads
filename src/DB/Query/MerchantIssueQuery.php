<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantIssueQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Query
 */
class MerchantIssueQuery extends Query {

	/**
	 * Query constructor.
	 *
	 * @param wpdb               $wpdb
	 * @param MerchantIssueTable $table
	 */
	public function __construct( wpdb $wpdb, MerchantIssueTable $table ) {
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
		return $value;
	}
}
