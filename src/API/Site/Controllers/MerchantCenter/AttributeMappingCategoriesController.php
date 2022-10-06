<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class for handling API requests for getting category tree in Attribute Mapping
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class AttributeMappingCategoriesController extends BaseOptionsController {


	/**
	 * AttributeMappingController constructor.
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
		$this->register_route(
			'mc/mapping/categories',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_categories_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}


	/**
	 * Callback function for getting the category tree
	 *
	 * @return callable
	 */
	protected function get_categories_callback(): callable {
		return function( Request $request ) {
			try {
				$cats = $this->get_category_tree();
				return array_map(
					function ( $cats ) use ( $request ) {
						$response = $this->prepare_item_for_response( $cats, $request );

						return $this->prepare_response_for_collection( $response );
					},
					$cats
				);
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array The Schema properties
	 */
	protected function get_schema_properties(): array {
		return [
			'value'    => [
				'description'       => __( 'The Category ID.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'validate_callback' => 'rest_validate_request_arg',
				'readonly'          => true,
			],
			'label'    => [
				'description'       => __( 'The category name.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'readonly'          => true,
			],
			'children' => [
				'description'       => __( 'The category children.', 'google-listings-and-ads' ),
				'type'              => 'array',
				'validate_callback' => 'rest_validate_request_arg',
				'readonly'          => true,
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
		return 'attribute_mapping_rules';
	}

	/**
	 * Function to get all the categories in a hierarchy
	 *
	 * @param int $parent The category to get descendants
	 * @return array The categories
	 */
	private function get_category_tree( int $parent = 0 ): array {
		$categories_hierarchy = [];

		$categories = get_categories(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => $parent,
			]
		);

		foreach ( $categories as $category ) {
			$category_with_children = [
				'value'    => $category->term_id,
				'label'    => $category->name,
				'children' => $this->get_category_tree( $category->term_id ),
			];
			array_push( $categories_hierarchy, $category_with_children );
		}

		return $categories_hierarchy;
	}


}
