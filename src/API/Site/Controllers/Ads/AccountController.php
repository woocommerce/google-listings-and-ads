<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class AccountController extends BaseController {

	/**
	 * @var Middleware
	 */
	protected $middleware;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 * @param Middleware $middleware
	 */
	public function __construct( RESTServer $server, Middleware $middleware ) {
		parent::__construct( $server );
		$this->middleware = $middleware;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'ads/accounts',
			[
				[
					'methods'              => TransportMethods::READABLE,
					'callback'             => $this->get_accounts_callback(),
					'permissions_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_accounts_schema(),
			]
		);

		$this->register_route(
			'ads/accounts',
			[
				[
					'methods'              => TransportMethods::CREATABLE,
					'callback'             => $this->get_create_account_callback(),
					'permissions_callback' => $this->get_permission_callback(),
					'args'                 => $this->create_accounts_schema(),
				],
			]
		);

		$this->register_route(
			'ads/accounts/(?P<id>\d+)',
			[
				[
					'methods'              => TransportMethods::CREATABLE,
					'callback'             => $this->get_link_account_callback(),
					'permissions_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback function for the list accounts request.
	 *
	 * @return callable
	 */
	protected function get_accounts_callback(): callable {
		return function() {
			return $this->middleware->get_ads_account_ids();
		};
	}

	/**
	 * Get the callback function for the create account request.
	 *
	 * @return callable
	 */
	protected function get_create_account_callback(): callable {
		return function( WP_REST_Request $request ) {
			return $this->middleware->create_ads_account( $request->get_json_params() );
		};
	}

	/**
	 * Get the callback function for the link account request.
	 *
	 * @return callable
	 */
	protected function get_link_account_callback(): callable {
		return function( WP_REST_Request $request ) {
			return $this->middleware->link_ads_account( absint( $request['id'] ) );
		};
	}

	/**
	 * @return array
	 */
	protected function get_accounts_schema(): array {
		return [
			'type'  => 'array',
			'items' => [
				'type'        => 'integer',
				'description' => __( 'Google Ads account ID.', 'google-listings-and-ads' ),
			],
		];
	}

	/**
	 * @return array
	 */
	protected function create_accounts_schema(): array {
		return [
			'descriptive_name' => [
				'type'        => 'string',
				'description' => __( 'Unique descriptive name to use for a new account.', 'google-listings-and-ads' ),
			],
			'currency_code'    => [
				'type'        => 'string',
				'description' => __( '3 letter currency code.', 'google-listings-and-ads' ),
			],
			'time_zone'        => [
				'type'        => 'string',
				'description' => __( 'Time zone name.', 'google-listings-and-ads' ),
			],
		];
	}
}
