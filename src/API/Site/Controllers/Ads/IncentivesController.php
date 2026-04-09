<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsIncentives;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class IncentivesController
 *
 * Handles fetching Choose-Your-Own (CYO) incentive offers from the Google Ads API.
 *
 * @since 3.3.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class IncentivesController extends BaseController {

	/**
	 * @var AdsIncentives
	 */
	protected $ads;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * IncentivesController constructor.
	 *
	 * @param RESTServer    $rest_server
	 * @param AdsIncentives $ads
	 * @param WC            $wc
	 */
	public function __construct( RESTServer $rest_server, AdsIncentives $ads, WC $wc ) {
		parent::__construct( $rest_server );
		$this->ads = $ads;
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

			$incentives = $this->ads->fetch_incentives( $country_code, $language_code );

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
