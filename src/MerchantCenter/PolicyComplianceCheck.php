<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\TargetAudience;


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
	 * @var TargetAudience
	 */
	protected $target_audience;

	/**
	 * BaseController constructor.
	 *
	 * @param WC             $wc
	 * @param GoogleHelper   $google_helper
	 * @param TargetAudience $target_audience
	 */
	public function __construct( WC $wc, GoogleHelper $google_helper, TargetAudience $target_audience ) {
		$this->wc              = $wc;
		$this->google_helper   = $google_helper;
		$this->target_audience = $target_audience;
	}

	/**
	 * Check if the store website is accessed by all users for the controller.
	 *
	 * @return bool
	 */
	public function is_accessible(): bool {
		$all_allowed_countries = $this->wc->get_allowed_countries();
		$target_countries      = $this->target_audience->get_target_countries();

		foreach ( $target_countries as $country ) {
			if ( ! array_key_exists( $country, $all_allowed_countries ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if the store sample product landing pages lead to a 404 error.
	 *
	 * @return bool
	 */
	public function has_page_not_found_error(): bool {
		$headers = $this->get_landing_page_headers();
		if ( ! empty( $headers ) && $headers[0] !== 'HTTP/1.1 200 OK' ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the store sample product landing pages has redirects through 3P domains.
	 *
	 * @return bool
	 */
	public function has_redirects(): bool {
				$headers = $this->get_landing_page_headers();
		if ( ! empty( $headesr ) && isset( $headers[0] ) ) {
			if ( $headers[0] === 'HTTP/1.1 302 Found' ) {
				// this is the URL where it's redirecting
				return true;
			}
		}
			return false;
	}

	/**
	 * Get the headers of the store sample product landing pages.
	 *
	 * @return array
	 */
	public function get_landing_page_headers(): array {
		$ids = wc_get_products(
			[
				'return' => 'ids',
				'limit'  => 1,
			]
		);
		if ( ! empty( $ids ) ) {
			$response = wp_remote_get( $ids[0]->get_permalink() );
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				return $response['headers'];
			}
		}
	}

	/**
	 * Check if the merchant set the restrictions in robots.txt or not in the store.
	 *
	 * @return bool
	 */
	public function has_restriction(): bool {
		return ! $this->robots_allowed( $this->get_site_url() );
	}

	/**
	 * Check if the robots.txt has restrictions or not in the store.
	 *
	 * @param string $url
	 * @return bool
	 */
	private function robots_allowed( $url ) {
		$agents = [ preg_quote( '*', '/' ) ];
		$agents = implode( '|', $agents );

		// location of robots.txt file
		$robotstxt = file( trailingslashit( $url ) . 'robots.txt' );

		// if there isn't a robots, then we're allowed in
		if ( empty( $robotstxt ) ) {
			return true;
		}

		$rules        = [];
		$rule_applies = false;
		foreach ( $robotstxt as $line ) {
			// skip blank lines
			$line = trim( $line );
			if ( ! $line ) {
				continue;
			}

			// following rules only apply if User-agent matches '*'
			if ( preg_match( '/^\s*User-agent:\s*(.*)/i', $line, $match ) ) {
				$rule_applies = preg_match( "/($agents)/i", $match[1] );
			}
			if ( $rule_applies && preg_match( '/^\s*Disallow:\s*(.*)/i', $line, $regs ) ) {
				// an empty rule implies full access - no further tests required
				if ( ! $regs[1] ) {
					return true;
				}
				// add rules that apply to array for testing
				$rules[] = preg_quote( trim( $regs[1] ), '/' );
			}
		}

		foreach ( $rules as $rule ) {
			// check if page is disallowed to us
			if ( preg_match( "/^$rule/", '/' ) ) {
				return false;
			}
		}

		// page is not disallowed
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
		return 'https' === wp_parse_url( $this->get_site_url(), PHP_URL_SCHEME );
	}


	/**
	 * Check if the store has refund return policy page for the controller.
	 *
	 * @return bool
	 */
	public function has_refund_return_policy_page(): bool {
		// Check the slug as it's translated by the "woocommerce" text domain name.
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		if ( $this->the_slug_exists( _x( 'refund_returns', 'Page slug', 'woocommerce' ) ) ) {
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
