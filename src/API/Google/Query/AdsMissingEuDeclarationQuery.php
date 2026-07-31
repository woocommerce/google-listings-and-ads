<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsMissingEuDeclarationQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsMissingEuDeclarationQuery extends AdsQuery {

	/**
	 * AdsMissingEuDeclarationQuery constructor.
	 */
	public function __construct() {
		parent::__construct( 'campaign' );
	}

	/**
	 * Override to produce the exact GAQL with an unquoted boolean WHERE clause.
	 *
	 * The base Query class wraps all WHERE values in single quotes, which is
	 * invalid for GAQL boolean comparisons. This override returns the raw query.
	 *
	 * @return string
	 */
	protected function build_query(): string {
		return 'SELECT campaign.id, campaign.name, campaign.advertising_channel_type FROM campaign WHERE campaign.missing_eu_political_advertising_declaration = true';
	}
}
