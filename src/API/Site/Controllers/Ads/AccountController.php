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
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_accounts_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->create_account_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_accounts_schema_callback(),
			]
		);

		$this->register_route(
			'ads/accounts/(?P<id>\d+)',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->link_account_callback(),
					'permission_callback' => $this->get_permission_callback(),
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
	protected function create_account_callback(): callable {
		return function() {
			return $this->middleware->create_ads_account();
		};
	}

	/**
	 * Get the callback function for the link account request.
	 *
	 * @return callable
	 */
	protected function link_account_callback(): callable {
		return function( WP_REST_Request $request ) {
			return $this->middleware->link_ads_account( absint( $request['id'] ) );
		};
	}

	/**
	 * Get the callback to obtain the accounts schema.
	 *
	 * @return callable
	 */
	protected function get_accounts_schema_callback(): callable {
		return function() {
			return [
				'$schema' => 'http://json-schema.org/draft-04/schema#',
				'title'   => 'accounts',
				'type'    => 'array',
				'items'   => [
					'type'        => 'integer',
					'description' => __( 'Google Ads account ID.', 'google-listings-and-ads' ),
				],
			];
		};
	}
}
