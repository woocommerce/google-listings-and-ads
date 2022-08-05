<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;


defined( 'ABSPATH' ) || exit;

/**
 * Class PolicyComplianceCheck
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 *
 * @since x.x.x
 */
class PolicyComplianceCheck implements Service {
	use PluginHelper;
	/**
	 * The WC proxy object.
	 *
	 * @var wc
	 */
	protected $wc;

	/**
	 * @var GoogleHelper
	 */
	protected $google_helper;

	/**
	 * BaseController constructor.
	 *
	 * @param WC           $wc
	 * @param GoogleHelper $google_helper
	 */
	public function __construct( WC $wc, GoogleHelper $google_helper ) {
		$this->wc            = $wc;
		$this->google_helper = $google_helper;
	}

	/**
	 * Check if the store website is accessed by all users for the controller.
	 *
	 * @return bool
	 */
	public function is_accessible(): bool {
		$all_allowed_countries = $this->wc->get_allowed_countries();
		$mc_countries          = $this->google_helper->get_mc_supported_countries();

		foreach ( $mc_countries as $country ) {
			if ( ! array_key_exists( $country, $all_allowed_countries ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if the payment gateways is empty or not for the controller.
	 *
	 * @return bool
	 */
	public function has_payment_gateways(): bool {
		$gateways = $this->wc->get_available_payment_gateways();
		if ( empty( $gateways ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if the store is using SSL for the controller.
	 *
	 * @return bool
	 */
	public function get_is_store_ssl(): bool {
		global $wp_filesystem;
		WP_Filesystem();
		$ssl_check = $wp_filesystem->get_contents( 'ssl://' . $this->get_site_url(), 443, $errno, $errstr, 30 );
		if ( empty( $ssl_check ) ) {
			return false;
		}
		return ! ! $ssl_check;
	}


	/**
	 * Check if the store has refund return policy page for the controller.
	 *
	 * @return bool
	 */
	public function has_refund_return_policy_page(): bool {
		if ( $this->the_slug_exists( _x( 'refund_returns', 'Page slug', 'google-listings-and-ads' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the slug exists or not.
	 *
	 * @param string $post_name
	 * @return bool
	 */
	protected function the_slug_exists( string $post_name ): bool {
		$args = [
			'name'        => $post_name,
			'post_type'   => 'page',
			'post_status' => 'publish',
			'numberposts' => 1,
		];

		if ( get_posts( $args ) ) {
			return true;
		}
		return false;
	}

}
