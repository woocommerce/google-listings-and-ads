<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Class Color
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class ColorInput extends Text {

	/**
	 * ColorInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Color', 'google-listings-and-ads' ) );
		$this->set_description( __( 'Color of the product.', 'google-listings-and-ads' ) );
	}

}
