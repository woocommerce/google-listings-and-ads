<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Decimal
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class Decimal extends Input {
	/**
	 * Decimal constructor.
	 */
	public function __construct() {
		parent::__construct( 'decimal' );
	}
}
