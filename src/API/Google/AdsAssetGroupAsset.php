<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAssetGroupAssetQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsAsset;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\V11\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V11\Resources\AssetGroupAsset;
use Google\ApiCore\ApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;



/**
 * Class AdsAssetGroupAsset
 *
 * Use to get assets group assets for specific asset groups.
 * https://developers.google.com/google-ads/api/fields/v11/asset_group_asset
 *
 * @since x.x.x
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AdsAssetGroupAsset implements OptionsAwareInterface {

	use OptionsAwareTrait;
	use ApiExceptionTrait;

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
	 * AdsAssetGroup constructor.
	 *
	 * @param GoogleAdsClient $client
	 * @param AdsAsset        $asset
	 */
	public function __construct( GoogleAdsClient $client, AdsAsset $asset ) {
		$this->client = $client;
		$this->asset  = $asset;
	}

	/**
	 * Get the asset field types to use for the asset group assets query.
	 *
	 * @return string[]
	 */
	protected function get_asset_field_types_query(): array {
		return [
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
	 * Get Assets for specific asset groups ids.
	 *
	 * @param array $asset_groups_ids The asset groups ids.
	 *
	 * @return array The assets for the asset groups.
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function get_assets_by_asset_group_ids( array $asset_groups_ids ): array {
		try {
			if ( empty( $asset_groups_ids ) ) {
				return [];
			}

			$asset_group_assets = [];
			$asset_results      = ( new AdsAssetGroupAssetQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->add_columns( [ 'asset_group.id' ] )
				->where( 'asset_group.id', $asset_groups_ids, 'IN' )
				->where( 'asset_group_asset.field_type', $this->get_asset_field_types_query(), 'IN' )
				->where( 'asset_group_asset.status', 'REMOVED', '!=' )
				->get_results();

			/** @var GoogleAdsRow $row */
			foreach ( $asset_results->iterateAllElements() as $row ) {

				/** @var AssetGroupAsset $asset_group_asset */
				$asset_group_asset = $row->getAssetGroupAsset();

				$field_type = AssetFieldType::label( $asset_group_asset->getFieldType() );
				$asset_group_assets[ $row->getAssetGroup()->getId() ][ $field_type ][] = $this->asset->convert_asset( $row );
			}

			return $asset_group_assets;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_api_exception_errors( $e );
			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Error retrieving asset groups assets: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[ 'errors' => $errors ]
			);
		}

	}
}
