<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\DB\Query\AttributeMappingRulesQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WP_Error;
use WP_REST_Request as Request;
use WP_REST_Response as Response;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class for handling API requests for getting source and destination data for Attribute Mapping
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\AttributeMapping
 */
class AttributeMappingRulesController extends BaseOptionsController {

	/**
	 * @var AttributeMappingRulesQuery
	 */
	private AttributeMappingRulesQuery $attribute_mapping_rules_query;

	/**
	 * @var AttributeMappingHelper
	 */
	private AttributeMappingHelper $attribute_mapping_helper;

	/**
	 * AttributeMappingController constructor.
	 *
	 * @param RESTServer                 $server
	 * @param AttributeMappingHelper     $attribute_mapping_helper
	 * @param AttributeMappingRulesQuery $attribute_mapping_rules_query
	 */
	public function __construct( RESTServer $server, AttributeMappingHelper $attribute_mapping_helper, AttributeMappingRulesQuery $attribute_mapping_rules_query ) {
		parent::__construct( $server );
		$this->attribute_mapping_helper      = $attribute_mapping_helper;
		$this->attribute_mapping_rules_query = $attribute_mapping_rules_query;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/mapping/rules',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_rule_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->create_rule_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);

		$this->register_route(
			'mc/mapping/rules/(?P<id>[\d]+)',
			[
				[
					'methods'             => TransportMethods::EDITABLE,
					'callback'            => $this->update_rule_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				[
					'methods'             => TransportMethods::DELETABLE,
					'callback'            => $this->delete_rule_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			],
		);
	}


	/**
	 * Callback function for getting the Attribute Mapping rules from DB
	 *
	 * @return callable
	 */
	protected function get_rule_callback(): callable {
		return function( Request $request ) {
			try {
				$page     = $request->get_param( 'page' );
				$per_page = $request->get_param( 'per_page' );

				$this->attribute_mapping_rules_query->set_limit( $per_page );
				$this->attribute_mapping_rules_query->set_offset( $per_page * ( $page - 1 ) );

				$rules       = $this->attribute_mapping_rules_query->get_results();
				$total_rules = $this->attribute_mapping_rules_query->get_count();

				$response_data = [];

				foreach ( $rules as $rule ) {
					$item_data       = $this->prepare_item_for_response( $rule, $request );
					$response_data[] = $this->prepare_response_for_collection( $item_data );
				}

				return new Response(
					$response_data,
					200,
					[
						'X-WP-Total'      => $total_rules,
						'X-WP-TotalPages' => ceil( $total_rules / $per_page ),
					]
				);

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
	protected function create_rule_callback(): callable {
		return function( Request $request ) {
			try {
				if ( ! $this->attribute_mapping_rules_query->insert( $this->prepare_item_for_database( $request ) ) ) {
					return $this->response_from_exception( new Exception( 'Unable to create the new rule.' ) );
				}

				$response = $this->prepare_item_for_response( $this->attribute_mapping_rules_query->get_rule( $this->attribute_mapping_rules_query->last_insert_id() ), $request );
				do_action( 'woocommerce_gla_mapping_rules_change' );
				return $response;
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
	protected function update_rule_callback(): callable {
		return function( Request $request ) {
			try {
				$rule_id = $request->get_url_params()['id'];

				if ( ! $this->attribute_mapping_rules_query->update( $this->prepare_item_for_database( $request ), [ 'id' => $rule_id ] ) ) {
					return $this->response_from_exception( new Exception( 'Unable to update the new rule.' ) );
				}

				$response = $this->prepare_item_for_response( $this->attribute_mapping_rules_query->get_rule( $rule_id ), $request );
				do_action( 'woocommerce_gla_mapping_rules_change' );
				return $response;
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
	protected function delete_rule_callback(): callable {
		return function( Request $request ) {
			try {
				$rule_id = $request->get_url_params()['id'];

				if ( ! $this->attribute_mapping_rules_query->delete( 'id', $rule_id ) ) {
					return $this->response_from_exception( new Exception( 'Unable to delete the rule' ) );
				}

				do_action( 'woocommerce_gla_mapping_rules_change' );
				return [
					'id' => $rule_id,
				];
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
			'id'                      => [
				'description'       => __( 'The Id for the rule.', 'google-listings-and-ads' ),
				'type'              => 'integer',
				'validate_callback' => 'rest_validate_request_arg',
				'readonly'          => true,
			],
			'attribute'               => [
				'description'       => __( 'The attribute value for the rule.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
				'enum'              => array_column( $this->attribute_mapping_helper->get_attributes(), 'id' ),
			],
			'source'                  => [
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
				'enum'              => $this->attribute_mapping_helper->get_category_condition_types(),
			],
			'categories'              => [
				'description'       => __( 'List of category IDs, separated by commas.', 'google-listings-and-ads' ),
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => function( $param ) {
					return $this->validate_categories_param( $param );
				},
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
	 * @param string $categories  Categories to validate
	 * @return bool|WP_Error  True if it's validated
	 *
	 * @throw Exception when invalid categories are provided
	 */
	public function validate_categories_param( string $categories ) {
		if ( $categories === '' ) {
			return true;
		}

		$categories_array = explode( ',', $categories );

		foreach ( $categories_array as $category ) {
			if ( ! is_numeric( $category ) ) {
				return new WP_Error(
					'woocommerce_gla_attribute_mapping_invalid_categories_schema',
					'categories should be a string of category IDs separated by commas.',
					[
						'categories' => $categories,
					]
				);
			}
		}

		return true;
	}
}
