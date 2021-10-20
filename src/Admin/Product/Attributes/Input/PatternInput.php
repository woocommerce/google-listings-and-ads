<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product\Attributes\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input\Text;

defined( 'ABSPATH' ) || exit;

/**
 * Class Pattern
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes
 *
 * @since 1.5.0
 */
class PatternInput extends Text {

	/**
	 * PatternInput constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->set_label( __( 'Pattern', 'google-listings-and-ads' ) );
		$this->set_description( __( 'The item\'s pattern (e.g. polka dots).', 'google-listings-and-ads' ) );
	}

}
