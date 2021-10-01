<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;

defined( 'ABSPATH' ) || exit;

/**
 * Class Condition
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class ConditionInput extends Select {

	/**
	 * ConditionInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Condition', 'google-listings-and-ads' ) );
		$this->set_description( __( 'Condition or state of the item.', 'google-listings-and-ads' ) );
	}

}
