<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\BooleanSelect;

defined( 'ABSPATH' ) || exit;

/**
 * Class Adult
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class AdultInput extends BooleanSelect {

	/**
	 * AdultInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Adult content', 'google-listings-and-ads' ) );
		$this->set_description( __( 'Whether the product contains nudity or sexually suggestive content', 'google-listings-and-ads' ) );
	}

}
