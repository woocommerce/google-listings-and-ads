<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\BooleanSelect;

defined( 'ABSPATH' ) || exit;

/**
 * Class IsBundle
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class IsBundleInput extends BooleanSelect {

	/**
	 * IsBundleInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Is Bundle?', 'google-listings-and-ads' ) );
		$this->set_description( __( 'Whether the item is a bundle of products. A bundle is a custom grouping of different products sold by a merchant for a single price.', 'google-listings-and-ads' ) );
	}

}
