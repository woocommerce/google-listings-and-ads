<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Ads;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AssetFieldType;
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

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * Mapping from lowercase input strings to AssetFieldType constants.
	 *
	 * @var array
	 */
	protected const TYPE_MAPPING = [
		'headline'                 => AssetFieldType::HEADLINE,
		'long_headline'            => AssetFieldType::LONG_HEADLINE,
		'description'              => AssetFieldType::DESCRIPTION,
		'marketing_image'          => AssetFieldType::MARKETING_IMAGE,
		'square_marketing_image'   => AssetFieldType::SQUARE_MARKETING_IMAGE,
		'portrait_marketing_image' => AssetFieldType::PORTRAIT_MARKETING_IMAGE,
	];

	/**
	 * AdsAssetGenerationService constructor.
	 *
	 * @param GoogleAdsClient $client The Google Ads client.
	 */
	public function __construct( GoogleAdsClient $client ) {
		$this->client = $client;
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

		// Set default types if not provided.
		$types = $args['asset_field_types'] ?? [];
		if ( empty( $types ) ) {
			$types = [ 'headline', 'long_headline', 'description' ];
		}

		// Convert asset field types from lowercase strings to enum numbers.
		$asset_field_types = $this->convert_text_types_to_enums( $types );

		$request = new GenerateTextRequest(
			[
				'customer_id'              => $customer_id,
				'final_url'                => $final_url,
				'advertising_channel_type' => AdvertisingChannelType::PERFORMANCE_MAX,
				'asset_field_types'        => $asset_field_types,
			]
		);

		try {
			$service_client = $this->client->getAssetGenerationServiceClient();
			$response       = $service_client->generateTextAssets( $request );

			$results = [];
			foreach ( $response->getTextAssets() as $text_asset ) {
				$asset_field_type_number = $text_asset->getAssetFieldType();
				$asset_field_type_label  = AssetFieldType::label( $asset_field_type_number );
				$results[]               = [
					'text' => $text_asset->getText(),
					'type' => AssetFieldType::name( $asset_field_type_label ),
				];
			}

			return $results;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to generate text assets.', 'google-listings-and-ads' ) . ' ' . $e->getMessage(), $e->getCode() );
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
			$asset_field_types = $this->convert_image_types_to_enums( $args['asset_field_types'] );
		}

		$request_data = [
			'customer_id'              => $customer_id,
			'generation_type'          => 'final_url_generation',
			'advertising_channel_type' => AdvertisingChannelType::PERFORMANCE_MAX,
		];

		// Add final_url_generation_input.
		$request_data['final_url_generation_input'] = new FinalUrlImageGenerationInput(
			[
				'final_url' => $final_url,
			]
		);

		// Add asset_field_types only if provided.
		if ( ! empty( $asset_field_types ) ) {
			$request_data['asset_field_types'] = $asset_field_types;
		}

		$request = new GenerateImagesRequest( $request_data );

		try {
			$service_client = $this->client->getAssetGenerationServiceClient();
			$response       = $service_client->generateImages( $request );

			$results = [];
			foreach ( $response->getImageAssets() as $image_asset ) {
				$asset_field_type_number = $image_asset->getAssetFieldType();
				$asset_field_type_label  = AssetFieldType::label( $asset_field_type_number );
				$results[]               = [
					'temporary_image_url' => $image_asset->getTemporaryImageUrl(),
					'type'                => AssetFieldType::name( $asset_field_type_label ),
				];
			}

			return $results;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );
			throw new Exception( __( 'Unable to generate image assets.', 'google-listings-and-ads' ) . ' ' . $e->getMessage(), $e->getCode() );
		}
	}

	/**
	 * Convert text asset field types from lowercase strings to enum numbers.
	 *
	 * @param array $types Array of lowercase type strings (headline, long_headline, description).
	 * @return array Array of enum numbers.
	 */
	protected function convert_text_types_to_enums( array $types ): array {
		$enums = [];
		foreach ( $types as $type ) {
			if ( ! isset( self::TYPE_MAPPING[ $type ] ) ) {
				continue;
			}

			$internal_type = self::TYPE_MAPPING[ $type ];
			// Only include text types.
			if ( in_array( $internal_type, [ AssetFieldType::HEADLINE, AssetFieldType::LONG_HEADLINE, AssetFieldType::DESCRIPTION ], true ) ) {
				$enums[] = AssetFieldType::number( $internal_type );
			}
		}

		return $enums;
	}

	/**
	 * Convert image asset field types from lowercase strings to enum numbers.
	 *
	 * @param array $types Array of lowercase type strings (marketing_image, square_marketing_image, portrait_marketing_image).
	 * @return array Array of enum numbers.
	 */
	protected function convert_image_types_to_enums( array $types ): array {
		$enums = [];
		foreach ( $types as $type ) {
			if ( ! isset( self::TYPE_MAPPING[ $type ] ) ) {
				continue;
			}

			$internal_type = self::TYPE_MAPPING[ $type ];
			// Only include image types.
			if ( in_array( $internal_type, [ AssetFieldType::MARKETING_IMAGE, AssetFieldType::SQUARE_MARKETING_IMAGE, AssetFieldType::PORTRAIT_MARKETING_IMAGE ], true ) ) {
				$enums[] = AssetFieldType::number( $internal_type );
			}
		}

		return $enums;
	}
}
