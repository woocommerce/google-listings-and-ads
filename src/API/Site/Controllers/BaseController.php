<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

use Automattic\WooCommerce\GoogleListingsAndAds\API\PermissionsTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use WC_REST_Controller;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

/**
 * Class BaseEndpoint
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site
 */
abstract class BaseController extends WC_REST_Controller implements Registerable {

	use PluginHelper;
	use PermissionsTrait;
	use ResponseFromExceptionTrait;

	/**
	 * @var RESTServer
	 */
	protected $server;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 */
	public function __construct( RESTServer $server ) {
		$this->server    = $server;
		$this->namespace = $this->get_namespace();
	}

	/**
	 * Register a service.
	 */
	public function register(): void {
		$this->register_routes();
	}

	/**
	 * Register a single route.
	 *
	 * @param string $route The route name.
	 * @param array  $args  The arguments for the route.
	 */
	protected function register_route( string $route, array $args ): void {
		$this->server->register_route( $this->get_namespace(), $route, $args );
	}

	/**
	 * Get the namespace for the current controller.
	 *
	 * @return string
	 */
	protected function get_namespace(): string {
		return "wc/{$this->get_slug()}";
	}

	/**
	 * Get the callback to determine the route's permissions.
	 *
	 * @return callable
	 */
	protected function get_permission_callback(): callable {
		return function () {
			return $this->can_manage();
		};
	}

	/**
	 * Prepare an item schema for sending to the API.
	 *
	 * @param array  $properties   Array of raw properties.
	 * @param string $schema_title Schema title.
	 *
	 * @return array
	 */
	protected function prepare_item_schema( array $properties, string $schema_title ): array {
		return $this->add_additional_fields_schema(
			[
				'$schema'              => 'http://json-schema.org/draft-04/schema#',
				'title'                => $schema_title,
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => $properties,
			]
		);
	}

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema(): array {
		return $this->prepare_item_schema( $this->get_schema_properties(), $this->get_schema_title() );
	}

	/**
	 * Get a callback function for returning the API schema.
	 *
	 * @return callable
	 */
	protected function get_api_response_schema_callback(): callable {
		return function () {
			return $this->get_item_schema();
		};
	}

	/**
	 * Get a route name which is safe to use as a filter (removes namespace prefix).
	 *
	 * @param Request $request Request object.
	 *
	 * @return string
	 */
	protected function get_route_name( Request $request ): string {
		$route = trim( $request->get_route(), '/' );

		if ( 0 === strpos( $route, $this->get_namespace() ) ) {
			$route = substr( $route, strlen( $this->get_namespace() ) );
		}

		return sanitize_title( $route );
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param mixed   $item    WordPress representation of the item.
	 * @param Request $request Request object.
	 *
	 * @return Response Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$prepared = [];
		$context  = $request['context'] ?? 'view';
		$schema   = $this->get_schema_properties();
		foreach ( $schema as $key => $property ) {
			$item_value = $item[ $key ] ?? $property['default'] ?? null;

			// Cast empty arrays to empty objects if property is supposed to be an object.
			if ( is_array( $item_value ) && empty( $item_value ) && isset( $property['type'] ) && 'object' === $property['type'] ) {
				$item_value = (object) [];
			}

			$prepared[ $key ] = $item_value;
		}

		$prepared = $this->add_additional_fields_to_object( $prepared, $request );
		$prepared = $this->filter_response_by_context( $prepared, $context );
		$prepared = apply_filters(
			'woocommerce_gla_prepared_response_' . $this->get_route_name( $request ),
			$prepared,
			$request
		);

		return new Response( $prepared );
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @param Request $request Request object.
	 *
	 * @return array The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_item_for_database( $request ): array {
		$prepared = [];
		$schema   = $this->get_schema_properties();
		foreach ( $schema as $key => $property ) {
			if ( $property['readonly'] ?? false ) {
				continue;
			}

			$prepared[ $key ] = $request[ $key ] ?? $property['default'] ?? null;
		}

		return $prepared;
	}

	/**
	 * Get the callback to sanitize a price value.
	 *
	 * Supports positive integers and floats.
	 * Maximum number length is 10 digits + 8 decimals.
	 *
	 * @since 3.2.0
	 *
	 * @return callable
	 */
	protected function get_sanitize_price_callback(): callable {
		return function ( $price ) {
			return preg_match( '/^[0-9]{0,10}(\.[0-9]{0,8})?$/', (string) $price ) ? (float) $price : false;
		};
	}

	/**
	 * Get the item schema properties for the controller.
	 *
	 * @return array
	 */
	abstract protected function get_schema_properties(): array;

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	abstract protected function get_schema_title(): string;
}
