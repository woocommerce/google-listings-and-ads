<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Proxy as Middleware;
use Exception;
use WP_REST_Request;
use WP_REST_Response;

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
					'callback'            => $this->create_or_link_account_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_create_or_link_account_schema(),
				],
				'schema' => $this->get_accounts_schema_callback(),
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
			try {
				return new WP_REST_Response( $this->middleware->get_ads_account_ids() );
			} catch ( Exception $e ) {
				return new WP_REST_Response( $e->getMessage(), 400 );
			}
		};
	}

	/**
	 * Get the callback function for creating or linking an account.
	 *
	 * @return callable
	 */
	protected function create_or_link_account_callback(): callable {
		return function( WP_REST_Request $request ) {
			try {
				$link_id    = absint( $request['id'] );
				$account_id = $link_id ?
					$this->middleware->link_ads_account( $link_id ) :
					$this->middleware->create_ads_account();

				return new WP_REST_Response( $account_id );
			} catch ( Exception $e ) {
				return new WP_REST_Response( $e->getMessage(), 400 );
			}
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

	/**
	 * Get the schema for creating or linking an account.
	 *
	 * @return array
	 */
	protected function get_create_or_link_account_schema(): array {
		return [
			'id' => [
				'type'              => 'number',
				'description'       => __( 'Google Ads Account ID to link.', 'google-listings-and-ads' ),
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => false,
			],
		];
	}
}
