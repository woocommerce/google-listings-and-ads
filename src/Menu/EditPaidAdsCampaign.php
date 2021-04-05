<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class EditPaidAdsCampaign
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class EditPaidAdsCampaign implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'  => __( 'Edit Paid Ads Campaign', 'google-listings-and-ads' ),
						'parent' => 'google-dashboard',
						'path'   => '/google/edit-paid-ads-campaign',
						'id'     => 'google-edit-paid-ads-campaign',
					]
				);
			}
		);
	}
}
