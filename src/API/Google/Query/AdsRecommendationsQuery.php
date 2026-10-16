<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsRecommendationsQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsRecommendationsQuery extends AdsQuery {

	/**
	 * AdsRecommendationsQuery constructor.
	 */
	public function __construct() {
		parent::__construct( 'recommendation' );
		$this->set_initial_columns();
	}

	/**
	 * Set the initial columns for this query.
	 */
	protected function set_initial_columns() {
		$this->columns(
			[
				'recommendation_type'            => 'recommendation.type',
				'recommendation_resource_name'   => 'recommendation.resource_name',
				'recommendation_campaign_id'     => 'campaign.id',
				'recommendation_campaign_name'   => 'campaign.name',
				'recommendation_campaign_status' => 'campaign.status',
				'recommendation_customer_id'     => 'customer.id',
			]
		);
	}
}
