<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Event;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class ClearProductStatsCache
 *
 * @since 1.1.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ClearProductStatsCache implements Registerable, Service {
	/**
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * ClearProductStatsCache constructor.
	 *
	 * @param MerchantStatuses $merchant_statuses
	 */
	public function __construct( MerchantStatuses $merchant_statuses ) {
		$this->merchant_statuses = $merchant_statuses;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'woocommerce_gla_batch_updated_products',
			function() {
				$this->clear_stats_cache();
			}
		);
		add_action(
			'woocommerce_gla_batch_deleted_products',
			function() {
				$this->clear_stats_cache();
			}
		);
	}

	/**
	 * Clears the product statistics cache
	 */
	protected function clear_stats_cache() {
		try {
			$this->merchant_statuses->clear_cache();
		} catch ( Exception $exception ) {
			// log and fail silently
			do_action( 'woocommerce_gla_exception', $exception, __METHOD__ );
		}
	}
}
