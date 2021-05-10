<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

defined( 'ABSPATH' ) || exit;

/**
 * Class Text
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input
 */
class Text extends Input {
	/**
	 * Text constructor.
	 */
	public function __construct() {
		parent::__construct( 'text' );
	}
}
