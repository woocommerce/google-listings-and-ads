<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsCampaignLabelQuery extends AdsQuery {

	/**
	 * Query constructor.
	 *
	 * @param array $args Query arguments.
	 */
	public function __construct( $args = [] ) {
		parent::__construct( 'label' );
		$this->columns(
			[
				'label.id',
			]
		);
	}
}
