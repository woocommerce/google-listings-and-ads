<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ISO3166AwareInterface
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits
 */
interface ISO3166AwareInterface {

	/**
	 * @param ISO3166DataProvider $provider
	 *
	 * @return void
	 */
	public function set_iso3166_provider( ISO3166DataProvider $provider ): void;
}
