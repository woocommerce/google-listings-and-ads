<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAppliedIncentiveQuery
 *
 * Lists the incentives applied to the Google Ads account.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsAppliedIncentiveQuery extends AdsQuery {

	/**
	 * AdsAppliedIncentiveQuery constructor.
	 */
	public function __construct() {
		parent::__construct( 'applied_incentive' );
		$this->columns( [ 'applied_incentive.resource_name' ] );
	}
}
