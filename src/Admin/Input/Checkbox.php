<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Checkbox
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class Checkbox extends Input {
	/**
	 * Number constructor.
	 */
	public function __construct() {
		parent::__construct( 'checkbox' );
	}
}
