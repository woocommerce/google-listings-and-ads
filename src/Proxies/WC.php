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
	 * @var array
	 */
	protected $countries;

	/**
	 * WC constructor.
	 *
	 * @param WC_Countries|null $countries
	 */
	public function __construct( ?WC_Countries $countries = null ) {
		$this->countries = $countries->get_countries() ?? [];
	}

	/**
	 * Get WooCommerce
	 *
	 * @return array
	 */
	public function get_countries(): array {
		return $this->countries;
	}
}
