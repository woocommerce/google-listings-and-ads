<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Google\Service\ShoppingContent\AccountAddress;

defined( 'ABSPATH' ) || exit;

/**
 * Class AddressUtility
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Utility
 *
 * @since 1.4.0
 */
class AddressUtility implements Service {
	/**
	 * Checks whether two account addresses are the same and returns true if they are.
	 *
	 * @param AccountAddress $address_1
	 * @param AccountAddress $address_2
	 *
	 * @return bool True if the two addresses are the same, false otherwise.
	 */
	public function compare_addresses( AccountAddress $address_1, AccountAddress $address_2 ): bool {
		$cmp_street_address = $address_1->getStreetAddress() === $address_2->getStreetAddress();
		$cmp_locality       = $address_1->getLocality() === $address_2->getLocality();
		$cmp_region         = $address_1->getRegion() === $address_2->getRegion();
		$cmp_postal_code    = $address_1->getPostalCode() === $address_2->getPostalCode();
		$cmp_country        = $address_1->getCountry() === $address_2->getCountry();

		return $cmp_street_address && $cmp_locality && $cmp_region && $cmp_postal_code && $cmp_country;
	}
}
