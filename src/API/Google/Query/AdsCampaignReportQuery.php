<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignReportQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsCampaignReportQuery extends AdsReportQuery {

	/**
	 * Set the initial columns for this query.
	 */
	protected function set_initial_columns() {
		$this->columns(
			[
				'id'     => 'campaign.id',
				'name'   => 'campaign.name',
				'status' => 'campaign.status',
				'type'   => 'campaign.advertising_channel_type',
			]
		);
	}

	/**
	 * Filter the query by a list of ID's.
	 *
	 * @param array $ids list of ID's to filter by.
	 *
	 * @return $this
	 */
	public function filter( array $ids ): QueryInterface {
		if ( empty( $ids ) ) {
			return $this;
		}

		// Google Ads requires a field used in WHERE to also be selected, so keep the campaign
		// ID column even when the dimension columns are left out.
		if ( ! $this->include_dimensions ) {
			$this->add_columns( [ 'id' => 'campaign.id' ] );
		}

		return $this->where( 'campaign.id', $ids, 'IN' );
	}
}
