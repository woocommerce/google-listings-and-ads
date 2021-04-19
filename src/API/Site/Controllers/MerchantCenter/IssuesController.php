<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantIssues;
use WP_REST_Response as Response;
use WP_REST_Request as Request;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class IssuesController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class IssuesController extends BaseOptionsController {

	/**
	 * The MerchantIssues object.
	 *
	 * @var MerchantIssues
	 */
	protected $mc_issues;

	/**
	 * IssuesController constructor.
	 *
	 * @param RESTServer     $server
	 * @param MerchantIssues $mc_issues
	 */
	public function __construct( RESTServer $server, MerchantIssues $mc_issues ) {
		parent::__construct( $server );
		$this->mc_issues = $mc_issues;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$types = implode( '|', $this->mc_issues->get_issue_types() );
		$this->register_route(
			'mc/issues(/(?P<type_filter>(' . $types . ')))?',
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
				$issues = $this->mc_issues->get( $type_filter, $per_page, $page );
				return $this->prepare_item_for_response(
					[
						'issues' => $issues['results'],
						'total'  => $issues['count'],
						'page'   => $page,
					],
					$request
				);
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
