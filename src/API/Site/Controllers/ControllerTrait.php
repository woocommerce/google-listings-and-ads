<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * Trait ControllerTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
trait ControllerTrait {

	/**
	 * Prepare an item to be sent as an API response.
	 *
	 * @param array $data The raw item data.
	 *
	 * @return array The prepared item data.
	 */
	protected function prepare_item_for_response( array $data ): array {
		$prepared = [];
		$schema   = $this->get_item_schema();
		foreach ( $schema as $key => $property ) {
			if ( $property['readonly'] ?? false ) {
				continue;
			}

			$prepared[ $key ] = $data[ $key ] ?? $property['default'] ?? null;
		}

		return $prepared;
	}

	/**
	 * Get the schema for returning an API response.
	 *
	 * @return array
	 */
	protected function get_api_response_schema(): array {
		return $this->prepare_item_schema( $this->get_item_schema(), $this->get_item_schema_name() );
	}

	/**
	 * Get a callback function for returning the API schema.
	 *
	 * @return callable
	 */
	protected function get_api_response_schema_callback(): callable {
		return function() {
			return $this->get_api_response_schema();
		};
	}

	/**
	 * Prepare an item schema for sending to the API.
	 *
	 * @param array  $properties            Array of raw properties.
	 * @param string $schema_title          Schema title.
	 * @param bool   $additional_properties (Optional) Whether additional properties are allowed.
	 *
	 * @return array
	 */
	protected function prepare_item_schema(
		array $properties,
		string $schema_title,
		bool $additional_properties = false
	): array {
		array_walk(
			$properties,
			function( &$value ) {
				unset(
					$value['default'],
					$value['items'],
					$value['validate_callback'],
					$value['sanitize_callback']
				);
			}
		);

		return [
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => $schema_title,
			'type'                 => 'object',
			'additionalProperties' => $additional_properties,
			'properties'           => $properties,
		];
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	abstract protected function get_item_schema(): array;

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	abstract protected function get_item_schema_name(): string;
}
