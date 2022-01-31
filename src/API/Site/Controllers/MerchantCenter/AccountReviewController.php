<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\RequestReview;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;

defined('ABSPATH') || exit;

/**
 * Class IssuesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class AccountReviewController extends BaseOptionsController
{
	/**
	 * @var RequestReview Request review Helper
	 */
	protected RequestReview $requestReview;


	/**
	 * AccountReviewController constructor.
	 *
	 * @param RESTServer $server
	 * @param RequestReview $requestReview
	 */
	public function __construct(RESTServer $server, RequestReview $requestReview)
	{
		parent::__construct($server);
		$this->requestReview = $requestReview;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void
	{

		/**
		 * GET information regarding the current Account Status
		 */
		$this->register_route(
			'mc/review',
			[
				[
					'methods' => TransportMethods::READABLE,
					'callback' => $this->get_review_read_callback(),
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
	protected function get_review_read_callback(): callable
	{
		return function (Request $request) {

			// TODO: Temporary. Implement this after Google finishes new API implementation.
			$response = array(
				'reviewStatus' => RequestReview::REVIEW_STATUS_DISAPPROVED,
				'nextReviewRequestAttempt' => $this->requestReview->get_next_review_request_attempt(),
				'issues' => ["#1", "#2", "#3"]
			);

			return $this->prepare_item_for_response($response, $request);
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array
	{
		return [
			'reviewStatus' => [
				'type' => 'string',
				'description' => __('The status of the last review.', 'google-listings-and-ads'),
				'context' => ['view'],
				'readonly' => true,
			],
			'nextReviewRequestAttempt' => [
				'type' => 'int',
				'description' => __('The date when the last review was requested.', 'google-listings-and-ads'),
				'context' => ['view'],
				'readonly' => true,
			],
			'reviewIssues' => [
				'type' => 'array',
				'description' => __('The issues related to the Merchant Center to be reviewed and addressed before approval.', 'google-listings-and-ads'),
				'context' => ['view'],
				'readonly' => true,
				'items' => [
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
	protected function get_schema_title(): string
	{
		return 'merchant_account_review';
	}
}
