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
	public function features(): array {
		// TODO: Replace hardcoded values with request to external API.
		return [
			'version'  => 1,
			'features' => [
				'google_tag_gateway' => [
					'enabled'    => true,
					'default'    => true,
					'percentage' => 100,
				],
			],
		];
	}
}
