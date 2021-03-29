<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantReport;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReportsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ReportsController extends BaseReportsController {

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/reports',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_reports_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for the reports request.
	 *
	 * @return callable
	 */
	protected function get_reports_callback(): callable {
		return function( Request $request ) {
			try {
				/** @var MerchantReport $merchant */
				$merchant = $this->container->get( MerchantReport::class );
				$data     = $merchant->get_report_data( $this->prepare_query_arguments( $request ) );
				return $this->prepare_item_for_response( $data, $request );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Add collection parameters.
	 *
	 * @param array $params Initial set of collection parameters.
	 *
	 * @return array
	 */
	protected function add_collection_parameters( array $params ): array {
		$params['interval'] = [
			'description'       => __( 'Time interval to use for segments in the returned data.', 'google-listings-and-ads' ),
			'type'              => 'string',
			'enum'              => [
				'day',
			],
			'validate_callback' => 'rest_validate_request_arg',
		];
		return $params;
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'intervals' => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'interval'  => [
							'type'        => 'string',
							'description' => __( 'ID of this report segment.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'subtotals' => $this->get_totals_schema(),
					],
				],
			],
			'totals'    => $this->get_totals_schema(),
		];
	}

	/**
	 * Return schema for total fields.
	 *
	 * @return array
	 */
	protected function get_totals_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'clicks'      => [
					'type'        => 'integer',
					'description' => __( 'Clicks.', 'google-listings-and-ads' ),
					'context'     => [ 'view' ],
				],
				'impressions' => [
					'type'        => 'integer',
					'description' => __( 'Impressions.', 'google-listings-and-ads' ),
					'context'     => [ 'view' ],
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
		return 'reports';
	}
}
