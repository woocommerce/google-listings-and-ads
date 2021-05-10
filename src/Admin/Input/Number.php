<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Number
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class Number extends Input {
	/**
	 * Number constructor.
	 */
	public function __construct() {
		parent::__construct( 'number' );
	}
}
