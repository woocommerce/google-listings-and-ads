<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class CreatePaidAdsCampaign
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class CreatePaidAdsCampaign implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'  => __( 'Create your paid campaign', 'google-listings-and-ads' ),
						'parent' => 'google-dashboard',
						'path'   => '/google/campaigns/create',
						'id'     => 'google-create-paid-ads-campaign',
					]
				);
			}
		);
	}
}
