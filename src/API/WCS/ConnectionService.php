<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WCS;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\WCS
 */
class ConnectionService implements Service, OptionsAwareInterface {

	use OptionsAwareTrait;

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

	/**
	 * Update the feature flags option in the database.
	 *
	 * @return void
	 */
	public function update_feature_flags(): void {
		$features = $this->features();

		$this->options->update( OptionsInterface::WCS_FEATURE_FLAGS, $features );
	}
}
