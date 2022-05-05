<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantStatuses;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class IssuesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class IssuesController extends BaseOptionsController {

	/**
	 * @var MerchantStatuses
	 */
	protected $merchant_statuses;

	/**
	 * @var ProductHelper
	 */
	protected $product_helper;

	/**
	 * IssuesController constructor.
	 *
	 * @param RESTServer       $server
	 * @param MerchantStatuses $merchant_statuses
	 * @param ProductHelper    $product_helper
	 */
	public function __construct( RESTServer $server, MerchantStatuses $merchant_statuses, ProductHelper $product_helper ) {
		parent::__construct( $server );
		$this->merchant_statuses = $merchant_statuses;
		$this->product_helper    = $product_helper;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/issues(/(?P<type_filter>[a-z]+))?',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_issues_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}

	/**
	 * Get the callback function for returning account and product issues.
	 *
	 * @return callable
	 */
	protected function get_issues_read_callback(): callable {
		return function( Request $request ) {
			$type_filter = $request['type_filter'];
			$per_page    = intval( $request['per_page'] );
			$page        = max( 1, intval( $request['page'] ) );

			try {
				$results         = $this->merchant_statuses->get_issues( $type_filter, $per_page, $page );
				$results['page'] = $page;
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}

			// Replace variation IDs with parent ID (for Edit links).
			foreach ( $results['issues'] as &$issue ) {
				$issue = apply_filters( 'woocommerce_gla_merchant_issue_override', $issue );

				if ( empty( $issue['product_id'] ) ) {
					continue;
				}
				try {
					$issue['product_id'] = $this->product_helper->maybe_swap_for_parent_id( $issue['product_id'] );
				} catch ( InvalidValue $e ) {
					// Don't include invalid products
					do_action(
						'woocommerce_gla_debug_message',
						sprintf( 'Merchant Center product ID %s not found in this WooCommerce store.', $issue['product_id'] ),
						__METHOD__,
					);

					continue;
				}
			}

			return $this->prepare_item_for_response( $results, $request );
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'issues' => [
				'type'        => 'array',
				'description' => __( 'The issues related to the Merchant Center account.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'type'                 => [
							'type'        => 'string',
							'description' => __( 'Issue type.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'product'              => [
							'type'        => 'string',
							'description' => __( 'Affected product.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'product_id'           => [
							'type'        => 'numeric',
							'description' => __( 'The WooCommerce product ID.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'code'                 => [
							'type'        => 'string',
							'description' => __( 'Internal Google code for issue.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'issue'                => [
							'type'        => 'string',
							'description' => __( 'Descriptive text of the issue.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'action'               => [
							'type'        => 'string',
							'description' => __( 'Descriptive text of action to take.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'action_url'           => [
							'type'        => 'string',
							'description' => __( 'Documentation URL for issue and/or action.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'severity'             => [
							'type'        => 'string',
							'description' => __( 'Severity level of the issue: warning or error.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
						'applicable_countries' => [
							'type'        => 'array',
							'description' => __( 'Country codes of the product audience.', 'google-listings-and-ads' ),
							'context'     => [ 'view' ],
						],
					],
				],
			],
			'total'  => [
				'type'     => 'numeric',
				'context'  => [ 'view' ],
				'readonly' => true,
			],
			'page'   => [
				'type'     => 'numeric',
				'context'  => [ 'view' ],
				'readonly' => true,
			],
		];
	}


	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return [
			'context'  => $this->get_context_param( [ 'default' => 'view' ] ),
			'page'     => [
				'description'       => __( 'Page of data to retrieve.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'per_page' => [
				'description'       => __( 'Maximum number of rows to be returned in result data.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'default'           => 0,
				'minimum'           => 0,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
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
		return 'merchant_issues';
	}
}
