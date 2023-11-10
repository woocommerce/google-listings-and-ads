<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Class Material
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class MaterialInput extends Text {

	/**
	 * MaterialInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Material', 'google-listings-and-ads' ) );
		$this->set_description( __( 'The material of which the item is made.', 'google-listings-and-ads' ) );
	}
}
