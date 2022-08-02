<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;


defined( 'ABSPATH' ) || exit;

/**
 * Class PolicyComplianceCheck
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 *
 * @since x.x.x
 */
class PolicyComplianceCheck implements Service {
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
		$all_countries = $this->wc->get_countries();
		$mc_countries  = $this->google_helper->get_mc_supported_countries();

		foreach ( $mc_countries as $country ) {
			if ( ! array_key_exists( $country, $all_countries ) ) {
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
	public function has_page_error(): bool {
		$ids = wc_get_products(
			[
				'return' => 'ids',
				'limit'  => 1,
			]
		);
		if ( ! empty( $ids ) ) {
			$url     = get_permalink( $ids[0] );
			$headers = get_headers( $url );
			if ( ! empty( $headesr ) && $headers[0] !== 'HTTP/1.1 200 OK' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the store sample product landing pages has redirects through 3P domains.
	 *
	 * @return bool
	 */
	public function has_redirects(): bool {
		$ids = wc_get_products(
			[
				'return' => 'ids',
				'limit'  => 1,
			]
		);
		if ( ! empty( $ids ) ) {
			$headers = get_headers( $get_permalink( $ids[0] ) );
			if ( ! empty( $headesr ) && isset( $headers[0] ) ) {
				if ( $headers[0] === 'HTTP/1.1 302 Found' ) {
					// this is the URL where it's redirecting
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if the merchant set the restrictions in robots.txt or not in the store.
	 *
	 * @return bool
	 */
	public function has_restriction(): bool {
		return $this->robots_allowed( get_site_url() );
	}

	/**
	 * Check if the robots.txt has restrictions or not in the store.
	 *
	 * @param string $url
	 * @param bool   $useragent
	 * @return bool
	 */
	private function robots_allowed( $url, $useragent = false ) {
		// parse url to retrieve host and path
		$parsed = wp_parse_url( $url );

		$agents = [ preg_quote( '*', null ) ];
		if ( $useragent ) {
			$agents[] = preg_quote( $useragent, null );
		}
		$agents = implode( '|', $agents );

		// location of robots.txt file

		$robotstxt = @file( "http://{$parsed['host']}/robots.txt" );

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

			// following rules only apply if User-agent matches $useragent or '*'
			if ( preg_match( '/^\s*User-agent: (.*)/i', $line, $match ) ) {
				$rule_applies = preg_match( "/($agents)/i", $match[1] );
			}
			if ( $rule_applies && preg_match( '/^\s*Disallow:(.*)/i', $line, $regs ) ) {
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
			if ( preg_match( "/^$rule/", $parsed['path'] ) ) {
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
		return is_ssl();
	}

	/**
	 * Check if the store has refund return policy page for the controller.
	 *
	 * @return bool
	 */
	public function has_refund_return_policy_page(): bool {
		if ( $this->the_slug_exists( 'refund_returns' ) ) {
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
