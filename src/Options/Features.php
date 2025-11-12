<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WCS\ConnectionService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class Features
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class Features implements Service, TransientsAwareInterface {

	use TransientsAwareTrait;

	/** @var ConnectionService */
	protected $connection;

	/** @var string Google Tag Gateway feature ID */
	public const GOOGLE_TAG_GATEWAY = 'google_tag_gateway';

	/** @var array Valid feature IDs */
	public const VALID_FEATURES = [
		self::GOOGLE_TAG_GATEWAY,
	];

	/**
	 * Constructor.
	 *
	 * @param ConnectionService $connection
	 */
	public function __construct( ConnectionService $connection ) {
		$this->connection = $connection;
	}

	/**
	 * Return the transient name.
	 *
	 * @return string
	 */
	protected function transient_name(): string {
		return TransientsInterface::WCS_FEATURE_FLAGS;
	}

	/**
	 * Get the feature flag configuration.
	 *
	 * @param string $feature
	 * @return array
	 */
	public function get_features( string $feature = '' ): array {
		$configuration = $this->transients->get( $this->transient_name() );

		if ( is_null( $configuration ) ) {
			$configuration = $this->update_features();
		}

		if ( empty( $feature ) ) {
			return $configuration;
		}

		if ( ! in_array( $feature, self::VALID_FEATURES, true ) || ! isset( $configuration['features'][ $feature ] ) ) {
			return [];
		}

		return $configuration['features'][ $feature ];
	}

	/**
	 * Check if a feature is enabled.
	 *
	 * @param string $feature
	 * @return boolean True if the feature is enabled, false if disabled.
	 */
	public function is_enabled( string $feature ): bool {
		$option = $this->get_features();

		if ( isset( $option['features'][ $feature ]['enabled'] ) ) {
			return $option['features'][ $feature ]['enabled'];
		}

		return false;
	}

	/**
	 * Update the feature flags option from the WCS API.
	 *
	 * @return array
	 */
	protected function update_features() {
		// Get the latest feature flags from WCS.
		$features = $this->connection->get_features();

		// Create the new option array.
		$option = [
			'version'  => isset( $features['version'] ) ? $features['version'] : gmdate( 'c' ),
			'features' => [],
		];

		// Loop over each feature and determine if it is enabled.
		foreach ( $features['features'] as $name => $attributes ) {
			// Skip if not a recognised feature.
			if ( ! in_array( $name, self::VALID_FEATURES, true ) ) {
				continue;
			}

			// Create the feature array with the feature disabled by default.
			$option['features'][ $name ] = [
				'enabled'    => false,
				'attributes' => $attributes,
			];

			// Continue if the feature is not enabled.
			if ( isset( $attributes['enabled'] ) && false === $attributes['enabled'] ) {
				continue;
			}

			// If enabled with no percentage rollout.
			if ( ! isset( $attributes['percentage'] ) ) {
				$option['features'][ $name ]['enabled'] = true;
				continue;
			}

			// Check if the site is enabled based on the percentage rollout.
			if ( $this->get_site_percentage_rollout( $name ) < $attributes['percentage'] ) {
				$option['features'][ $name ]['enabled'] = true;
			}
		}

		// Update the transient in the database.
		$this->transients->set( $this->transient_name(), $option, MINUTE_IN_SECONDS * 5 );

		return $option;
	}

	/**
	 * Return the site percentage rollout for a specific feature.
	 *
	 * Generates a deterministic percentage value (0–99) used for feature rollout decisions.
	 *
	 * Provides a consistent value to determine whether a site falls within a feature rollout
	 * group based on a target percentage. The same site and feature will always produce
	 * the same value, allowing predictable rollouts on the client-side without relying on external state.
	 *
	 * Example:
	 *   Enable a feature for 25% of sites:
	 *   if ( $this->get_site_percentage_rollout( 'feature_slug' ) < 25 ) { ... }
	 *
	 * @param string $feature
	 * @param string $site_id
	 * @return integer
	 */
	public function get_site_percentage_rollout( string $feature, $site_id = null ): int {
		// Get the unique ID for the site.
		$site_id = $site_id ?? get_home_url();

		// Create a numeric hash using the site ID and feature name.
		$hash = abs( crc32( $site_id . ':' . $feature ) );

		// Return the site percentage rollout.
		return $hash % 100;
	}
}
