<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsProductLinkInvitationQuery
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query
 */
class AdsProductLinkInvitationQuery extends AdsQuery {

	/**
	 * Query constructor.
	 */
	public function __construct() {
		parent::__construct( 'product_link_invitation' );
		$this->columns( [ 'product_link_invitation.merchant_center.merchant_center_id', 'product_link_invitation.status' ] );
	}
}
