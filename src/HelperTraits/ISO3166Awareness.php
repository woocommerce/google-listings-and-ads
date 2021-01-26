<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Trait ISO3166Awareness
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits
 */
trait ISO3166Awareness {

	/**
	 * The object implementing the ISO3166DataProvider interface.
	 *
	 * @var ISO3166DataProvider
	 */
	protected $iso3166_data_provider;

	/**
	 * @param ISO3166DataProvider $provider
	 *
	 * @return void
	 */
	public function set_iso3166_provider( ISO3166DataProvider $provider ): void {
		$this->iso3166_data_provider = $provider;
	}
}
