<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WCS;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\WCS
 */
class ConnectionService implements Service {

	/**
	 * Return an array of feature flags from the external API.
	 *
	 * @return array
	 */
	public function get_features(): array {
		// TODO: Replace hardcoded values with request to external API.
		$features = [
			'version'  => gmdate( 'c' ),
			'features' => [
				'google_tag_gateway' => [
					'enabled'    => true,
					'default'    => true,
					'percentage' => 100,
				],
			],
		];

		return apply_filters( 'woocommerce_gla_wcs_feature_flags', $features );
	}
}
