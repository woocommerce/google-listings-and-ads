<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

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
	 * Temporary ID to use within a batch job.
	 * A negative number which is unique for all the created resources.
	 *
	 * @var int
	 */
	protected const TEMPORARY_ID = -5;

	/**
	 * Return a temporary resource name for the asset.
	 *
	 * @param int $temporary_id The temporary ID to use for the asset.
	 *
	 * @return string The Asset resource name.
	 */
	protected function temporary_resource_name( $temporary_id = self::TEMPORARY_ID ): string {
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
	protected function get_asset_type_by_field_type( $field_type ): int {
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
	 * Returns an operation to create a text asset.
	 *
	 * @param array $data The assets to use the text asset.
	 * @param int   $temporary_id The temporary ID to use for the asset.
	 *
	 * @return MutateOperation The create asset operation.
	 * @throws Exception If the asset type is not supported or if the image url is not a valid url.
	 */
	public function create_operation( array $data, int $temporary_id = self::TEMPORARY_ID ): MutateOperation {
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
				$image_data = wp_remote_get( $data['content'] );

				if ( is_wp_error( $image_data ) || empty( $image_data['body'] ) ) {
					throw new Exception( 'Incorrect image asset url.' );
				}

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
	 * Convert Asset data to an array.
	 *
	 * @param GoogleAdsRow $row Data row returned from a query request.
	 *
	 * @return array
	 */
	public function convert_asset( GoogleAdsRow $row ): array {
		/** @var Asset $asset */
		$asset = $row->getAsset();

		switch ( $asset->getType() ) {
			case AssetType::IMAGE:
				$data = $asset->getImageAsset()->getFullSize()->getUrl();
				break;
			case AssetType::TEXT:
				$data = $asset->getTextAsset()->getText();
				break;
			case AssetType::CALL_TO_ACTION:
				$data = CallToActionType::label( $asset->getCallToActionAsset()->getCallToAction() );
				break;
			default:
				$data = '';
		}

		return [
			'id'      => $asset->getId(),
			'content' => $data,
		];
	}
}
