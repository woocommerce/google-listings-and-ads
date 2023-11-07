<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\MerchantReport;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Exception;
use WP_REST_Request as Request;

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
			'mc/reports/programs',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_programs_report_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			'mc/reports/products',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_products_report_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for the programs report request.
	 *
	 * @return callable
	 */
	protected function get_programs_report_callback(): callable {
		return function ( Request $request ) {
			try {
				/** @var MerchantReport $merchant */
				$merchant = $this->container->get( MerchantReport::class );
				$data     = $merchant->get_report_data( 'free_listings', $this->prepare_query_arguments( $request ) );
				return $this->prepare_item_for_response( $data, $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback function for the products report request.
	 *
	 * @return callable
	 */
	protected function get_products_report_callback(): callable {
		return function ( Request $request ) {
			try {
				/** @var MerchantReport $merchant */
				$merchant = $this->container->get( MerchantReport::class );
				$data     = $merchant->get_report_data( 'products', $this->prepare_query_arguments( $request ) );
				return $this->prepare_item_for_response( $data, $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		$params = parent::get_collection_params();

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
			'free_listings' => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'subtotals' => $this->get_totals_schema(),
					],
				],
			],
			'products'      => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'id'        => [
							'type'        => 'string',
							'description' => __( 'Product ID.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'name'      => [
							'type'        => 'string',
							'description' => __( 'Product name.', 'google-listings-and-ads' ),
							'context'     => [ 'view', 'edit' ],
						],
						'subtotals' => $this->get_totals_schema(),
					],
				],
			],
			'intervals'     => [
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
			'totals'        => $this->get_totals_schema(),
			'next_page'     => [
				'type'        => 'string',
				'description' => __( 'Token to retrieve the next page of results.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
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
