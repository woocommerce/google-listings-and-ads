<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Class MPN
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class MPNInput extends Text {

	/**
	 * MPNInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Manufacturer Part Number (MPN)', 'google-listings-and-ads' ) );
		$this->set_description( __( 'This code uniquely identifies the product to its manufacturer.', 'google-listings-and-ads' ) );
	}

}
