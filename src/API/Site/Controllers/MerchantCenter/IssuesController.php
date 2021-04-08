<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantIssues;
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
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
		$this->register_route(
			'mc/issues/' . $this->mc_issues::TYPE_PRODUCT . '/search',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_issues_search_callback(),
					'permission_callback' => $this->get_permission_callback(),
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
			return $this->get_issues( $request, (string) $request['type_filter'] );
		};
	}

	/**
	 * Get the callback function for searching product issues.
	 *
	 * @return callable
	 */
	protected function get_issues_search_callback(): callable {
		return function( Request $request ) {
			return $this->get_issues(
				$request,
				$this->mc_issues::TYPE_PRODUCT,
				$request['query'] ?? null
			);
		};
	}

	/**
	 * @param Request     $request
	 * @param string|null $type_filter Maybe filter by issue type.
	 * @param string|null $query Maybe search product issues.
	 *
	 * @return Response
	 */
	protected function get_issues( Request $request, string $type_filter = null, string $query = null ): Response {
		$per_page = intval( $request['per_page'] );
		$page     = max( 1, intval( $request['page'] ) );

		try {
			$total = $this->mc_issues->count( $type_filter, $query );
			return $this->prepare_item_for_response(
				[
					'issues'   => $this->mc_issues->get( $type_filter, $query, $per_page, $page ),
					'total'    => $total,
					'page'     => $page,
					'per_page' => $per_page ?: $total,
				],
				$request
			);
		} catch ( Exception $e ) {
			return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
		}
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'issues'   => [
				'type'        => 'array',
				'description' => __( 'The issues related to the Merchant Center account.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
				'items'       => [
					'type' => 'object',
				],
			],
			'total'    => [
				'type'     => 'numeric',
				'context'  => [ 'view' ],
				'readonly' => true,
			],
			'page'     => [
				'type'     => 'numeric',
				'context'  => [ 'view' ],
				'readonly' => true,
			],
			'per_page' => [
				'type'     => 'numeric',
				'context'  => [ 'view' ],
				'readonly' => true,
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
