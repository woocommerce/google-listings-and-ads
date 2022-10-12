<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateSyncableProductsCount;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncableProductsCountController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class SyncableProductsCountController extends BaseOptionsController {

	/**
	 * @var JobRepository
	 */
	protected $job_repository;

	/**
	 * SyncableProductsCountController constructor.
	 *
	 * @param RESTServer    $server
	 * @param JobRepository $job_repository
	 */
	public function __construct( RESTServer $server, JobRepository $job_repository ) {
		parent::__construct( $server );
		$this->job_repository = $job_repository;
	}


	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$this->register_route(
			'mc/syncable-products-count',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_syncable_products_count_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->update_syncable_products_count_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback function for marking setup complete.
	 *
	 * @return callable
	 */
	protected function get_syncable_products_count_callback(): callable {
		return function( Request $request ) {
			$response = [
				'count' => null,
			];

			$count = $this->options->get( OptionsInterface::SYNCABLE_PRODUCTS_COUNT );

			if ( isset( $count ) ) {
				$response['count'] = (int) $count;
			}

			return $this->prepare_item_for_response( $response, $request );
		};
	}

	/**
	 * Get the callback for syncing shipping.
	 *
	 * @return callable
	 */
	protected function update_syncable_products_count_callback(): callable {
		return function( Request $request ) {
			$this->options->delete( OptionsInterface::SYNCABLE_PRODUCTS_COUNT );
			$this->options->delete( OptionsInterface::SYNCABLE_PRODUCTS_COUNT_INTERMEDIATE_DATA );

			$job = $this->job_repository->get( UpdateSyncableProductsCount::class );
			$job->schedule();

			return new Response(
				[
					'status'  => 'success',
					'message' => __( 'Successfully scheduled a job to update the number of syncable products.', 'google-listings-and-ads' ),
				],
				201
			);
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'count' => [
				'type'        => 'number',
				'description' => __( 'The number of products that are ready to be synced to Google.', 'google-listings-and-ads' ),
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
		return 'syncable_products_count';
	}
}
