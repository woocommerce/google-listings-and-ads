<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Integer;

defined( 'ABSPATH' ) || exit;

/**
 * Class Multipack
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class MultipackInput extends Integer {

	/**
	 * MultipackInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Multipack', 'google-listings-and-ads' ) );
		$this->set_description( __( 'The number of identical products in a multipack. Use this attribute to indicate that you\'ve grouped multiple identical products for sale as one item.', 'google-listings-and-ads' ) );
	}
}
