<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\AttributeMappingRules;
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
			'mc/mapping/attributes',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_mappping_attributes_read_callback(),
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
						],
					],
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);

		/**
		 * GET - Receive All Attribute mapping rules from database
		 */
		$this->register_route(
			'mc/mapping/rules',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_mapping_rules_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			],
		);
		/**
		 * POST - Upsert an Attribute mapping rule
		 */
		$this->register_route(
			'mc/mapping/rule',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->post_mapping_rule_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'rule' => [
							'description'       => __( 'The rule to be inserted or updated.', 'google-listings-and-ads' ),
							'type'              => 'object',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
							'properties'        => [
								'id' => [
									'description'       => __( 'The id for the rule to update.', 'google-listings-and-ads' ),
									'type'              => 'integer',
									'validate_callback' => 'rest_validate_request_arg',
									'minimum'           => 1,
								],
								'attribute' => [
									'description'       => __( 'The attribute value for the rule.', 'google-listings-and-ads' ),
									'type'              => 'string',
									'validate_callback' => 'rest_validate_request_arg',
									'required'          => true,
									'enum'              => array_keys( $this->attribute_mapping_helper->get_attributes() )
								],
								'source' => [
									'description'       => __( 'The source value for the rule.', 'google-listings-and-ads' ),
									'type'              => 'string',
									'validate_callback' => 'rest_validate_request_arg',
									'required'          => true,
								],
								'category_condition_type' => [
									'description'       => __( 'The category condition type to apply for this rule.', 'google-listings-and-ads' ),
									'type'              => 'string',
									'validate_callback' => 'rest_validate_request_arg',
									'required'          => true,
									'enum'              => $this->attribute_mapping_helper->get_category_condition_types()
								],
								'categories' => [
									'description'       => __( 'Comma separated categories for this rule.', 'google-listings-and-ads' ),
									'type'              => 'string',
									'validate_callback' => 'rest_validate_request_arg',
								]
							]
						],
					],
				],
			],
		);

		/**
		 * DELETE - Delete an Attribute mapping rule
		 */
		$this->register_route(
			'mc/mapping/rule',
			[
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->delete_mapping_rule_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => [
						'rule_id' => [
							'description'       => __( 'The ID for the rule to be deleted.', 'google-listings-and-ads' ),
							'type'              => 'integer',
							'validate_callback' => 'rest_validate_request_arg',
							'required'          => true,
							'minimum'           => 1,
						],
					],
				],
			],
		);
	}

	/**
	 * Callback function for returning the attributes
	 *
	 * @return callable
	 */
	protected function get_mappping_attributes_read_callback(): callable {
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
		return function( Request $request ) {
			try {
				$attribute = $request->get_param( 'attribute' );

				if ( ! $attribute ) {
					return [
						'data' => [],
					];
				}

				return $this->get_sources_for_attribute( $attribute );
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}


	/**
	 * Callback function for getting the Attribute Mapping rules from DB
	 *
	 * @return callable
	 */
	protected function get_mapping_rules_callback(): callable {
		return function() {
			try {
				return $this->attribute_mapping_helper->get_rules();
			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Callback function for saving an Attribute Mapping rule in DB
	 *
	 * @return callable
	 */
	protected function post_mapping_rule_callback(): callable {
		return function( Request $request ) {
			try {
				$rule = $request->get_param( 'rule' );
				$result = $this->attribute_mapping_helper->upsert_rule( $rule );

				if ( is_null( $result ) ) {
					return $this->response_from_exception( new Exception( 'Unable to register the new rule.' ) );
				}

				return $result;

			} catch ( Exception $e ) {
				return $this->response_from_exception( $e );
			}
		};
	}

	/**
	 * Callback function for deleting an Attribute Mapping rule in DB
	 *
	 * @return callable
	 */
	protected function delete_mapping_rule_callback(): callable {
		return function( Request $request ) {
			try {
				$rule_id = $request->get_param( 'rule_id' );
				if ( ! $this->attribute_mapping_helper->delete_rule( $rule_id ) ) {
					return $this->response_from_exception( new Exception( 'Unable to delete the rule' ) );
				}

				return true;
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
				'type'        => 'object',
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
		return 'attribute_mapping';
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

	/**
	 * Sources getter
	 *
	 * @param string $attribute The attribute to get the sources for
	 * @return array[] Array with sources
	 */
	private function get_sources_for_attribute( string $attribute ): array {
		$sources = $this->attribute_mapping_helper->get_sources();
		return [
			'data' => $sources[ $attribute ] ?? [],
		];
	}
}
