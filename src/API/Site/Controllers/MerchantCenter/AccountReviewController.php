<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
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
	 * AccountReviewController constructor.
	 *
	 * @param RESTServer $server
	 */
	public function __construct( RESTServer $server ) {
		parent::__construct( $server );
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
			// Statuses: APPROVED, UNDER_REVIEW, DISAPPROVED, WARNING, BLOCKED
			$response = [
				'status' => 'DISAPPROVED',
				'issues' => [
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
