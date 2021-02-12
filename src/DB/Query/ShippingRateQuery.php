<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\ShippingRateTable;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShippingRateQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Query
 */
class ShippingRateQuery extends Query {

	/**
	 * Query constructor.
	 *
	 * @param wpdb              $wpdb
	 * @param ShippingRateTable $table
	 */
	public function __construct( wpdb $wpdb, ShippingRateTable $table ) {
		parent::__construct( $wpdb, $table );
	}
}
