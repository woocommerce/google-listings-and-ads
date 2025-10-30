<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class Features
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class Features implements Service, OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var string Google Tag Gateway feature ID */
	public const GOOGLE_TAG_GATEWAY = 'google_tag_gateway';

	/** @var array Valid feature IDs */
	public const VALID_FEATURES = [
		self::GOOGLE_TAG_GATEWAY,
	];

	/**
	 * Return the option name.
	 *
	 * @return string
	 */
	protected function option_name(): string {
		return OptionsInterface::WCS_FEATURE_FLAGS;
	}

	/**
	 * Check if a feature is enabled.
	 *
	 * @param string $feature
	 * @return boolean True if the feature is enabled, false if disabled.
	 */
	public function is_enabled( string $feature ): bool {
		$option = $this->options->get( $this->option_name() );

		if ( isset( $option['features'][ $feature ]['enabled'] ) ) {
			return $option['features'][ $feature ]['enabled'];
		}

		return false;
	}

	/**
	 * Update the feature flags option from the WCS API.
	 *
	 * @param array $features
	 * @return void
	 */
	public function update( array $features ) {
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

		// Update the option in the database.
		$this->options->update( $this->option_name(), $option );
	}

	/**
	 * Return the site percentage rollout for a specific feature.
	 *
	 * @param string $feature
	 * @return integer
	 */
	protected function get_site_percentage_rollout( string $feature ): int {
		// Get the unique ID for the site.
		$site_id = get_home_url();

		// Create a numeric hash using the site ID and feature name.
		$hash = crc32( $site_id . ':' . $feature );

		// Return the site percentage rollout.
		return $hash % 100;
	}
}
