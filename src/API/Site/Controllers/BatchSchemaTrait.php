<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * Trait BatchSchemaTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers
 */
trait BatchSchemaTrait {

	use CountryCodeTrait;

	/**
	 * Get the schema for a batch request.
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		$schema = parent::get_schema_properties();
		unset( $schema['country'], $schema['country_code'] );

		// Context is always edit for batches.
		foreach ( $schema as $key => &$value ) {
			$value['context'] = [ 'edit' ];
		}

		$schema['country_codes'] = [
			'type'              => 'array',
			'description'       => __(
				'Array of country codes in ISO 3166-1 alpha-2 format.',
				'google-listings-and-ads'
			),
			'context'           => [ 'edit' ],
			'sanitize_callback' => $this->get_country_code_sanitize_callback(),
			'validate_callback' => $this->get_country_code_validate_callback(),
			'minItems'          => 1,
			'required'          => true,
			'uniqueItems'       => true,
			'items'             => [
				'type' => 'string',
			],
		];

		return $schema;
	}

	/**
	 * Get the schema for a batch DELETE request.
	 *
	 * @return array
	 */
	public function get_item_delete_schema(): array {
		$schema = $this->get_item_schema();
		unset( $schema['rate'], $schema['currency'] );

		return $schema;
	}
}
