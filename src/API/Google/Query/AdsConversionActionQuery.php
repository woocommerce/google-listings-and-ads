<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsConversionActionQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsConversionActionQuery extends AdsQuery {

	/**
	 * Query constructor.
	 */
	public function __construct() {
		parent::__construct( 'conversion_action' );
		$this->columns(
			[
				'id'           => 'conversion_action.id',
				'name'         => 'conversion_action.name',
				'status'       => 'conversion_action.status',
				'tag_snippets' => 'conversion_action.tag_snippets',
			]
		);
	}
}
