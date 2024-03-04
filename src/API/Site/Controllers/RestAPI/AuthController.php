<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use Jetpack_Options;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class AuthController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\RestAPI
 */
class AuthController extends BaseController {

	/**
	 * Mapping between the client page name and its path.
	 * The first value is also used as a default,
	 * and changing the order of keys/values may affect things below.
	 *
	 * @var string[]
	 */
	private const NEXT_PATH_MAPPING = [
		'setup-mc'  => '/google/setup-mc',
		'reconnect' => '/google/settings',
	];

	/**
	 * AuthController constructor.
	 *
	 * @param RESTServer $server
	 */
	public function __construct( RESTServer $server ) {
		parent::__construct( $server );
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$this->register_route(
			'rest-api/authorize',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_authorize_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_auth_params(),
				],
			]
		);
	}

	/**
	 * Get the callback function for the authorization request.
	 *
	 * @return callable
	 */
	protected function get_authorize_callback(): callable {
		return function ( Request $request ) {
			/*
			The full auth URL example:

			https://public-api.wordpress.com/oauth2/authorize?
			client_id=CLIENT_ID&
			redirect_uri=PARTNER_REDIRECT_URL&
			response_type=code&
			blog=BLOD_ID&
			scope=wc-partner-access&
			state=BASE64_ENCODED_STRING

			Content of state is an URL query string where the value of its parameter "redirect_url" is being URL encoded.
			E.g.
			nonce=nonce-123&redirect_url=https%3A%2F%2Fmerchant-site.example.com%2Fwp-admin%2Fadmin.php%3Fpage%3Dwc-admin%26path%3D%2Fgoogle%2Fsetup-mc

			where its URL decoded version is:
			nonce=nonce-123&redirect_url=https://merchant-site.example.com/wp-admin/admin.php?page=wc-admin&path=/google/setup-mc

			Ref: https://developer.wordpress.com/docs/oauth2/
			*/

			try {
				$auth_url = 'https://public-api.wordpress.com/oauth2/authorize';

				// TODO: Call an API by Google with merchant information and get the below data.
				// We'd probably need use WCS to communicate with the new API.
				$client_id    = '91299';
				$redirect_uri = 'https://woo.com';
				$nonce        = 'nonce-123';

				$response_type = 'code';
				$blog_id       = Jetpack_Options::get_option( 'id' );
				$scope         = 'wc-partner-access';

				$next                  = $request->get_param( 'next_page_name' );
				$path                  = self::NEXT_PATH_MAPPING[ $next ];
				$merchant_redirect_url = urlencode_deep( admin_url( "admin.php?page=wc-admin&path={$path}" ) );

				$state = base64_encode(
					build_query(
						[
							'nonce'        => $nonce,
							'redirect_url' => $merchant_redirect_url,
						]
					)
				);

				$auth_url = esc_url_raw(
					add_query_arg(
						[
							'blog'          => $blog_id,
							'client_id'     => $client_id,
							'redirect_uri'  => $redirect_uri,
							'response_type' => $response_type,
							'scope'         => $scope,
							'state'         => $state,
						],
						$auth_url
					)
				);

				$response = [
					'auth_url' => $auth_url,
				];

				return $this->prepare_item_for_response( $response, $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the query params for the authorize request.
	 *
	 * @return array
	 */
	protected function get_auth_params(): array {
		return [
			'next_page_name' => [
				'description'       => __( 'Indicates the next page name mapped to the redirect URL when redirected back from Google WPCOM App authorization.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'default'           => array_key_first( self::NEXT_PATH_MAPPING ),
				'enum'              => array_keys( self::NEXT_PATH_MAPPING ),
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'auth_url' => [
				'type'        => 'string',
				'description' => __( 'The authorization URL for granting access to Google WPCOM App.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'rest_api_authorize';
	}
}
