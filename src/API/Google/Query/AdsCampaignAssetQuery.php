<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignAssetQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsCampaignAssetQuery extends AdsQuery {

	/**
	 * AdsCampaignAssetQuery constructor.
	 */
	public function __construct() {
		parent::__construct( 'campaign_asset' );
		$this->columns(
			[
				'campaign.id',
				'campaign_asset.field_type',
				'campaign_asset.asset',
				'campaign_asset.status',
			]
		);
	}
}
