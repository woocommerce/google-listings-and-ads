<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Locale;
use WP_Error;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class TargetAudienceController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class TargetAudienceController extends BaseOptionsController {

	use CountryCodeTrait;

	/**
	 * The WP proxy object.
	 *
	 * @var WP
	 */
	protected $wp;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer $server
	 * @param WP         $wp
	 */
	public function __construct( RESTServer $server, WP $wp ) {
		parent::__construct( $server );
		$this->wp = $wp;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'mc/target_audience',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_read_audience_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_update_audience_callback(),
					'permission_callback' => $this->get_permission_callback(),
					'args'                => $this->get_schema_properties(),
				],
				'schema' => $this->get_api_response_schema_callback(),
			]
		);
	}

	/**
	 * Get the callback function for reading the target audience data.
	 *
	 * @return callable
	 */
	protected function get_read_audience_callback(): callable {
		return function( Request $request ) {
			return $this->prepare_item_for_response( $this->get_target_audience_option(), $request );
		};
	}

	/**
	 * Get the callback function for updating the target audience data.
	 *
	 * @return callable
	 */
	protected function get_update_audience_callback(): callable {
		return function( Request $request ) {
			$data = $this->prepare_item_for_database( $request );
			$this->update_target_audience_option( $data );
			$this->prepare_item_for_response( $data, $request );

			return new Response(
				[
					'status'  => 'success',
					'message' => __( 'Successfully updated the Target Audience settings.', 'google-listings-and-ads' ),
				],
				201
			);
		};
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param mixed   $item    WordPress representation of the item.
	 * @param Request $request Request object.
	 *
	 * @return Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$prepared = [];
		$context  = $request['context'] ?? 'view';
		$schema   = $this->get_schema_properties();
		foreach ( $schema as $key => $property ) {
			$prepared[ $key ] = $data[ $key ] ?? $property['default'] ?? null;
		}

		$locale               = $this->wp->get_locale();
		$prepared['locale']   = $locale;
		$prepared['language'] = Locale::getDisplayLanguage( $locale, $locale );

		$prepared = $this->add_additional_fields_to_object( $prepared, $request );
		$prepared = $this->filter_response_by_context( $prepared, $context );

		return new Response( $prepared );
	}

	/**
	 * Get the option data for the target audience.
	 *
	 * @return array
	 */
	protected function get_target_audience_option(): array {
		return $this->options->get( OptionsInterface::TARGET_AUDIENCE, [] );
	}

	/**
	 * Update the option data for the target audience.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function update_target_audience_option( array $data ): bool {
		return $this->options->update( OptionsInterface::TARGET_AUDIENCE, $data );
	}

	/**
	 * Get the item schema for the controller.
	 *
	 * @return array
	 */
	protected function get_schema_properties(): array {
		return [
			'language'  => [
				'type'        => 'string',
				'description' => __( 'The language to use for product listings.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'location'  => [
				'type'              => 'string',
				'description'       => __( 'Location where products will be shown.', 'google-listings-and-ads' ),
				'context'           => [ 'edit', 'view' ],
				'validate_callback' => 'rest_validate_request_arg',
				'required'          => true,
				'enum'              => [
					'all',
					'selected',
				],
			],
			'countries' => [
				'type'              => 'array',
				'description'       => __(
					'Array of country codes in ISO 3166-1 alpha-2 format.',
					'google-listings-and-ads'
				),
				'context'           => [ 'edit', 'view' ],
				'sanitize_callback' => $this->get_country_code_sanitize_callback(),
				'validate_callback' => $this->get_country_code_validate_callback(),
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
		return 'target_audience';
	}
}
