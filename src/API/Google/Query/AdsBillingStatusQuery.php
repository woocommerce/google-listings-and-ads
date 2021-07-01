<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsBillingStatusQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsBillingStatusQuery extends AdsQuery {

	/**
	 * Query constructor.
	 */
	public function __construct() {
		parent::__construct( 'billing_setup' );
		$this->columns(
			[
				'status'          => 'billing_setup.status',
				'start_date_time' => 'billing_setup.start_date_time',
			]
		);
		$this->set_order( 'start_date_time', 'DESC' );
	}
}
