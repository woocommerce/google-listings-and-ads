<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AvailabilityDate;
use WC_Product;

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
	 * @var AttributeManager
	 */
	protected $attribute_manager;

	/**
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * BackorderAvailabilityDateNotice constructor.
	 *
	 * @param AttributeManager      $attribute_manager
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( AttributeManager $attribute_manager, MerchantCenterService $merchant_center ) {
		$this->attribute_manager = $attribute_manager;
		$this->merchant_center   = $merchant_center;
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

	/**
	 * Whether the notice should be shown for the given product.
	 *
	 * @param WC_Product $product
	 * @return bool
	 */
	protected function should_show_notice( WC_Product $product ): bool {
		if ( ! in_array( $product->get_type(), AvailabilityDate::get_applicable_product_types(), true ) ) {
			return false;
		}

		if ( ! $product->is_on_backorder( 1 ) ) {
			return false;
		}

		try {
			$availability_date = $this->attribute_manager->get( $product, AvailabilityDate::get_id() );
		} catch ( InvalidValue $e ) {
			return true;
		}

		if ( $availability_date === null ) {
			return true;
		}

		$value = $availability_date->get_value();
		return $value === null || $value === '';
	}

	/**
	 * Get the current product being edited.
	 *
	 * @return WC_Product|null
	 */
	protected function get_current_product(): ?WC_Product {
		global $product_object;

		if ( $product_object instanceof WC_Product ) {
			return $product_object;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return null;
		}

		$product = wc_get_product( $post_id );

		return $product instanceof WC_Product ? $product : null;
	}
}
