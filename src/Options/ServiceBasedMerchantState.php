<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class ServiceBasedMerchantState
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class ServiceBasedMerchantState implements Service, OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * Batch size for product queries.
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 100;

	/**
	 * Check if the store is service-based.
	 *
	 * If the option has not been calculated yet, it will be calculated automatically
	 * based on whether the store has physical products.
	 *
	 * @return bool True if service-based, false otherwise.
	 */
	public function is_service_based_merchant(): bool {
		$option_value = $this->options->get( OptionsInterface::IS_SERVICE_BASED_MERCHANT );

		// If option is null, calculate it now.
		if ( null === $option_value ) {
			$service_based = ! $this->has_physical_products() ? 'yes' : 'no';
			$this->options->update( OptionsInterface::IS_SERVICE_BASED_MERCHANT, $service_based );
			return 'yes' === $service_based;
		}

		return 'yes' === $option_value;
	}

	/**
	 * Check if the store has at least one physical product that requires shipping.
	 *
	 * @return bool True if at least one physical product requiring shipping is found, false otherwise.
	 */
	public function has_physical_products(): bool {
		$offset = 0;

		do {
			$products = wc_get_products(
				[
					'limit'  => self::BATCH_SIZE,
					'offset' => $offset,
					'status' => 'publish',
				]
			);

			foreach ( $products as $product ) {
				if ( $product->needs_shipping() ) {
					return true;
				}
			}

			$offset        += self::BATCH_SIZE;
			$products_count = count( $products );
		} while ( $products_count === self::BATCH_SIZE );

		return false;
	}

	/**
	 * Reset the service-based merchant status by clearing the option.
	 *
	 * This forces a recalculation on the next call to is_service_based_merchant().
	 *
	 * @return void
	 */
	public function reset_service_based_merchant_status(): void {
		$this->options->delete( OptionsInterface::IS_SERVICE_BASED_MERCHANT );
	}
}
