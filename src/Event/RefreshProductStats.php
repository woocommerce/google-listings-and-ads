<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Event;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class RefreshProductStats
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class RefreshProductStats implements Registerable, Service {
	/**
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * RefreshProductStats constructor.
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
			'gla_batch_updated_products',
			function() {
				$this->refresh_stats();
			}
		);
	}

	/**
	 * Refresh the product statistics cache
	 */
	protected function refresh_stats() {
		try {
			$this->merchant_statuses->maybe_refresh_status_data( true );
		} catch ( Exception $exception ) {
			// log and fail silently
			do_action( 'gla_exception', $exception, __METHOD__ );
		}
	}
}
