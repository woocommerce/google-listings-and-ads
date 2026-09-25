<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Menu;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;

/**
 * Class GetStarted
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Menu
 */
class GetStarted implements Service, Registerable {

	use MenuFixesTrait;

	public const PATH = '/google/start';

	/**
	 * Onboarding completed status.
	 *
	 * @var OnboardingCompleted
	 */
	private OnboardingCompleted $onboarding_completed;

	/**
	 * GetStarted constructor.
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
		if ( $this->onboarding_completed->is_onboarding_complete() ) {
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
