<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Class Brand
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class BrandInput extends Text {

	/**
	 * BrandInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Brand', 'google-listings-and-ads' ) );
		$this->set_description( __( 'Brand of the product.', 'google-listings-and-ads' ) );
	}

}
