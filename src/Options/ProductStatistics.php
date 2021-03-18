<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductRepository;
use Exception;

/**
 * Class ProductStatistics
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class ProductStatistics implements Service, OptionsAwareInterface, ContainerAwareInterface {

	use OptionsAwareTrait;
	use ContainerAwareTrait;

	/**
	 * The time the statistics option should live.
	 */
	public const STATISTICS_LIFETIME = HOUR_IN_SECONDS;

	/**
	 * Option array keys.
	 */
	private const TIMESTAMP_KEY  = 'timestamp';
	private const STATISTICS_KEY = 'statistics';

	/**
	 * Retrieve or initialize the product_statistics option.
	 *
	 * @param bool $refresh_if_necessary True to initialize or refresh the stats if necessary.
	 *
	 * @return array The account creation steps and statuses.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function get( bool $refresh_if_necessary = true ): array {
		$product_statistics = $this->options->get( Options::MC_PRODUCT_STATISTICS, [] );
		$timestamp          = $product_statistics[ self::TIMESTAMP_KEY ] ?? 0;
		$stats              = $product_statistics[ self::STATISTICS_KEY ] ?? [];

		if ( $refresh_if_necessary && $timestamp < time() - self::STATISTICS_LIFETIME ) {
			$stats = $this->recalculate();
		}

		return $stats;
	}

	/**
	 * Recalculate the product statistics and update the DB option.
	 *
	 * @returns array The recalculated product statistics.
	 * @throws Exception If the account state can't be retrieved from Google.
	 */
	public function recalculate(): array {
		$product_stats = [
			'active'      => 0,
			'expiring'    => 0,
			'pending'     => 0,
			'disapproved' => 0,
			'not_synced'  => 0,
		];
		/** @var Merchant $merchant */
		$merchant = $this->container->get( Merchant::class );
		foreach ( $merchant->get_accountstatus()->getProducts() as $product ) {
			$stats                         = $product->getStatistics();
			$product_stats['active']      += intval( $stats->getActive() );
			$product_stats['expiring']    += intval( $stats->getExpiring() );
			$product_stats['pending']     += intval( $stats->getPending() );
			$product_stats['disapproved'] += intval( $stats->getDisapproved() );
		}

		/** @var ProductRepository $product_repository */
		$product_repository          = $this->container->get( ProductRepository::class );
		$product_stats['not_synced'] = count( $product_repository->find_sync_pending_product_ids() );

		// Update the cached values
		$this->options->update(
			Options::MC_PRODUCT_STATISTICS,
			[
				self::TIMESTAMP_KEY  => time(),
				self::STATISTICS_KEY => $product_stats,
			]
		);

		return $product_stats;
	}

	/**
	 * Delete the cached statistics.
	 */
	public function delete(): void {
		$this->options->delete( Options::MC_PRODUCT_STATISTICS );
	}
}
