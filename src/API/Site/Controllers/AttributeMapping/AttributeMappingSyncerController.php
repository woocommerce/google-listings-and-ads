<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateAllProducts;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class for handling API requests for getting the current Syncing state
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping
 */
class AttributeMappingSyncerController extends BaseController {

	/**
	 * @var JobRepository
	 */
	protected $job_repository;

	/**
	 * AttributeMappingSyncerController constructor.
	 *
	 * @param RESTServer    $server
	 * @param JobRepository $job_repository
	 */
	public function __construct( RESTServer $server, JobRepository $job_repository ) {
		parent::__construct( $server );
		$this->job_repository = $job_repository;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/mapping/sync',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_sync_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}


	/**
	 * Callback function for getting the Attribute Mapping Sync State
	 *
	 * @return callable
	 */
	protected function get_sync_callback(): callable {
		return function( Request $request ) {
			try {
				$update_all_products_job = $this->job_repository->get( UpdateAllProducts::class );
				$state                   = [
					'is_syncing' => $update_all_products_job->is_syncing(),
				];
				return $this->prepare_item_for_response( $state, $request );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}


	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array The Schema properties
	 */
	protected function get_schema_properties(): array {
		return [
			'is_syncing' => [
				'description'       => __( 'Indicates if the products are currently syncing', 'google-listings-and-ads' ),
				'type'              => 'boolean',
				'validate_callback' => 'rest_validate_request_arg',
				'readonly'          => true,
				'context'           => [ 'view' ],
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
		return 'attribute_mapping_syncer';
	}
}
