<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\ControllerTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class TargetAudienceController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class TargetAudienceController extends BaseOptionsController {

	use ControllerTrait {
		prepare_item_for_response as trait_item_response;
	}
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
	protected function register_routes(): void {
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
					'args'                => $this->get_item_schema(),
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
		return function() {
			return $this->prepare_item_for_response( $this->get_target_audience_option() );
		};
	}

	/**
	 * Get the callback function for updating the target audience data.
	 *
	 * @return callable
	 */
	protected function get_update_audience_callback(): callable {
		return function( WP_REST_Request $request ) {
			$this->update_target_audience_option( $this->prepare_item_for_response( $request->get_params() ) );

			return new WP_REST_Response(
				[
					'status'  => 'success',
					'message' => __( 'Successfully updated the Target Audience settings.', 'google-listings-and-ads' ),
				],
				201
			);
		};
	}

	/**
	 * Prepare an item to be sent as an API response.
	 *
	 * @param array $data The raw item data.
	 *
	 * @return array The prepared item data.
	 */
	protected function prepare_item_for_response( array $data ): array {
		$response             = $this->trait_item_response( $data );
		$response['language'] = $this->wp->get_locale();

		return $response;
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
	protected function get_item_schema(): array {
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
	protected function get_item_schema_name(): string {
		return 'target_audience';
	}
}
