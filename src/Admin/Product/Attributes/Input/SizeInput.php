<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Class Size
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class SizeInput extends Text {

	/**
	 * SizeInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Size', 'google-listings-and-ads' ) );
		$this->set_description( __( 'Size of the product.', 'google-listings-and-ads' ) );
	}
}
