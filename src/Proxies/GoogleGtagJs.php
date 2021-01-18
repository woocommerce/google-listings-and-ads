<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;


/**
 * Class GoogleGtagJs
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
class GoogleGtagJs {

	/**
	 * Whether the WC_Google_Gtag_JS class exists
	 * (i.e., WooCommerce Google Analytics Integration is active).
	 *
	 * @return bool True if the class exists.
	 */
	public function class_exists(): bool {
		return class_exists( '\WC_Google_Gtag_JS' );
	}
}
