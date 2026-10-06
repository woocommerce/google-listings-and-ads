<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SyncHealth;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth\ProductJourney;
use Automattic\WooCommerce\GoogleListingsAndAds\SyncHealth\SyncHealthService;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only endpoints for the Sync Status page.
 *
 * - GET wc/gla/sync-health       Overall state, the four pillars, and queue totals.
 * - GET wc/gla/sync-health/jobs  Per-job Action Scheduler counts and states.
 * - POST wc/gla/sync-health/review-refresh  Start fetching review results from Google (rate limited).
 * - GET wc/gla/sync-health/product-journey  Products per journey segment, for the Product Feed overview.
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\SyncHealth
 */
class SyncHealthController extends BaseController {

	/**
	 * @var SyncHealthService
	 */
	protected $sync_health;

	/**
	 * @var ProductJourney
	 */
	protected $product_journey;

	/**
	 * SyncHealthController constructor.
	 *
	 * @param RESTServer        $server
	 * @param SyncHealthService $sync_health
	 * @param ProductJourney    $product_journey
	 */
	public function __construct( RESTServer $server, SyncHealthService $sync_health, ProductJourney $product_journey ) {
		parent::__construct( $server );
		$this->sync_health     = $sync_health;
		$this->product_journey = $product_journey;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'sync-health',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_summary_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'refresh' => [
							'description'       => __( 'Skip the short-lived cache and recompute.', 'google-listings-and-ads' ),
							'type'              => 'boolean',
							'default'           => false,
							'validate_callback' => 'rest_validate_request_arg',
						],
					],
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);

		$this->register_route(
			'sync-health/jobs',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_jobs_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'sync-health/review-refresh',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_review_refresh_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);

		$this->register_route(
			'sync-health/product-journey',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_product_journey_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Get the callback for the health summary.
	 *
	 * @return callable
	 */
	protected function get_summary_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->prepare_item_for_response(
					$this->sync_health->get_summary( (bool) $request->get_param( 'refresh' ) ),
					$request
				);
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback for the per-job breakdown.
	 *
	 * @return callable
	 */
	protected function get_jobs_callback(): callable {
		return function () {
			try {
				return new Response( $this->sync_health->get_jobs() );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback that starts a review results fetch.
	 *
	 * @return callable
	 */
	protected function get_review_refresh_callback(): callable {
		return function () {
			try {
				return new Response( $this->sync_health->request_review_refresh() );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the callback for the product journey counts.
	 *
	 * @return callable
	 */
	protected function get_product_journey_callback(): callable {
		return function () {
			try {
				$segments = $this->product_journey->get_counts();

				return new Response(
					[
						'segments' => $segments,
						'total'    => array_sum( $segments ),
					]
				);
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'generated_at' => [
				'type'        => 'integer',
				'description' => __( 'Unix timestamp when the summary was computed.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'state'        => [
				'type'        => 'string',
				'description' => __( 'Overall sync state.', 'google-listings-and-ads' ),
				'enum'        => [
					SyncHealthService::STATE_HEALTHY,
					SyncHealthService::STATE_SYNCING,
					SyncHealthService::STATE_ATTENTION,
					SyncHealthService::STATE_PAUSED,
					SyncHealthService::STATE_DISCONNECTED,
				],
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'reasons'      => [
				'type'        => 'array',
				'description' => __( 'Reasons behind the overall state, most severe first.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'code'     => [ 'type' => 'string' ],
						'pillar'   => [ 'type' => 'string' ],
						'severity' => [ 'type' => 'string' ],
						'data'     => [ 'type' => 'object' ],
					],
				],
			],
			'pillars'      => [
				'type'        => 'object',
				'description' => __( 'Connection, products, shipping, and Google review health.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'queue'        => [
				'type'        => 'object',
				'description' => __( 'Totals across all background jobs.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
		];
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'sync_health';
	}
}
