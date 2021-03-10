<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use WC_Countries;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
class WC {

	/**
	 * The base location for the store.
	 *
	 * @var string
	 */
	protected $base_country;

	/**
	 * @var array
	 */
	protected $countries;

	/** @var WC_Countries */
	protected $wc_countries;

	/**
	 * WC constructor.
	 *
	 * @param WC_Countries|null $countries
	 */
	public function __construct( ?WC_Countries $countries = null ) {
		$countries          = $countries ?? new WC_Countries();
		$this->wc_countries = $countries;
		$this->base_country = $countries->get_base_country() ?? 'US';
		$this->countries    = $countries->get_countries() ?? [];
	}

	/**
	 * Get WooCommerce
	 *
	 * @return array
	 */
	public function get_countries(): array {
		return $this->countries;
	}

	/**
	 * Get the base country for the store.
	 *
	 * @return string
	 */
	public function get_base_country(): string {
		return $this->base_country;
	}

	/**
	 * Get the WC_Countries object
	 *
	 * @return WC_Countries
	 */
	public function get_wc_countries(): WC_Countries {
		return $this->wc_countries;
	}
}
