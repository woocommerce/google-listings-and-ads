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
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\Dashboard;
use Automattic\WooCommerce\GoogleListingsAndAds\Menu\GetStarted;
use Automattic\WooCommerce\Admin\PageController;

/**
 * Class Redirect
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class Redirect implements Activateable, Service, Registerable, OptionsAwareInterface, MerchantCenterAwareInterface {

	use MerchantCenterAwareTrait;
	use OptionsAwareTrait;

	protected const OPTION = OptionsInterface::REDIRECT_TO_ONBOARDING;

	public const PATHS = [
		'dashboard'   => Dashboard::PATH,
		'get_started' => GetStarted::PATH,
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
				$this->maybe_redirect();
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
	 * @return void
	 */
	public function maybe_redirect() {
		if ( $this->wp->wp_doing_ajax() ) {
			return;
		}

		// Maybe redirect to onboarding after activation
		if ( 'yes' === $this->options->get( self::OPTION ) ) {
			return $this->maybe_redirect_after_activation();
		}

		// If setup ISNT complete then redirect from dashboard to onboarding
		if ( ! $this->merchant_center->is_setup_complete() && $this->is_current_wc_admin_page( self::PATHS['dashboard'] ) ) {
			return $this->redirect_to( self::PATHS['get_started'] );
		}

		// If setup IS complete then redirect from onboarding to dashboard
		if ( $this->merchant_center->is_setup_complete() && $this->is_current_wc_admin_page( self::PATHS['get_started'] ) ) {
			return $this->redirect_to( self::PATHS['dashboard'] );
		}

		return false;
	}

	/**
	 * Checks if merchant should be redirected to the onboarding page after extension activation.
	 *
	 * @return bool True if the redirection should have happened
	 */
	protected function maybe_redirect_after_activation(): bool {
		// Do not redirect if setup is already complete
		if ( $this->merchant_center->is_setup_complete() ) {
			$this->options->update( self::OPTION, 'no' );
			return false;
		}

		// if we are on the get started page don't redirect again
		if ( $this->is_current_wc_admin_page( self::PATHS['get_started'] ) ) {
			$this->options->update( self::OPTION, 'no' );
			return false;
		}

		// Redirect if setup is not complete
		$this->redirect_to( self::PATHS['get_started'] );
		return true;
	}

	/**
	 * Utility function to immediately redirect to a given WC Admin path.
	 * Note that this function immediately ends the execution.
	 *
	 * @param string $path The WC Admin path to redirect to
	 *
	 * @return void
	 */
	public function redirect_to( $path ): void {
		// If we are already on this path, do nothing.
		if ( $this->is_current_wc_admin_page( $path ) ) {
			return;
		}

		$params = [
			'page' => PageController::PAGE_ROOT,
			'path' => $path,
		];

		wp_safe_redirect( admin_url( add_query_arg( $params, 'admin.php' ) ) );
		exit();
	}

	/**
	 * Check if the current WC Admin page matches the given path.
	 *
	 * @param string $path The path to check.
	 *
	 * @return bool
	 */
	public function is_current_wc_admin_page( $path ): bool {
		$params = [
			'page' => PageController::PAGE_ROOT,
			'path' => $path,
		];

		return 2 === count( array_intersect_assoc( $_GET, $params ) ); // phpcs:disable WordPress.Security.NonceVerification.Recommended
	}

}
