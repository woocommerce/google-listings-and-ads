<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Activateable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;

/**
 * Class ActivationRedirect
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class ActivationRedirect implements Activateable, Service, Registerable, OptionsAwareInterface, MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;

	protected const OPTION = OptionsInterface::REDIRECT_TO_ONBOARDING;

	protected const PARAMS = [
		'page' => 'wc-admin',
		'path' => '/google/start',
	];
	/**
	 * @var WP
	 */
	protected $wp;

	/**
	 * Installer constructor.
	 *
	 * @param WP $wp
	 */
	public function __construct( WP $wp ) {
		$this->wp = $wp;
	}

	/**
	 * Register a service.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action(
			'admin_init',
			function () {
				$this->maybe_redirect_to_onboarding();
			}
		);
	}

	/**
	 * Activate a service.
	 *
	 * @return void
	 */
	public function activate(): void {
		// Do not take any action if activated in a REST request (via wc-admin).
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if (
			// Only redirect to onboarding when activated on its own. Either with a link...
			isset( $_GET['action'] ) && 'activate' === $_GET['action'] // phpcs:ignore WordPress.Security.NonceVerification
			// ...or with a bulk action.
			|| isset( $_POST['checked'] ) && is_array( $_POST['checked'] ) && 1 === count( $_POST['checked'] ) // phpcs:ignore WordPress.Security.NonceVerification
		) {
			$this->options->update( self::OPTION, 'yes' );
		}
	}

	/**
	 * Checks if merchant should be redirected to the onboarding page if it is not.
	 *
	 * @return bool True if the redirection should have happened
	 */
	protected function maybe_redirect_to_onboarding(): bool {
		if ( $this->wp->wp_doing_ajax() ) {
			return false;
		}

		// If we have redirected before do not attempt to redirect again.
		if ( 'yes' !== $this->options->get( self::OPTION ) ) {
			return false;
		}

		// Do not redirect if setup is already complete
		if ( $this->merchant_center->is_setup_complete() ) {
			$this->options->update( self::OPTION, 'no' );
			return false;
		}

		// if we are on the get started page don't redirect again
		if ( $this->is_onboarding_page() && 'yes' === $this->options->get( self::OPTION, 'yes' ) ) {
			$this->options->update( self::OPTION, 'no' );
			return false;
		}

		// Redirect if setup is not complete
		$this->redirect_to_onboarding_page();
		return true;
	}

	/**
	 * Utility function to check if are on the main "Get Started" onboarding page.
	 *
	 * @return bool
	 */
	protected function is_onboarding_page(): bool {
		// If we are already in the onboarding page, return true
		if ( count( self::PARAMS ) === count( array_intersect_assoc( $_GET, self::PARAMS ) ) ) { // phpcs:disable WordPress.Security.NonceVerification.Recommended
			return true;
		}

		return false;
	}

	/**
	 * Utility function to immediately redirect to the main "Get Started" onboarding page.
	 * Note that this function immediately ends the execution.
	 *
	 * @return void
	 */
	protected function redirect_to_onboarding_page(): void {
		// If we are already on the onboarding page, do nothing.
		if ( $this->is_onboarding_page() ) {
			return;
		}

		wp_safe_redirect( admin_url( add_query_arg( self::PARAMS, 'admin.php' ) ) );
		exit();
	}
}
