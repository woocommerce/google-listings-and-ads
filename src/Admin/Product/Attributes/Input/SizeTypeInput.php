<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Select;

defined( 'ABSPATH' ) || exit;

/**
 * Class SizeType
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class SizeTypeInput extends Select {

	/**
	 * SizeTypeInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Size type', 'google-listings-and-ads' ) );
		$this->set_description( __( 'The cut of the item. Recommended for apparel items.', 'google-listings-and-ads' ) );
	}
}
