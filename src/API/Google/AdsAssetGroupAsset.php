<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAssetGroupAssetQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\V11\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V11\Enums\AssetFieldTypeEnum\AssetFieldType;
use Google\Ads\GoogleAds\V11\Resources\AssetGroupAsset;



/**
 * Class AdsAssetGroupAsset
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsAssetGroupAsset implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * The Google Ads Client.
	 *
	 * @var GoogleAdsClient
	 */
	protected $client;

	/**
	 * Ads Asset class.
	 *
	 * @var AdsAsset
	 */
	protected $asset;

	/**
	 * The asset field types to use for the asset group assets.
	 *
	 * @var string[]
	 */
	protected array $asset_field_types;

	/**
	 * AdsAssetGroup constructor.
	 *
	 * @param GoogleAdsClient $client
	 * @param AdsAsset        $asset
	 */
	public function __construct( GoogleAdsClient $client, AdsAsset $asset ) {
		$this->client = $client;
		$this->asset  = $asset;

		$this->asset_field_types = [
			AssetFieldType::name( AssetFieldType::BUSINESS_NAME ),
			AssetFieldType::name( AssetFieldType::CALL_TO_ACTION_SELECTION ),
			AssetFieldType::name( AssetFieldType::DESCRIPTION ),
			AssetFieldType::name( AssetFieldType::HEADLINE ),
			AssetFieldType::name( AssetFieldType::LOGO ),
			AssetFieldType::name( AssetFieldType::LONG_HEADLINE ),
			AssetFieldType::name( AssetFieldType::MARKETING_IMAGE ),
			AssetFieldType::name( AssetFieldType::SQUARE_MARKETING_IMAGE ),
		];
	}

	/**
	 * Get Asset Group Assets for specific asset groups ids.
	 *
	 * @param array $asset_groups_ids The asset groups ids.
	 *
	 * @return array The asset group assets for the asset groups.
	 */
	public function get_asset_group_assets( array $asset_groups_ids ): array {
		if ( empty( $asset_groups_ids ) ) {
			return [];
		}

		$asset_group_assets = [];
		$asset_results      = ( new AdsAssetGroupAssetQuery() )
			->set_client( $this->client, $this->options->get_ads_id() )
			->add_columns( [ 'asset_group.id' ] )
			->where( 'asset_group.id', $asset_groups_ids, 'IN' )
			->where( 'asset_group_asset.field_type', $this->asset_field_types, 'IN' )
			->where( 'asset_group_asset.status', 'REMOVED', '!=' )
			->get_results();

		/** @var GoogleAdsRow $row */
		foreach ( $asset_results->iterateAllElements() as $row ) {

			/** @var AssetGroupAsset $asset_group_asset */
			$asset_group_asset = $row->getAssetGroupAsset();

			$field_type = strtolower( AssetFieldType::name( $asset_group_asset->getFieldType() ) );
			$asset_group_assets[ $row->getAssetGroup()->getId() ][ $field_type ][] = $this->asset->convert_asset( $row );
		}

		return $asset_group_assets;
	}
}
