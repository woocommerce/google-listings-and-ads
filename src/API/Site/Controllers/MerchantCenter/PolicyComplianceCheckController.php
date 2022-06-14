<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\EmptySchemaPropertiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WC_Install;

defined( 'ABSPATH' ) || exit;

/**
 * Class PolicyComplianceCheckController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class PolicyComplianceCheckController extends BaseController {

	use CountryCodeTrait;
	use EmptySchemaPropertiesTrait;

	/**
	 * The WC proxy object.
	 *
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var GoogleHelper
	 */
	protected $google_helper;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer   $server
	 * @param WC           $wc
	 * @param GoogleHelper $google_helper
	 */
	public function __construct( RESTServer $server, WC $wc, GoogleHelper $google_helper ) {
		parent::__construct( $server );
		$this->wc            = $wc;
		$this->google_helper = $google_helper;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/policy_check/allowed_countries',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_allowed_countries(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'mc/policy_check/payment_gateways',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->has_payment_gateways(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'mc/policy_check/store_ssl',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_is_store_ssl(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'mc/policy_check/refund_return_policy',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->has_refund_return_policy_page(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the allowed countries for the controller.
	 *
	 * @return array
	 */
	protected function get_allowed_countries(): array {
		return $this->wc->get_allowed_countries();
	}

	/**
	 * Check if the payment gateways is empty or not for the controller.
	 *
	 * @return bool
	 */
	protected function has_payment_gateways(): bool {
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
	protected function get_is_store_ssl(): bool {
		return is_store_ssl();
	}

	/**
	 * Check if the store has refund return policy page for the controller.
	 *
	 * @return bool
	 */
	protected function has_refund_return_policy_page(): bool {
		if ( $this->the_slug_exists( 'refund_returns' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'policy_check';
	}

	/**
	 * Check if the slug exists or not.
	 *
	 * @param PostName $post_name
	 */
	protected function the_slug_exists( $post_name ) {
		global $wpdb;

		if ( $wpdb->get_row( $wpdb->prepare( 'SELECT post_name FROM wp_posts WHERE post_name = %s', $post_name ) ) ) {
			return true;
		}
		return false;
	}
}
