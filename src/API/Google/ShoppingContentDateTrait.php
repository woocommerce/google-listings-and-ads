<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\Date as ShoppingContentDate;
use DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Trait ShoppingContentDateTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
trait ShoppingContentDateTrait {

	/**
	 * Convert ShoppingContentDate to DateTime.
	 *
	 * @param ShoppingContentDate $date The Google date.
	 *
	 * @return DateTime|false The date converted or false if the date is invalid.
	 */
	protected function convert_shopping_content_date( ShoppingContentDate $date ) {
		return DateTime::createFromFormat( 'Y-m-d|', "{$date->getYear()}-{$date->getMonth()}-{$date->getDay()}" );
	}
}
