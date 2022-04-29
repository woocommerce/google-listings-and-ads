<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\Transients;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Psr\Container\ContainerInterface;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class IssuesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class RequestReviewController extends BaseOptionsController {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var Middleware
	 */
	protected $middleware;

	/**
	 * @var RequestReviewStatuses
	 */
	protected $request_review_statuses;

	/**
	 * RequestReviewController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
		parent::__construct( $container->get( RESTServer::class ) );
		$this->middleware              = $container->get( Middleware::class );
		$this->request_review_statuses = $container->get( RequestReviewStatuses::class );
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		/**
		 * GET information regarding the current Account Status
		 */
		$this->register_route(
			'mc/review',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_review_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}

	/**
	 * Get the callback function for returning the review status.
	 *
	 * @return callable
	 */
	protected function get_review_read_callback(): callable {
		return function ( Request $request ) {
			try {
				$review_status = $this->get_cached_review_status();

				if ( is_null( $review_status ) ) {
					$response      = $this->middleware->get_account_review_status();
					$review_status = $this->request_review_statuses->get_statuses_from_response( $response );
					$this->set_cached_review_status( $review_status );
				}

				return $this->prepare_item_for_response( $review_status, $request );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
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
			'status'   => [
				'type'        => 'string',
				'description' => __( 'The status of the last review.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'cooldown' => [
				'type'        => 'integer',
				'description' => __( 'Timestamp indicating if the user is in cool down period.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'issues'   => [
				'type'        => 'array',
				'description' => __( 'The issues related to the Merchant Center to be reviewed and addressed before approval.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'items'       => [
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
		return 'merchant_account_review';
	}

	/**
	 * Save the Account Review Status data inside a transient for caching purposes.
	 *
	 * @param array $value The Account Review Status data to save in the transient
	 */
	private function set_cached_review_status( $value ): void {
		$this->container->get( TransientsInterface::class )->set(
			Transients::MC_ACCOUNT_REVIEW,
			$value,
			$this->request_review_statuses->get_account_review_lifetime()
		);
	}

	/**
	 * Get the Account Review Status data inside a transient for caching purposes.
	 *
	 * @return null|array Returns NULL in case no data is available or an array with the Account Review Status data otherwise.
	 */
	private function get_cached_review_status(): ?array {
		return $this->container->get( TransientsInterface::class )->get(
			Transients::MC_ACCOUNT_REVIEW,
		);
	}
}
