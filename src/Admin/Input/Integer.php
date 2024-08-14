<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Integer
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class Integer extends Input {
	/**
	 * Integer constructor.
	 */
	public function __construct() {
		// Ideally, it should use the 'woocommerce/product-number-field' block
		// but the block doesn't support integer validation. Therefore, it uses
		// the text field block to work around it.
		parent::__construct( 'integer', 'woocommerce/product-text-field' );

		$this->set_block_attribute(
			'pattern',
			[
				'value' => '0|[1-9]\d*',
			]
		);
	}
}
