<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Google\Ads\GoogleAds\V11\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V11\Enums\AssetTypeEnum\AssetType;
use Google\Ads\GoogleAds\V11\Resources\Asset;
use Google\Ads\GoogleAds\V11\Services\AssetOperation;
use Google\Ads\GoogleAds\V11\Services\MutateOperation;
use Google\Ads\GoogleAds\Util\V11\ResourceNames;
use Google\Ads\GoogleAds\V11\Common\TextAsset;
use Google\Ads\GoogleAds\V11\Common\ImageAsset;
use Google\Ads\GoogleAds\V11\Common\CallToActionAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WP;
use Exception;

/**
 * Class AdsAsset
 *
 * Used for the Performance Max Campaigns
 * https://developers.google.com/google-ads/api/docs/performance-max/assets
 *
 * @since x.x.x
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
	protected $client;

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
				return AssetType::IMAGE;
			case AssetFieldType::CALL_TO_ACTION_SELECTION:
				return AssetType::CALL_TO_ACTION;
			case AssetFieldType::HEADLINE:
			case AssetFieldType::LONG_HEADLINE:
			case AssetFieldType::DESCRIPTION:
			case AssetFieldType::BUSINESS_NAME:
				return AssetType::TEXT;
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
	 * @throws Exception If the image url is not a valid url.
	 */
	protected function get_image_data( string $url ): array {
		$image_data = $this->wp->wp_remote_get( $url );

		if ( is_wp_error( $image_data ) || empty( $image_data['body'] ) ) {
			throw new Exception( 'Incorrect image asset url.' );
		}

		return $image_data;
	}

	/**
	 * Returns a list of batches of assets.
	 *
	 * @param array $assets A list of assets.
	 *
	 * @return array A list of batches of assets.
	 */
	protected function create_batches( array $assets ): array {
		$batches = [];
		return $batches;
	}

	/**
	 * Creates the assets so they can be used in the asset groups.
	 *
	 * @param array $assets The assets to create.
	 *
	 * @return array A list of Asset's ARN created.
	 *
	 * @throws Exception If the asset type is not supported or if the image url is not a valid url.
	 * @throws ApiException If any of the operations fail.
	 */
	public function create_assets( array $assets ): array {
		$operations = [];
		foreach ( $assets as $asset ) {
			$operations[] = $this->create_operation( $asset, self::$temporary_id-- );
		}

		if ( empty( $operations ) ) {
			return [];
		}

		return $this->mutate( $operations );

	}

	/**
	 * Returns an operation to create a text asset.
	 *
	 * @param array $data The asset data.
	 * @param int   $temporary_id The temporary ID to use for the asset.
	 *
	 * @return MutateOperation The create asset operation.
	 * @throws Exception If the asset type is not supported or if the image url is not a valid url.
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
				$image_data = $this->get_image_data( $data['content'] );
				$asset->setImageAsset( new ImageAsset( [ 'data' => $image_data['body'] ] ) );
				$asset->setName( basename( $data['content'] ) );
				break;
			case AssetType::TEXT:
				$asset->setTextAsset( new TextAsset( [ 'text' => $data['content'] ] ) );
				break;
			default:
				throw new Exception( 'Asset type not supported' );
		}

		$operation = ( new AssetOperation() )->setCreate( $asset );
		return ( new MutateOperation() )->setAssetOperation( $operation );

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
		$arns      = [];
		$responses = $this->client->getGoogleAdsServiceClient()->mutate(
			$this->options->get_ads_id(),
			$operations
		);

		foreach ( $responses->getMutateOperationResponses() as $response ) {
			if ( 'asset_result' === $response->getResponse() ) {
				$asset_result = $response->getAssetResult();
				$arns[]       = $asset_result->getResourceName();
			}
		}

		return $arns;
	}
}
