<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;

/**
 * Class GetStarted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class GetStarted implements Service, Registerable, MerchantCenterAwareInterface {

	use MenuFixesTrait;
	use MerchantCenterAwareTrait;

	public const PATH = '/google/start';

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( $this->merchant_center->is_setup_complete() ) {
			return;
		}

		add_action(
			'admin_menu',
			function () {
				$this->register_classic_submenu_page(
					[
						'id'     => 'google-listings-and-ads',
						'title'  => __( 'Google for WooCommerce', 'google-listings-and-ads' ),
						'parent' => 'woocommerce-marketing',
						'path'   => self::PATH,
					]
				);
			}
		);
	}
}
