<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Ads;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseReportsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReportsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Ads
 */
class ReportsController extends BaseReportsController {

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'ads/reports',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_reports_callback(),
					'permission_callback' => $this->get_permission_callback(),
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
				/** @var Ads $ads */
				$ads  = $this->container->get( Ads::class );
				$data = $ads->get_report_data( $this->prepare_query_arguments( $request ) );
				return $this->prepare_item_for_response( $data, $request );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'sales'     => [
				'type'        => 'number',
				'description' => __( 'Total sales amount.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'spend'     => [
				'type'        => 'number',
				'description' => __( 'Total spend.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
			],
			'campaigns' => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'id'     => [
							'type'        => 'integer',
							'description' => __( 'ID number.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'name'   => [
							'type'        => 'string',
							'description' => __( 'Campaign name.', 'google-listings-and-ads' ),
							'context'     => [ 'view', 'edit' ],
						],
						'sales'  => [
							'type'        => 'number',
							'description' => __( 'Sales amount.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'spend'  => [
							'type'        => 'number',
							'description' => __( 'Spend.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'status' => [
							'type'        => 'string',
							'enum'        => CampaignStatus::labels(),
							'description' => __( 'Campaign status.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
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
		return 'reports';
	}
}
