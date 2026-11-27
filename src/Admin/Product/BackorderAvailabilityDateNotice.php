<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;

defined( 'ABSPATH' ) || exit;

/**
 * Class BackorderAvailabilityDateNotice
 *
 * Displays a notice on the product Inventory tab when the product is on backorder
 * and no Google availability date is set, directing the user to the Google for WooCommerce tab.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product
 */
class BackorderAvailabilityDateNotice implements Service, Registerable, Conditional {

	use AdminConditional;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * BackorderAvailabilityDateNotice constructor.
	 *
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( MerchantCenterService $merchant_center ) {
		$this->merchant_center = $merchant_center;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( ! $this->merchant_center->is_setup_complete() ) {
			return;
		}

		add_action(
			'woocommerce_product_options_inventory_product_data',
			[ $this, 'render_notice' ]
		);
	}

	/**
	 * Output the notice container. React mounts inside and controls visibility
	 * based on backorder/date selection; the empty container collapses to 0 height when hidden.
	 */
	public function render_notice(): void {
		echo '<div class="gla-backorder-availability-date-notice"></div>';
	}
}
