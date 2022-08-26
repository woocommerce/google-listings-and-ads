<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class for handling API requests for getting source and destination data for Attribute Mapping
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class AttributeMappingController extends BaseOptionsController {

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
			'mc/attribute-mapping/destinations',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_attribute_mapping_destinations_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);

		/**
		 * GET for getting the source data for a specific destination
		 */
		$this->register_route(
			'mc/attribute-mapping/sources',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_attribute_mapping_sources_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}

	/**
	 * Get the callback function for returning the destinations data
	 *
	 * @return callable
	 */
	protected function get_attribute_mapping_destinations_read_callback(): callable {
		return function ( Request $request ) {
			try {
				return $this->prepare_item_for_response( $this->get_destinations(), $request );
			} catch ( Exception $e ) {
				return new Response( [ 'message' => $e->getMessage() ], $e->getCode() ?: 400 );
			}
		};
	}

	/**
	 * Get the callback function getting destination data.
	 *
	 * @return callable
	 */
	protected function get_attribute_mapping_sources_read_callback(): callable {
		return function( Request $request ) {
			try {
				$destination = $request['destination'];
				return $this->get_sources_for_destination( $destination );
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
				'description' => __( 'The data with the different destinations or sources.', 'google-listings-and-ads' ),
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
		return 'attribute_mapping';
	}

	/**
	 * Destinations getter
	 *
	 * @return array The destinations
	 */
	private function get_destinations(): array {
		return [
			'data' => $this->attribute_mapping_helper->get_destinations(),
		];
	}

	/**
	 * Sources getter
	 *
	 * @param string $destination The destination to get the sources for
	 * @return array[] Array with sources
	 */
	private function get_sources_for_destination( string $destination ): array {
		$sources = $this->attribute_mapping_helper->get_sources();
		return [
			'data' => $sources[ $destination ] ?? [],
		];
	}
}
