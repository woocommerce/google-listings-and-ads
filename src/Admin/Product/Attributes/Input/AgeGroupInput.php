<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;

defined( 'ABSPATH' ) || exit;

/**
 * Class AgeGroup
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class AgeGroupInput extends Select {

	/**
	 * AgeGroupInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Age Group', 'google-listings-and-ads' ) );
		$this->set_description( __( 'Target age group of the item.', 'google-listings-and-ads' ) );
	}
}
