<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WCS;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Class ConnectionService
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\WCS
 */
class ConnectionService implements Service {

	/** @var Features */
	protected $features;

	/**
	 * Constructor
	 *
	 * @param Features $features
	 */
	public function __construct( Features $features ) {
		$this->features = $features;
	}

	/**
	 * Return an array of feature flags from the external API.
	 *
	 * @return array
	 */
	public function get_features(): array {
		// TODO: Replace hardcoded values with request to external API.
		return [
			'version'  => gmdate( 'c' ),
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
		$feature_flags = $this->get_features();

		$this->features->update( $feature_flags );
	}
}
