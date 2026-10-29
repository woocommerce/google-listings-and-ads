<?php
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are context-neutral data; escape only at the eventual output boundary.
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Google\Ads\GoogleAds\V23\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V23\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\V23\Resources\Asset;
use Google\Ads\GoogleAds\V23\Services\AssetOperation;
use Google\Ads\GoogleAds\V23\Services\MutateGoogleAdsRequest;
use Google\Ads\GoogleAds\V23\Services\MutateOperation;
use Google\Ads\GoogleAds\Util\V23\ResourceNames;
use Google\Ads\GoogleAds\V23\Common\TextAsset;
use Google\Ads\GoogleAds\V23\Common\ImageAsset;
use Google\Ads\GoogleAds\V23\Common\CallToActionAsset;
use Google\Ads\GoogleAds\V23\Common\YoutubeVideoAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidSourceImage;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Google\ApiCore\ApiException;
use Exception;

/**
 * Class AdsAsset
 *
 * Used for the Performance Max Campaigns
 * https://developers.google.com/google-ads/api/docs/performance-max/assets
 *
 * @since 2.4.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsAsset implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * WP Proxy
	 *
	 * @var WP
	 */
	protected WP $wp;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected GoogleAdsClient $client;

	/**
	 * Maximum payload size in bytes.
	 *
	 * @var int
	 */
	public const MAX_PAYLOAD_BYTES = 30 * 1024 * 1024;

	/**
	 * Maximum image size in bytes.
	 *
	 * @var int
	 */
	protected const MAX_IMAGE_SIZE_BYTES = 5 * 1024 * 1024;

	/**
	 * Timeout in seconds for downloading an image.
	 *
	 * @var int
	 */
	protected const IMAGE_FETCH_TIMEOUT = 10;

	/**
	 * Maximum number of redirects to follow when downloading an image.
	 *
	 * @var int
	 */
	protected const IMAGE_FETCH_MAX_REDIRECTS = 3;

	/**
	 * Image types accepted by Google Ads image assets.
	 *
	 * @var int[]
	 */
	protected const SUPPORTED_IMAGE_TYPES = [ IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF ];

	/**
	 * AdsAsset constructor.
	 *
	 * @param GoogleAdsClient $client The Google Ads client.
	 * @param WP              $wp The WordPress proxy.
	 */
	public function __construct( GoogleAdsClient $client, WP $wp ) {
		$this->client = $client;
		$this->wp     = $wp;
	}

	/**
	 * Temporary ID to use within a batch job.
	 * A negative number which is unique for all the created resources.
	 *
	 * @var int
	 */
	protected static $temporary_id = -5;

	/**
	 * Return a temporary resource name for the asset.
	 *
	 * @param int $temporary_id The temporary ID to use for the asset.
	 *
	 * @return string The Asset resource name.
	 */
	protected function temporary_resource_name( int $temporary_id ): string {
		return ResourceNames::forAsset( $this->options->get_ads_id(), $temporary_id );
	}

	/**
	 * Returns the asset type for the given field type.
	 *
	 * @param string $field_type The field type.
	 *
	 * @return int The asset type.
	 * @throws Exception If the field type is not supported.
	 */
	protected function get_asset_type_by_field_type( string $field_type ): int {
		switch ( $field_type ) {
			case AssetFieldType::LOGO:
			case AssetFieldType::MARKETING_IMAGE:
			case AssetFieldType::SQUARE_MARKETING_IMAGE:
			case AssetFieldType::PORTRAIT_MARKETING_IMAGE:
				return AssetType::IMAGE;
			case AssetFieldType::CALL_TO_ACTION_SELECTION:
				return AssetType::CALL_TO_ACTION;
			case AssetFieldType::HEADLINE:
			case AssetFieldType::LONG_HEADLINE:
			case AssetFieldType::DESCRIPTION:
			case AssetFieldType::BUSINESS_NAME:
				return AssetType::TEXT;
			case AssetFieldType::YOUTUBE_VIDEO:
				return AssetType::YOUTUBE_VIDEO;
			default:
				throw new Exception( 'Asset Field type not supported' );
		}
	}

	/**
	 * Returns the image data.
	 *
	 * @param string $url The image url.
	 *
	 * @return array The image data.
	 * @throws InvalidSourceImage If the download fails, doesn't return a supported image, or the image is too large.
	 */
	public function get_image_data( string $url ): array {
		$image_data = $this->wp->wp_remote_get(
			$url,
			[
				'reject_unsafe_urls'  => true,
				// One byte over the limit is enough to tell an oversized image apart without buffering the whole body.
				'limit_response_size' => self::MAX_IMAGE_SIZE_BYTES + 1,
				'timeout'             => self::IMAGE_FETCH_TIMEOUT,
				'redirection'         => self::IMAGE_FETCH_MAX_REDIRECTS,
			]
		);

		if ( is_wp_error( $image_data ) || empty( $image_data['body'] ) ) {
			throw InvalidSourceImage::fetch_failed( $url );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $image_data ) ) {
			throw InvalidSourceImage::fetch_failed( $url );
		}

		if ( ! str_starts_with( (string) wp_remote_retrieve_header( $image_data, 'content-type' ), 'image/' ) ) {
			throw InvalidSourceImage::fetch_failed( $url );
		}

		// The remote server controls Content-Length, so it can only reject early. The received bytes decide.
		if ( (int) wp_remote_retrieve_header( $image_data, 'content-length' ) > self::MAX_IMAGE_SIZE_BYTES ) {
			throw InvalidSourceImage::too_large();
		}

		$size = strlen( $image_data['body'] );

		if ( $size > self::MAX_IMAGE_SIZE_BYTES ) {
			throw InvalidSourceImage::too_large();
		}

		$image_info = getimagesizefromstring( $image_data['body'] );

		if ( false === $image_info || ! in_array( $image_info[2], self::SUPPORTED_IMAGE_TYPES, true ) ) {
			throw InvalidSourceImage::fetch_failed( $url );
		}

		return [
			'body' => $image_data['body'],
			'size' => $size,
		];
	}

	/**
	 * Creates the assets so they can be used in the asset groups.
	 *
	 * Downloads images in order and sends each batch before adding an image that would take it
	 * over $batch_size, so only one batch of images is in memory at a time.
	 *
	 * @param array $assets The assets to create.
	 * @param int   $batch_size The maximum size of the payload in bytes.
	 *
	 * @return array A list of Asset's ARN created, in the same order as the assets.
	 *
	 * @throws Exception If the asset type is not supported, or an image download fails or is too large.
	 * @throws ApiException If any of the operations fail.
	 */
	public function create_assets( array $assets, int $batch_size = self::MAX_PAYLOAD_BYTES ): array {
		if ( empty( $assets ) ) {
			return [];
		}

		$arns            = [];
		$operations      = [];
		$operations_size = 0;

		foreach ( $assets as $asset ) {
			if ( $this->get_asset_type_by_field_type( $asset['field_type'] ) === AssetType::IMAGE ) {
				$image_data = $this->get_image_data( $asset['content'] );

				if ( ! empty( $operations ) && $operations_size + $image_data['size'] > $batch_size ) {
					// If the mutate operation fails, it will throw an exception that will be caught by the caller.
					$arns            = [ ...$arns, ...$this->mutate( $operations ) ];
					$operations      = [];
					$operations_size = 0;
				}

				$asset['body']    = $image_data['body'];
				$operations_size += $image_data['size'];
			}

			$operations[] = $this->create_operation( $asset, self::$temporary_id-- );
		}

		return [ ...$arns, ...$this->mutate( $operations ) ];
	}

	/**
	 * Returns an operation to create a text asset.
	 *
	 * @param array $data The asset data.
	 * @param int   $temporary_id The temporary ID to use for the asset.
	 *
	 * @return MutateOperation The create asset operation.
	 * @throws Exception If the asset type is not supported.
	 */
	protected function create_operation( array $data, int $temporary_id ): MutateOperation {
		$asset = new Asset(
			[
				'resource_name' => $this->temporary_resource_name( $temporary_id ),
			]
		);

		switch ( $this->get_asset_type_by_field_type( $data['field_type'] ) ) {
			case AssetType::CALL_TO_ACTION:
				$asset->setCallToActionAsset( new CallToActionAsset( [ 'call_to_action' => CallToActionType::number( $data['content'] ) ] ) );
				break;
			case AssetType::IMAGE:
				$asset->setImageAsset( new ImageAsset( [ 'data' => $data['body'] ] ) );
				$asset->setName( basename( $data['content'] ) );
				break;
			case AssetType::TEXT:
				$asset->setTextAsset( new TextAsset( [ 'text' => $data['content'] ] ) );
				break;
			case AssetType::YOUTUBE_VIDEO:
				$asset->setYoutubeVideoAsset( new YoutubeVideoAsset( [ 'youtube_video_id' => $data['content'] ] ) );
				break;
			default:
				throw new Exception( 'Asset type not supported' );
		}

		$operation = ( new AssetOperation() )->setCreate( $asset );
		return ( new MutateOperation() )->setAssetOperation( $operation );
	}

	/**
	 * Returns an array of operations to create multiple assets.
	 *
	 * @param array $assets   An array of assets, each containing content and field_type keys
	 * @param int   $max_size The image byte limit across all operations.
	 * @return array An array of MutateOperation
	 * @throws InvalidSourceImage If an image download fails or the images go over $max_size.
	 */
	public function create_operations( array $assets, int $max_size = self::MAX_PAYLOAD_BYTES ): array {
		if ( empty( $assets ) ) {
			return [];
		}

		$operations  = [];
		$image_types = [
			AssetFieldType::LOGO,
			AssetFieldType::MARKETING_IMAGE,
			AssetFieldType::SQUARE_MARKETING_IMAGE,
			AssetFieldType::PORTRAIT_MARKETING_IMAGE,
		];

		$total_size = 0;

		foreach ( $assets as $asset ) {
			// For image assets, fetch the image data.
			if ( in_array( $asset['field_type'], $image_types, true ) ) {
				$image_data  = $this->get_image_data( $asset['content'] );
				$total_size += $image_data['size'];

				// The caller sends these in one mutate, so there is no later batch to move an image to.
				if ( $total_size > $max_size ) {
					throw InvalidSourceImage::too_large();
				}

				$asset['body'] = $image_data['body'];
			}

			$operations[] = $this->create_operation(
				$asset,
				self::$temporary_id--
			);
		}

		return $operations;
	}

	/**
	 * Returns the total image bytes carried by asset create operations.
	 *
	 * @param MutateOperation[] $operations The operations to measure.
	 *
	 * @return int The total image bytes.
	 */
	public static function get_image_bytes( array $operations ): int {
		$total_size = 0;

		foreach ( $operations as $operation ) {
			if ( ! $operation->hasAssetOperation() || ! $operation->getAssetOperation()->hasCreate() ) {
				continue;
			}

			$image_asset = $operation->getAssetOperation()->getCreate()->getImageAsset();

			if ( $image_asset ) {
				$total_size += strlen( $image_asset->getData() );
			}
		}

		return $total_size;
	}

	/**
	 * Returns the asset content for the given row.
	 *
	 * @param GoogleAdsRow $row Data row returned from a query request.
	 *
	 * @return string The asset content.
	 */
	protected function get_asset_content( GoogleAdsRow $row ): string {
		/** @var Asset $asset */
		$asset = $row->getAsset();

		switch ( $asset->getType() ) {
			case AssetType::IMAGE:
				return $asset->getImageAsset()->getFullSize()->getUrl();
			case AssetType::TEXT:
				return $asset->getTextAsset()->getText();
			case AssetType::CALL_TO_ACTION:
				// When CallToActionType::UNSPECIFIED is returned, does not have a CallToActionAsset.
				if ( ! $asset->getCallToActionAsset() ) {
					return CallToActionType::UNSPECIFIED;
				}
				return CallToActionType::label( $asset->getCallToActionAsset()->getCallToAction() );
			case AssetType::YOUTUBE_VIDEO:
				return $asset->getYoutubeVideoAsset()->getYoutubeVideoId();
			default:
				return '';
		}
	}

	/**
	 * Convert Asset data to an array.
	 *
	 * @param GoogleAdsRow $row Data row returned from a query request.
	 *
	 * @return array The asset data converted.
	 */
	public function convert_asset( GoogleAdsRow $row ): array {
		return [
			'id'      => $row->getAsset()->getId(),
			'content' => $this->get_asset_content( $row ),
		];
	}

	/**
	 * Send a batch of operations to mutate assets.
	 *
	 * @param MutateOperation[] $operations
	 *
	 * @return array A list of Asset's ARN created.
	 * @throws ApiException If any of the operations fail.
	 */
	protected function mutate( array $operations ): array {
		$arns    = [];
		$request = new MutateGoogleAdsRequest();
		$request->setCustomerId( $this->options->get_ads_id() );
		$request->setMutateOperations( $operations );
		$responses = $this->client->getGoogleAdsServiceClient()->mutate( $request );

		foreach ( $responses->getMutateOperationResponses() as $response ) {
			if ( 'asset_result' === $response->getResponse() ) {
				$asset_result = $response->getAssetResult();
				$arns[]       = $asset_result->getResourceName();
			}
		}

		return $arns;
	}
}
