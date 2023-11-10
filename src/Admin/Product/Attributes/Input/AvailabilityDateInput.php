<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Class AvailabilityDate
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class AvailabilityDateInput extends DateTime {

	/**
	 * AvailabilityDateInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Availability Date', 'google-listings-and-ads' ) );
		$this->set_description( __( 'The date a preordered or backordered product becomes available for delivery. Required if product availability is preorder or backorder', 'google-listings-and-ads' ) );
	}
}
