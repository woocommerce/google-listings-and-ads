<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAccountQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsAccountQuery extends AdsQuery {

	/**
	 * Query constructor.
	 */
	public function __construct() {
		parent::__construct( 'customer' );
		$this->columns( [ 'customer.id', 'customer.descriptive_name', 'customer.manager', 'customer.test_account' ] );
	}
}
