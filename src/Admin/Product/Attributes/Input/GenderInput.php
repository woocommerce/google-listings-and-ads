<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;

defined( 'ABSPATH' ) || exit;

/**
 * Class Gender
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class GenderInput extends Select {

	/**
	 * GenderInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Gender', 'google-listings-and-ads' ) );
		$this->set_description( __( 'The gender for which your product is intended.', 'google-listings-and-ads' ) );
	}

}
