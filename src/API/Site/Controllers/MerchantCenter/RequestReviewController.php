<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Services\MapiIssueResolutionService;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Merchant;
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
 * Class RequestReviewController
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
	 * @param Merchant              $merchant
	 * @param RequestReviewStatuses $request_review_statuses
	 * @param TransientsInterface   $transients
	 */
	public function __construct( RESTServer $server, Middleware $middleware, Merchant $merchant, RequestReviewStatuses $request_review_statuses, TransientsInterface $transients ) {
		parent::__construct( $server );
		$this->middleware              = $middleware;
		$this->merchant                = $merchant;
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
	 * Completes the in-app account-review action via the Issue Resolution triggeraction
	 * method.
	 *
	 * @return callable
	 */
	protected function post_review_request_callback(): callable {
		return function () {
			try {
				// Re-render so the action context used to trigger is current, not a cached copy.
				$review_status = $this->request_review_statuses->get_statuses_from_response(
					$this->get_account_review_status()
				);
				$action        = $review_status['reviewAction'] ?? null;

				// Abort if there is no available account-review action to request.
				if ( ! $action || empty( $action['isAvailable'] ) ) {
					do_action(
						'woocommerce_gla_request_review_failure',
						[
							'error'                 => 'ineligible',
							'account_review_status' => $review_status,
						]
					);
					throw new Exception( __( 'Your account is not eligible for a new review request.', 'google-listings-and-ads' ), 400 );
				}

				// Only the in-app action can be completed server-side; the redirect variant is opened in the browser.
				if ( 'in_app' !== ( $action['type'] ?? '' ) ) {
					do_action(
						'woocommerce_gla_request_review_failure',
						[
							'error'                 => 'redirect_required',
							'account_review_status' => $review_status,
						]
					);
					throw new Exception( __( 'This review request must be completed in Merchant Center.', 'google-listings-and-ads' ), 400 );
				}

				// Confirm the flow's required inputs (the "resolved all issues" checkbox);
				// triggeraction rejects an empty inputValues when the flow has a required input.
				$this->merchant->trigger_review_action(
					(string) ( $action['actionContext'] ?? '' ),
					(string) ( $action['flowId'] ?? '' ),
					$action['inputValues'] ?? []
				);

				return $this->set_under_review_status();

			} catch ( Exception $e ) {
				/**
				 * Treat an "already under review" response as success.
				 *
				 * The account can already be under review when the action is triggered, which the
				 * Merchant API reports as an error. There is no dedicated signal for this yet
				 * (triggeraction is allowlist-gated), so match the message, scoped to client errors
				 * so a 5xx is never misread as success. Prefer a structured check on
				 * MerchantApiException's response body / errors once the exact error shape for the
				 * "already under review" case has been confirmed against a live allowlisted account.
				 */
				$code = $e->getCode();
				if ( $code >= 400 && $code < 500 && stripos( $e->getMessage(), 'under review' ) !== false ) {
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
			'status'       => $this->request_review_statuses::UNDER_REVIEW,
			'issues'       => [],
			'reviewAction' => null,
		];

		// UNDER_REVIEW is an optimistic, client-side state: the Merchant API has no "under review"
		// signal, so it cannot be re-derived from a fresh render. Cache it for the longer
		// under-review window; once it expires the status reverts to the live severity-based
		// status from renderaccountissues.
		$this->set_cached_review_status( $new_status, $this->request_review_statuses->get_under_review_lifetime() );

		return new Response( $new_status );
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'status'       => [
				'type'        => 'string',
				'description' => __( 'The status of the last review.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'issues'       => [
				'type'        => 'array',
				'description' => __( 'The issues related to the Merchant Center to be reviewed and addressed before approval.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'items'       => [
					'type' => 'string',
				],
			],
			'reviewAction' => [
				'type'        => [ 'object', 'null' ],
				'description' => __( 'The account-review action to request a new review, or null when none is available.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'properties'  => [
					'type'        => [ 'type' => 'string' ],
					'isAvailable' => [ 'type' => 'boolean' ],
					'buttonLabel' => [ 'type' => 'string' ],
					'uri'         => [
						'type'        => 'string',
						'description' => __( 'Merchant Center URL for the redirect action; absent for in-app actions.', 'google-listings-and-ads' ),
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
		return 'merchant_account_review';
	}

	/**
	 * Save the Account Review Status data inside a transient for caching purposes.
	 *
	 * @param array    $value    The Account Review Status data to save in the transient
	 * @param int|null $lifetime Optional cache lifetime in seconds; defaults to the account review lifetime.
	 */
	private function set_cached_review_status( $value, ?int $lifetime = null ): void {
		$this->transients->set(
			TransientsInterface::MC_ACCOUNT_REVIEW,
			$value,
			$lifetime ?? $this->request_review_statuses->get_account_review_lifetime()
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
			$review_status = $this->render_review_status();
			$this->set_cached_review_status( $review_status );
		}

		return $review_status;
	}

	/**
	 * Render the review status for the GET response.
	 *
	 * The account issues are first rendered with in-app (built-in) actions. When the account
	 * has issues but the render returns no triggerable action - which happens when the account
	 * is not on Google's triggeraction allowlist - it re-renders with the Merchant Center
	 * redirect action so a working "Request review" control stays visible instead of silently
	 * disappearing.
	 *
	 * `actionContext`/`flowId`/`inputValues` are consumed only server-side by the POST handler
	 * (which re-renders fresh), so they are stripped from the client-facing payload here.
	 *
	 * @return array
	 * @throws Exception If the render fails.
	 */
	private function render_review_status(): array {
		$review_status = $this->request_review_statuses->get_statuses_from_response(
			$this->get_account_review_status()
		);

		if ( ! empty( $review_status['issues'] ) && empty( $review_status['reviewAction'] ) ) {
			$review_status = $this->request_review_statuses->get_statuses_from_response(
				$this->get_account_review_status( MapiIssueResolutionService::USER_INPUT_REDIRECT )
			);
		}

		if ( is_array( $review_status['reviewAction'] ?? null ) ) {
			unset(
				$review_status['reviewAction']['actionContext'],
				$review_status['reviewAction']['flowId'],
				$review_status['reviewAction']['inputValues']
			);
		}

		return $review_status;
	}

	/**
	 * Get Account Review Status
	 *
	 * @param string $user_input_action_option How user-input actions are rendered (in-app built-in vs redirect).
	 *
	 * @return array the response data
	 * @throws Exception When there is an invalid response.
	 */
	public function get_account_review_status( string $user_input_action_option = MapiIssueResolutionService::USER_INPUT_BUILT_IN ) {
		try {
			if ( ! $this->middleware->is_subaccount() ) {
				return [];
			}

			$response = $this->merchant->get_account_review_status( $user_input_action_option );
			do_action( 'woocommerce_gla_request_review_response', $response );
			return $response;
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );
			throw new Exception(
				$e->getMessage() ?? __( 'Error getting account review status.', 'google-listings-and-ads' ),
				$e->getCode()
			);
		}
	}
}
