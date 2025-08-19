<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\AdsRecommendationsTable;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsRecommendationsQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Query
 */
class AdsRecommendationsQuery extends Query {

	/**
	 * AdsRecommendationsQuery constructor.
	 *
	 * @param wpdb                    $wpdb
	 * @param AdsRecommendationsTable $table
	 */
	public function __construct( wpdb $wpdb, AdsRecommendationsTable $table ) {
		parent::__construct( $wpdb, $table );
		$this->table = $table;
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

	/**
	 * Reload merchant price benchmarks data.
	 *
	 * @return void
	 */
	public function reload_data(): void {
		if ( $this->table->exists() ) {
			$this->table->truncate();
		}
	}
}
