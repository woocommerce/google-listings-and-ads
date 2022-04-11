<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignCriterionQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsCampaignCriterionQuery extends AdsQuery {

	/**
	 * Query constructor.
	 */
	public function __construct() {
		parent::__construct( 'campaign_criterion' );
		$this->columns(
			[
				'campaign.id',
				'campaign_criterion.location.geo_target_constant',
			]
		);
	}
}
