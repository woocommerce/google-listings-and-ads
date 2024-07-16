<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsCampaignQuery extends AdsQuery {

	use ReportQueryTrait;


	/**
	 * Query constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( $args = [] ) {
		parent::__construct( 'campaign' );
		$this->columns(
			[
				'campaign.id',
				'campaign.name',
				'campaign.status',
				'campaign.advertising_channel_type',
				'campaign.shopping_setting.feed_label',
				'campaign_budget.amount_micros',
			]
		);

		$this->handle_query_args( $args );
	}
}
