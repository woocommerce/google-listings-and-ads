<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Input;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class FormException
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class FormException extends Exception implements GoogleListingsAndAdsException {
	/**
	 * Return a new instance of the exception when a submitted form is being modified.
	 *
	 * @return static
	 */
	public static function cannot_modify_submitted(): FormException {
		return new static( 'You cannot modify a submitted form.' );
	}
}
