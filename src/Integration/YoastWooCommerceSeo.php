<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Integration;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class YoastWooCommerceSeo
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Integration
 */
class YoastWooCommerceSeo implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action( 'gla_product_attribute_value_options_mpn', [ $this, 'add_mpn_option' ] );
	}

	/**
	 * @param array $value_options
	 *
	 * @return array
	 */
	public function add_mpn_option( array $value_options ): array {
		$value_options['yoast_seo'] = 'From Yoast Seo';

		return $value_options;
	}
}
