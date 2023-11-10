<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\RequestReviewStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
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
	 * @var TransientsInterface
	 */
	private $transients;

	/**
	 * RequestReviewController constructor.
	 *
	 * @param RESTServer            $server
	 * @param Middleware            $middleware
	 * @param RequestReviewStatuses $request_review_statuses
	 * @param TransientsInterface   $transients
	 */
	public function __construct( RESTServer $server, Middleware $middleware, RequestReviewStatuses $request_review_statuses, TransientsInterface $transients ) {
		parent::__construct( $server );
		$this->middleware              = $middleware;
		$this->request_review_statuses = $request_review_statuses;
		$this->transients              = $transients;
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

		/**
		 * POST a request review for the current account
		 */
		$this->register_route(
			'mc/review',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->post_review_request_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
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
				return $this->prepare_item_for_response( $this->get_review_status(), $request );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function after requesting a review.
	 *
	 * @return callable
	 */
	protected function post_review_request_callback(): callable {
		return function () {
			try {

				// getting the current account status
				$account_review_status = $this->get_review_status();

				// Abort if it's in cool down period
				if ( $account_review_status['cooldown'] ) {
					do_action(
						'woocommerce_gla_request_review_failure',
						[
							'error'                 => 'cooldown',
							'account_review_status' => $account_review_status,
						]
					);
					throw new Exception( __( 'Your account is under cool down period and cannot request a new review.', 'google-listings-and-ads' ), 400 );
				}

				// Abort if there is no eligible region available
				if ( ! count( $account_review_status['reviewEligibleRegions'] ) ) {
					do_action(
						'woocommerce_gla_request_review_failure',
						[
							'error'                 => 'ineligible',
							'account_review_status' => $account_review_status,
						]
					);
					throw new Exception( __( 'Your account is not eligible for a new request review.', 'google-listings-and-ads' ), 400 );
				}

				$this->middleware->account_request_review( $account_review_status['reviewEligibleRegions'] );
				return $this->set_under_review_status();

			} catch ( Exception $e ) {
				/**
				 * Catch potential errors in any specific region API call.
				 *
				 * Notice due some inconsistencies with Google API we are not considering [Bad Request -> ...already under review...]
				 * as an exception. This is because we suspect that calling the API of a region is triggering other regions requests as well.
				 * This makes all the calls after the first to fail as they will be under review.
				 *
				 * The undesired call of this function for accounts under review is already prevented in a previous stage, so, there is no danger doing this.
				 */
				if ( strpos( $e->getMessage(), 'under review' ) !== false ) {
					return $this->set_under_review_status();
				}
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Set Under review Status in the cache and return the response
	 *
	 * @return Response With the Under review status
	 */
	private function set_under_review_status() {
		$new_status = [
			'issues'                => [],
			'cooldown'              => 0,
			'status'                => $this->request_review_statuses::UNDER_REVIEW,
			'reviewEligibleRegions' => [],
		];

		// Update Account status when successful response
		$this->set_cached_review_status( $new_status );

		return new Response( $new_status );
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'status'                => [
				'type'        => 'string',
				'description' => __( 'The status of the last review.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'cooldown'              => [
				'type'        => 'integer',
				'description' => __( 'Timestamp indicating if the user is in cool down period.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'issues'                => [
				'type'        => 'array',
				'description' => __( 'The issues related to the Merchant Center to be reviewed and addressed before approval.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'items'       => [
					'type' => 'string',
				],
			],
			'reviewEligibleRegions' => [
				'type'        => 'array',
				'description' => __( 'The region codes in which is allowed to request a new review.', 'google-listings-and-ads' ),
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
		$this->transients->set(
			TransientsInterface::MC_ACCOUNT_REVIEW,
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
		return $this->transients->get(
			TransientsInterface::MC_ACCOUNT_REVIEW,
		);
	}

	/**
	 * Get the Account Review Status. We attempt to get the cached version or create a request otherwise.
	 *
	 * @return null|array Returns NULL in case no data is available or an array with the Account Review Status data otherwise.
	 * @throws Exception If the get_account_review_status API call fails.
	 */
	private function get_review_status(): ?array {
		$review_status = $this->get_cached_review_status();

		if ( is_null( $review_status ) ) {
			$response      = $this->middleware->get_account_review_status();
			$review_status = $this->request_review_statuses->get_statuses_from_response( $response );
			$this->set_cached_review_status( $review_status );
		}

		return $review_status;
	}
}
