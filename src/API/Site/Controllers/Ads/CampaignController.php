<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ControllerTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\ISO3166\ISO3166DataProvider;
use Exception;
use Psr\Container\ContainerInterface;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class CampaignController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class CampaignController extends BaseController {

	use ControllerTrait;
	use CountryCodeTrait;

	/**
	 * @var Ads
	 */
	protected $ads;

	/**
	 * BaseController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ) );
		$this->ads = $container->get( Ads::class );
		$this->iso = $container->get( ISO3166DataProvider::class );
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'ads/campaigns',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_campaigns_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->create_campaign_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_item_schema(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for listing campaigns.
	 *
	 * @return callable
	 */
	protected function get_campaigns_callback(): callable {
		return function() {
			try {
				return array_map( [ $this, 'prepare_item_for_response' ], $this->ads->get_campaigns() );
			} catch ( Exception $e ) {
				return new WP_REST_Response( $e->getMessage(), 400 );
			}
		};
	}

	/**
	 * Get the callback function for creating a campaign.
	 *
	 * @return callable
	 */
	protected function create_campaign_callback(): callable {
		return function( WP_REST_Request $request ) {
			return $this->ads->create_campaign( $request->get_json_params() );
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_item_schema(): array {
		return [
			'id'      => [
				'type'        => 'integer',
				'description' => __( 'ID number.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'name'    => [
				'type'              => 'string',
				'description'       => __( 'Descriptive campaign name.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'amount'  => [
				'type'              => 'number',
				'description'       => __( 'Budget amount in the local currency.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'country' => [
				'type'              => 'string',
				'description'       => __( 'Country code in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_country_code_validate_callback(),
				'required'          => true,
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
	protected function get_item_schema_name(): string {
		return 'campaign';
	}
}
