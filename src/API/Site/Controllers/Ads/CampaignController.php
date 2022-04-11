<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignType;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\GoogleHelperAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use DateTime;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class CampaignController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class CampaignController extends BaseController implements GoogleHelperAwareInterface, ISO3166AwareInterface {

	use CountryCodeTrait;

	/**
	 * @var AdsCampaign
	 */
	protected $ads_campaign;

	/**
	 * CampaignController constructor.
	 *
	 * @param RESTServer  $server
	 * @param AdsCampaign $ads_campaign
	 */
	public function __construct( RESTServer $server, AdsCampaign $ads_campaign ) {
		parent::__construct( $server );
		$this->ads_campaign = $ads_campaign;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
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
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			'ads/campaigns/(?P<id>[\d]+)',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_campaign_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->edit_campaign_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_edit_schema(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->delete_campaign_callback(),
					'permission_callback' => $this->get_permission_callback(),
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
		return function( Request $request ) {
			try {
				return array_map(
					function( $campaign ) use ( $request ) {
						$data = $this->prepare_item_for_response( $campaign, $request );
						return $this->prepare_response_for_collection( $data );
					},
					$this->ads_campaign->get_campaigns()
				);
			} catch ( ExceptionWithResponseData $e ) {
				return new Response( $e->get_response_data( true ), $e->getCode() ?: 400 );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function for creating a campaign.
	 *
	 * @return callable
	 */
	protected function create_campaign_callback(): callable {
		return function( Request $request ) {
			try {
				$fields = array_intersect_key( $request->get_json_params(), $this->get_schema_properties() );

				// Set the default value of campaign name.
				if ( empty( $fields['name'] ) ) {
					$current_date_time = ( new DateTime( 'now', wp_timezone() ) )->format( 'Y-m-d H:i:s' );
					$fields['name']    = sprintf(
					/* translators: %s: current date time. */
						__( 'Campaign %s', 'google-listings-and-ads' ),
						$current_date_time
					);
				}

				$campaign = $this->ads_campaign->create_campaign( $fields );

				return $this->prepare_item_for_response( $campaign, $request );
			} catch ( ExceptionWithResponseData $e ) {
				return new Response( $e->get_response_data( true ), $e->getCode() ?: 400 );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function for listing a single campaign.
	 *
	 * @return callable
	 */
	protected function get_campaign_callback(): callable {
		return function( Request $request ) {
			try {
				$id       = absint( $request['id'] );
				$campaign = $this->ads_campaign->get_campaign( $id );

				if ( empty( $campaign ) ) {
					return new Response(
						[
							'message' => __( 'Campaign is not available.', 'google-listings-and-ads' ),
							'id'      => $id,
						],
						404
					);
				}

				return $this->prepare_item_for_response( $campaign, $request );
			} catch ( ExceptionWithResponseData $e ) {
				return new Response( $e->get_response_data( true ), $e->getCode() ?: 400 );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function for editing a campaign.
	 *
	 * @return callable
	 */
	protected function edit_campaign_callback(): callable {
		return function( Request $request ) {
			try {
				$fields = array_intersect_key( $request->get_json_params(), $this->get_edit_schema() );
				if ( empty( $fields ) ) {
					return new Response(
						[
							'status'  => 'invalid_data',
							'message' => __( 'Invalid edit data.', 'google-listings-and-ads' ),
						],
						400
					);
				}

				$campaign_id = $this->ads_campaign->edit_campaign( absint( $request['id'] ), $fields );

				return [
					'status'  => 'success',
					'message' => __( 'Successfully edited campaign.', 'google-listings-and-ads' ),
					'id'      => $campaign_id,
				];
			} catch ( ExceptionWithResponseData $e ) {
				return new Response( $e->get_response_data( true ), $e->getCode() ?: 400 );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function for deleting a campaign.
	 *
	 * @return callable
	 */
	protected function delete_campaign_callback(): callable {
		return function( Request $request ) {
			try {
				$deleted_id = $this->ads_campaign->delete_campaign( absint( $request['id'] ) );

				return [
					'status'  => 'success',
					'message' => __( 'Successfully deleted campaign.', 'google-listings-and-ads' ),
					'id'      => $deleted_id,
				];
			} catch ( ExceptionWithResponseData $e ) {
				return new Response( $e->get_response_data( true ), $e->getCode() ?: 400 );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the schema for fields we are allowed to edit.
	 *
	 * @return array
	 */
	protected function get_edit_schema(): array {
		$allowed = [
			'name',
			'status',
			'amount',
		];

		$fields = array_intersect_key( $this->get_schema_properties(), array_flip( $allowed ) );

		// Unset required to allow editing individual fields.
		array_walk(
			$fields,
			function( &$value ) {
				unset( $value['required'] );
			}
		);

		return $fields;
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'id'                 => [
				'type'        => 'integer',
				'description' => __( 'ID number.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'name'               => [
				'type'              => 'string',
				'description'       => __( 'Descriptive campaign name.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => false,
			],
			'status'             => [
				'type'              => 'string',
				'enum'              => CampaignStatus::labels(),
				'description'       => __( 'Campaign status.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'type'               => [
				'type'              => 'string',
				'enum'              => CampaignType::labels(),
				'description'       => __( 'Campaign type.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			'amount'             => [
				'type'              => 'number',
				'description'       => __( 'Daily budget amount in the local currency.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
			],
			'country'            => [
				'type'              => 'string',
				'description'       => __( 'Country code of sale country in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_supported_country_code_validate_callback(),
				'readonly'          => true,
			],
			'targeted_locations' => [
				'type'              => 'array',
				'description'       => __( 'The locations that an Ads campaign is targeting in ISO 3166-1 alpha-2 format.', 'google-listings-and-ads' ),
				'context'           => [ 'view', 'edit' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_supported_country_code_validate_callback(),
				'required'          => true,
				'items'             => [
					'type' => 'string',
				],
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
		return 'campaign';
	}
}
