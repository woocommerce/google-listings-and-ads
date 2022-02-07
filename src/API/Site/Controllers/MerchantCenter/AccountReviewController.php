<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\RequestReview;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;

defined( 'ABSPATH' ) || exit;

/**
 * Class IssuesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class AccountReviewController extends BaseOptionsController {
	/**
	 * @var RequestReview Request review Helper
	 */
	protected RequestReview $request_review;


	/**
	 * AccountReviewController constructor.
	 *
	 * @param RESTServer    $server
	 * @param RequestReview $request_review
	 */
	public function __construct( RESTServer $server, RequestReview $request_review ) {
		parent::__construct( $server );
		$this->request_review = $request_review;
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
			// TODO: Temporary. Implement this after Google finishes new API implementation.
			$response = [
				'status'      => $this->request_review->is_allowed() ? RequestReview::REVIEW_STATUS_DISAPPROVED : RequestReview::REVIEW_STATUS_BLOCKED,
				'nextAttempt' => $this->request_review->get_next_attempt(),
				'issues'      => [
					'#1 Issue one',
					'#2 Issue two',
					'#3 Issue three',
					'#4 Issue four',
					'#5 Issue five',
					'#6 Issue six',
				],
			];

			return $this->prepare_item_for_response( $response, $request );
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'status'      => [
				'type'        => 'string',
				'description' => __( 'The status of the last review.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'nextAttempt' => [
				'type'        => 'int',
				'description' => __( 'The date when the last review was requested.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'issues'      => [
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
}
