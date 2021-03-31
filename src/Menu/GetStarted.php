<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\MerchantCenterTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;

/**
 * Class GetStarted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class GetStarted implements Service, Registerable, OptionsAwareInterface {

	use MenuFixesTrait;
	use MerchantCenterTrait;

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( $this->setup_complete() ) {
			return;
		}

		add_filter(
			'woocommerce_marketing_menu_items',
			function( $menu_items ) {
				return $this->add_items( $menu_items );
			}
		);

		add_action(
			'admin_menu',
			function() {
				$this->fix_menu_paths();
			}
		);
	}

	/**
	 * Add Google Menu item under Marketing
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	protected function add_items( array $items ): array {
		$items[] = [
			'id'         => 'google-start',
			'title'      => __( 'Google Listings & Ads', 'google-listings-and-ads' ),
			'path'       => '/google/start',
			'capability' => 'manage_woocommerce',
		];

		return $items;
	}
}
