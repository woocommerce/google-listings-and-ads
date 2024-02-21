<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\ProductSyncStats;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use WP_REST_Response as Response;
use WP_REST_Request as Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductStatisticsController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class ProductStatisticsController extends BaseOptionsController {

	/**
	 * The MerchantProducts object.
	 *
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * Helper class to count scheduled sync jobs.
	 *
	 * @var ProductSyncStats
	 */
	protected $sync_stats;


	/**
	 * ProductStatisticsController constructor.
	 *
	 * @param RESTServer       $server
	 * @param MerchantStatuses $merchant_statuses
	 * @param ProductSyncStats $sync_stats
	 */
	public function __construct( RESTServer $server, MerchantStatuses $merchant_statuses, ProductSyncStats $sync_stats ) {
		parent::__construct( $server );
		$this->merchant_statuses = $merchant_statuses;
		$this->sync_stats        = $sync_stats;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/product-statistics',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_product_statistics_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
		$this->register_route(
			'mc/product-statistics/refresh',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_product_statistics_refresh_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}

	/**
	 * Get the callback function for returning product statistics.
	 *
	 * @return callable
	 */
	protected function get_product_statistics_read_callback(): callable {
		return function ( Request $request ) {
			return $this->get_product_status_stats( $request );
		};
	}
	/**
	 * Get the callback function for getting re-calculated product statistics.
	 *
	 * @return callable
	 */
	protected function get_product_statistics_refresh_callback(): callable {
		return function ( Request $request ) {
			return $this->get_product_status_stats( $request, true );
		};
	}

	/**
	 * Get the overall product status statistics array.
	 *
	 * @param Request $request
	 * @param bool    $force_refresh True to force a refresh of the product status statistics.
	 *
	 * @return Response
	 */
	protected function get_product_status_stats( Request $request, bool $force_refresh = false ): Response {
		try {
			$response = $this->merchant_statuses->get_product_statistics( $force_refresh );

			$response['scheduled_sync'] = $this->sync_stats->get_count();

			return $this->prepare_item_for_response( $response, $request );
		} catch ( Exception $e ) {
			return $this->response_from_exception( $e );
		}
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'timestamp'      => [
				'type'        => 'number',
				'description' => __( 'Timestamp reflecting when the product status statistics were last generated.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'statistics'     => [
				'type'        => 'object',
				'description' => __( 'Merchant Center product status statistics.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'properties'  => [
					'active'      => [
						'type'        => 'integer',
						'description' => __( 'Active products.', 'google-listings-and-ads' ),
						'context'     => [ 'view' ],
					],
					'expiring'    => [
						'type'        => 'integer',
						'description' => __( 'Expiring products.', 'google-listings-and-ads' ),
						'context'     => [ 'view' ],
					],
					'pending'     => [
						'type'        => 'number',
						'description' => __( 'Pending products.', 'google-listings-and-ads' ),
						'context'     => [ 'view' ],
					],
					'disapproved' => [
						'type'        => 'number',
						'description' => __( 'Disapproved products.', 'google-listings-and-ads' ),
						'context'     => [ 'view' ],
					],
					'not_synced'  => [
						'type'        => 'number',
						'description' => __( 'Products not uploaded.', 'google-listings-and-ads' ),
						'context'     => [ 'view' ],
					],
				],
			],
			'scheduled_sync' => [
				'type'        => 'number',
				'description' => __( 'Amount of scheduled jobs which will sync products to Google.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'loading'        => [
				'type'        => 'boolean',
				'description' => __( 'Whether the product statistics are loading.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'error'          => [
				'type'        => 'string',
				'description' => __( 'Error message in case of failure', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'default'     => null,
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
		return 'product_statistics';
	}
}
