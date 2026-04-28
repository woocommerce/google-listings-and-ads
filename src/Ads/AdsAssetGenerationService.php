<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ExceptionTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Google\Ads\GoogleAds\V22\Services\GenerateTextRequest;
use Google\Ads\GoogleAds\V22\Services\GenerateImagesRequest;
use Google\Ads\GoogleAds\V22\Services\FinalUrlImageGenerationInput;
use Google\Ads\GoogleAds\V22\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\ApiCore\ApiException;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsAssetGenerationService
 *
 * Encapsulates all calls to the Google Ads API v22 AssetGenerationService.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Ads
 */
class AdsAssetGenerationService implements OptionsAwareInterface, Service {

	use OptionsAwareTrait;
	use PluginHelper;
	use ExceptionTrait;

	/**
	 * The Asset Generation Service Client.
	 *
	 * @var \Google\Ads\GoogleAds\V22\Services\Client\AssetGenerationServiceClient
	 */
	protected $client;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $google_ads_client;

	/**
	 * Valid text asset field types.
	 *
	 * @var array
	 */
	public const VALID_TEXT_TYPES = [
		AssetFieldType::HEADLINE,
		AssetFieldType::LONG_HEADLINE,
		AssetFieldType::DESCRIPTION,
	];

	/**
	 * Valid image asset field types.
	 *
	 * @var array
	 */
	public const VALID_IMAGE_TYPES = [
		AssetFieldType::MARKETING_IMAGE,
		AssetFieldType::SQUARE_MARKETING_IMAGE,
		AssetFieldType::PORTRAIT_MARKETING_IMAGE,
	];

	/**
	 * AdsAssetGenerationService constructor.
	 *
	 * @param GoogleAdsClient $client The Google Ads client.
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->google_ads_client = $client;
		$this->client            = $client->getAssetGenerationServiceClient();
	}

	/**
	 * Generate text assets using Google's AI.
	 *
	 * @param array $args {
	 *     Optional. Arguments for generating text assets.
	 *
	 *     @type string $final_url        The final URL - defaults to the Site URL.
	 *     @type array  $asset_field_types Can be one or more of: headline, long_headline, description.
	 * }
	 * @return array Array of generated text objects with 'text' and 'type' keys.
	 * @throws Exception If the text assets can't be generated.
	 */
	public function generate_text( array $args = [] ): array {
		$customer_id = $this->options->get_ads_id();
		if ( empty( $customer_id ) ) {
			throw new Exception( __( 'Ads account ID is required.', 'google-listings-and-ads' ) );
		}

		$final_url = $args['final_url'] ?? $this->get_site_url();

		// Default to all text types if not specified.
		if ( empty( $args['asset_field_types'] ) ) {
			$args['asset_field_types'] = [ 'headline', 'long_headline', 'description' ];
		}

		// Convert asset field types from lowercase strings to enum numbers.
		$asset_field_types = $this->convert_types_to_enums( $args['asset_field_types'], self::VALID_TEXT_TYPES );

		$request = new GenerateTextRequest(
			[
				'customer_id'              => $customer_id,
				'final_url'                => $final_url,
				'advertising_channel_type' => AdvertisingChannelType::PERFORMANCE_MAX,
				'asset_field_types'        => $asset_field_types,
			]
		);

		try {
			$response = $this->client->generateText( $request );

			$results = [];
			foreach ( $response->getGeneratedText() as $text_asset ) {
				$asset_field_type_number = $text_asset->getAssetFieldType();
				$asset_field_type_label  = AssetFieldType::label( $asset_field_type_number );
				$results[]               = [
					'text' => $text_asset->getText(),
					'type' => $asset_field_type_label,
				];
			}

			return $results;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Unable to generate text assets: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				$e,
				[ 'errors' => $errors ]
			);
		}
	}

	/**
	 * Generate image assets using Google's AI.
	 *
	 * @param array $args {
	 *     Optional. Arguments for generating image assets.
	 *
	 *     @type string $final_url        The final URL - defaults to the Site URL.
	 *     @type array  $asset_field_types Can be one or more of: marketing_image, square_marketing_image, portrait_marketing_image.
	 * }
	 * @return array Array of generated image objects with 'temporary_image_url' and 'type' keys.
	 * @throws Exception If the image assets can't be generated.
	 */
	public function generate_images( array $args = [] ): array {
		$customer_id = $this->options->get_ads_id();
		if ( empty( $customer_id ) ) {
			throw new Exception( __( 'Ads account ID is required.', 'google-listings-and-ads' ) );
		}

		$final_url = $args['final_url'] ?? $this->get_site_url();

		// Convert asset field types from lowercase strings to enum numbers (if provided).
		$asset_field_types = [];
		if ( ! empty( $args['asset_field_types'] ) ) {
			$asset_field_types = $this->convert_types_to_enums( $args['asset_field_types'], self::VALID_IMAGE_TYPES );
		}

		$request_data = [
			'customer_id'              => $customer_id,
			'advertising_channel_type' => AdvertisingChannelType::PERFORMANCE_MAX,
			'final_url_generation'     => new FinalUrlImageGenerationInput(
				[
					'final_url' => $final_url,
				]
			),
		];

		// Add asset_field_types only if provided.
		if ( ! empty( $asset_field_types ) ) {
			$request_data['asset_field_types'] = $asset_field_types;
		}

		$request = new GenerateImagesRequest( $request_data );

		try {
			$response = $this->client->generateImages( $request );

			$results = [];
			foreach ( $response->getGeneratedImages() as $image_asset ) {
				$asset_field_type_number = $image_asset->getAssetFieldType();
				$asset_field_type_label  = AssetFieldType::label( $asset_field_type_number );
				$results[]               = [
					'temporary_image_url' => $image_asset->getImageTemporaryUrl(),
					'type'                => $asset_field_type_label,
				];
			}

			return $results;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Unable to generate image assets: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				$e,
				[ 'errors' => $errors ]
			);
		}
	}

	/**
	 * Convert asset field types from lowercase strings to enum numbers.
	 *
	 * @param array $types Array of lowercase type strings.
	 * @param array $allowed_types Optional. Array of AssetFieldType constants to filter by.
	 * @return array Array of enum numbers.
	 */
	protected function convert_types_to_enums( array $types, array $allowed_types = [] ): array {
		$enums = [];
		foreach ( $types as $type ) {
			// Filter by allowed types if specified.
			if ( ! empty( $allowed_types ) && ! in_array( $type, $allowed_types, true ) ) {
				continue;
			}

			$enums[] = AssetFieldType::number( $type );
		}

		return $enums;
	}
}
