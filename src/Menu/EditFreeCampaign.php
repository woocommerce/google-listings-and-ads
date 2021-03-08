<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class EditFreeCampaign
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class EditFreeCampaign implements Service, Registerable {

	/**
	 * Register a service.
	 */
	public function register(): void {
		add_action(
			'admin_menu',
			function() {
				wc_admin_register_page(
					[
						'title'  => __( 'Edit Free Campaign', 'google-listings-and-ads' ),
						'parent' => 'google-dashboard',
						'path'   => '/google/edit-free-campaign',
						'id'     => 'google-edit-free-campaign',
					]
				);
			}
		);
	}
}
