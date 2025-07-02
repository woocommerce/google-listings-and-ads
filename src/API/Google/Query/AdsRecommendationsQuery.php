<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsRecommendationsQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsRecommendationsQuery extends AdsQuery {

	/**
	 * Client which handles the query.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client = null;

	/**
	 * Ads Account ID.
	 *
	 * @var int
	 */
	protected $id = null;

	/**
	 * AdsRecommendationsQuery constructor.
	 */
	public function __construct() {
		parent::__construct( 'recommendation' );
		$this->set_initial_columns();
		$this->where( 'recommendation.type', 'IMPROVE_PERFORMANCE_MAX_AD_STRENGTH' );
	}

	/**
	 * Set the client which will handle the query.
	 *
	 * @param GoogleAdsClient $client Client instance.
	 * @param int             $id     Account ID.
	 *
	 * @return QueryInterface
	 * @throws InvalidProperty If the ID is empty.
	 */
	public function set_client( GoogleAdsClient $client, int $id ): QueryInterface {
		if ( empty( $id ) ) {
			throw InvalidProperty::not_null( get_class( $this ), 'id' );
		}

		$this->client = $client;
		$this->id     = $id;

		return $this;
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
