<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class for handling API requests for getting source and destination data for Attribute Mapping
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping
 */
class AttributeMappingDataController extends BaseOptionsController {

	/**
	 * @var AttributeMappingHelper
	 */
	private AttributeMappingHelper $attribute_mapping_helper;


	/**
	 * AttributeMappingController constructor.
	 *
	 * @param RESTServer             $server
	 * @param AttributeMappingHelper $attribute_mapping_helper
	 */
	public function __construct( RESTServer $server, AttributeMappingHelper $attribute_mapping_helper ) {
		parent::__construct( $server );
		$this->attribute_mapping_helper = $attribute_mapping_helper;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		/**
		 * GET the destination fields for Google Shopping
		 */
		$this->register_route(
			'mc/mapping/attributes',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_mapping_attributes_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);

		/**
		 * GET for getting the source data for a specific destination
		 */
		$this->register_route(
			'mc/mapping/sources',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_mapping_sources_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'attribute' => [
							'description'       => __( 'The attribute key to get the sources.', 'google-listings-and-ads' ),
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
						],
					],
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}

	/**
	 * Callback function for returning the attributes
	 *
	 * @return callable
	 */
	protected function get_mapping_attributes_read_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->prepare_item_for_response( $this->get_attributes(), $request );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Callback function for returning the sources.
	 *
	 * @return callable
	 */
	protected function get_mapping_sources_read_callback(): callable {
		return function ( Request $request ) {
			try {
				$attribute = $request->get_param( 'attribute' );
				return [
					'data' => $this->attribute_mapping_helper->get_sources_for_attribute( $attribute ),
				];
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
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
			'data' => [
				'type'        => 'array',
				'description' => __( 'The list of attributes or attribute sources.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
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
		return 'attribute_mapping_data';
	}

	/**
	 * Attributes getter
	 *
	 * @return array The attributes available for mapping
	 */
	private function get_attributes(): array {
		return [
			'data' => $this->attribute_mapping_helper->get_attributes(),
		];
	}
}
