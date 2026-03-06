<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;

/**
 * Class Dashboard
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class Dashboard implements Service, Registerable {

	use MenuFixesTrait;

	public const PATH = '/google/dashboard';

	public const MARKETING_MENU_SLUG = 'woocommerce-marketing';

	/**
	 * Onboarding completed status.
	 *
	 * @var OnboardingCompleted
	 */
	private $onboarding_completed;

	/**
	 * Dashboard constructor.
	 *
	 * @param OnboardingCompleted $onboarding_completed Onboarding completed status.
	 */
	public function __construct( OnboardingCompleted $onboarding_completed ) {
		$this->onboarding_completed = $onboarding_completed;
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		if ( ! $this->onboarding_completed->is_onboarding_complete() ) {
			return;
		}

		add_action(
			'admin_menu',
			function () {
				$this->register_classic_submenu_page(
					[
						'id'     => 'google-listings-and-ads',
						'title'  => __( 'Google for WooCommerce', 'google-listings-and-ads' ),
						'parent' => self::MARKETING_MENU_SLUG,
						'path'   => self::PATH,
					]
				);
			}
		);
	}
}
