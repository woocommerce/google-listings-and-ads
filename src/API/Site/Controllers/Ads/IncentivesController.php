<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsIncentives;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class IncentivesController
 *
 * Handles fetching and applying Choose-Your-Own (CYO) incentive offers via the Google Ads API.
 *
 * @since 3.3.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class IncentivesController extends BaseController {

	/**
	 * @var AdsIncentives
	 */
	protected $ads_incentives;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * IncentivesController constructor.
	 *
	 * @param RESTServer    $rest_server
	 * @param AdsIncentives $ads_incentives
	 * @param WC            $wc
	 */
	public function __construct( RESTServer $rest_server, AdsIncentives $ads_incentives, WC $wc ) {
		parent::__construct( $rest_server );
		$this->ads_incentives = $ads_incentives;
		$this->wc  = $wc;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/incentives',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_incentives_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->apply_incentive_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_apply_incentive_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * @return callable
	 */
	protected function get_incentives_callback(): callable {
		return function ( Request $request ) {
			$country_code  = $this->wc->get_base_country();
			$language_code = $this->get_language_code();

			$incentives = $this->ads_incentives->fetch_incentives( $country_code, $language_code );

			return $this->prepare_item_for_response( $incentives, $request );
		};
	}

	/**
	 * Get the ISO 639-1 language code from the WordPress locale.
	 *
	 * @return string
	 */
	protected function get_language_code(): string {
		$locale = get_locale();

		if ( empty( $locale ) ) {
			return 'en';
		}

		return strtolower( substr( $locale, 0, 2 ) );
	}

	/**
	 * @return callable
	 */
	protected function apply_incentive_callback(): callable {
		return function ( Request $request ) {
			try {
				$incentive_id = $request->get_param( 'id' );
				$country_code = $this->wc->get_base_country();

				$result = $this->ads_incentives->apply_incentive( $incentive_id, $country_code );

				return new Response( $result );
			} catch ( ExceptionWithResponseData $e ) {
				return $this->response_from_exception( $e );
			} catch ( Exception $e ) {
				return new Response(
					[ 'message' => $e->getMessage() ],
					500
				);
			}
		};
	}

	/**
	 * Get the request params for applying an incentive.
	 *
	 * @return array
	 */
	protected function get_apply_incentive_params(): array {
		return [
			'id' => [
				'type'              => 'string',
				'description'       => __( 'The incentive ID to apply.', 'google-listings-and-ads' ),
				'required'          => true,
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
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
			'type'                  => [
				'type'        => 'string',
				'description' => __( 'The offer type.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'termsAndConditionsUrl' => [
				'type'        => 'string',
				'description' => __( 'The consolidated terms and conditions URL.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'incentives'            => [
				'type'        => 'array',
				'description' => __( 'The available incentive offers.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'id'                    => [
							'type'        => 'string',
							'description' => __( 'The incentive ID.', 'google-listings-and-ads' ),
						],
						'type'                  => [
							'type'        => 'string',
							'description' => __( 'The incentive type.', 'google-listings-and-ads' ),
						],
						'offer'                 => [
							'type'        => 'string',
							'enum'        => [ 'low', 'medium', 'high' ],
							'description' => __( 'The offer level.', 'google-listings-and-ads' ),
						],
						'termsAndConditionsUrl' => [
							'type'        => 'string',
							'description' => __( 'The terms and conditions URL for this incentive.', 'google-listings-and-ads' ),
						],
						'requirement'           => [
							'type'       => 'object',
							'properties' => [
								'spend' => [
									'type'       => 'object',
									'properties' => [
										'awardAmount'    => [
											'type'       => 'object',
											'properties' => [
												'currencyCode' => [
													'type' => 'string',
												],
												'units'        => [
													'type' => 'string',
												],
											],
										],
										'requiredAmount' => [
											'type'       => 'object',
											'properties' => [
												'currencyCode' => [
													'type' => 'string',
												],
												'units'        => [
													'type' => 'string',
												],
											],
										],
									],
								],
							],
						],
					],
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
		return 'incentives';
	}
}
