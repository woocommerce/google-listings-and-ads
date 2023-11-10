<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;

defined( 'ABSPATH' ) || exit;

/**
 * Class SizeSystem
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class SizeSystemInput extends Select {

	/**
	 * SizeInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Size system', 'google-listings-and-ads' ) );
		$this->set_description( __( 'System in which the size is specified. Recommended for apparel items.', 'google-listings-and-ads' ) );
	}
}
