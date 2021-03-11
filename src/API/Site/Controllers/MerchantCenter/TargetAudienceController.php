<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\CountryCodeTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ISO3166AwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Locale;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

use function wp_get_available_translations;

defined( 'ABSPATH' ) || exit;

/**
 * Class TargetAudienceController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\MerchantCenter
 */
class TargetAudienceController extends BaseOptionsController implements ISO3166AwareInterface {

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
	 * Retrieves all of the registered additional fields for a given object-type.
	 *
	 * @param string $object_type Optional. The object type.
	 *
	 * @return array Registered additional fields (if any), empty array if none or if the object type could
	 *               not be inferred.
	 */
	protected function get_additional_fields( $object_type = null ): array {
		$fields = parent::get_additional_fields( $object_type );

		// Fields are expected to be an array with a 'get_callback' callable that returns the field value.
		$fields['locale']   = [
			'schema'       => [
				'type'        => 'string',
				'description' => __( 'The locale for the site.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'get_callback' => function() {
				return $this->wp->get_locale();
			},
		];
		$fields['language'] = [
			'schema'       => [
				'type'        => 'string',
				'description' => __( 'The language to use for product listings.', 'google-listings-and-ads' ),
				'context'     => [ 'view' ],
				'readonly'    => true,
			],
			'get_callback' => $this->get_language_callback(),
		];

		return $fields;
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

	/**
	 * Get the callback to provide the language in use for the site.
	 *
	 * @return callable
	 */
	protected function get_language_callback(): callable {
		$locale = $this->wp->get_locale();

		// Default to using the Locale class if it is available.
		if ( class_exists( Locale::class ) ) {
			return function() use ( $locale ): string {
				return Locale::getDisplayLanguage( $locale, $locale );
			};
		}

		return function() use ( $locale ): string {
			// en_US isn't provided by the translations API.
			if ( 'en_US' === $locale ) {
				return 'English';
			}

			require_once ABSPATH . 'wp-admin/includes/translation-install.php';

			return wp_get_available_translations()[ $locale ]['native_name'] ?? $locale;
		};
	}
}
