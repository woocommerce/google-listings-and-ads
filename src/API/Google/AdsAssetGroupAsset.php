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
use Google\Ads\GoogleAds\V11\Services\MutateOperation;
use Google\Ads\GoogleAds\V11\Services\AssetGroupAssetOperation;
use Google\Ads\GoogleAds\Util\V11\ResourceNames;




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
	 * Temporary ID to use within a batch job.
	 * A negative number which is unique for all the created resources.
	 *
	 * @var int
	 */
	protected static $temporary_id = -4;

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

	/**
	 * Get Assets for specific final URL.
	 *
	 * @param string $url The final url.
	 *
	 * @return array The assets for the asset groups with a specific final url.
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function get_assets_by_final_url( string $url ): array {
		try {

			$asset_group_assets = [];

			// Search urls with and without trailing slash.
			$asset_results = ( new AdsAssetGroupAssetQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->where( 'asset_group.final_urls', [ rtrim( $url, '/' ), rtrim( $url, '/' ) . '/' ], 'CONTAINS ANY' )
				->where( 'asset_group_asset.field_type', $this->get_asset_field_types_query(), 'IN' )
				->where( 'asset_group_asset.status', 'REMOVED', '!=' )
				->get_results();

			/** @var GoogleAdsRow $row */
			foreach ( $asset_results->iterateAllElements() as $row ) {

				/** @var AssetGroupAsset $asset_group_asset */
				$asset_group_asset = $row->getAssetGroupAsset();

				$field_type                          = AssetFieldType::label( $asset_group_asset->getFieldType() );
				$asset_group_assets[ $field_type ][] = $this->asset->get_asset_content( $row );
			}

			return $asset_group_assets;
		} catch ( ApiException $e ) {
			do_action( 'woocommerce_gla_ads_client_exception', $e, __METHOD__ );

			$errors = $this->get_api_exception_errors( $e );
			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Error retrieving asset groups assets by final url: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$this->map_grpc_code_to_http_status_code( $e ),
				null,
				[ 'errors' => $errors ]
			);
		}

	}

	/**
	 * Edit assets group assets.
	 *
	 * @param int   $asset_group_id The asset group id.
	 * @param array $assets The assets to create.
	 *
	 * @return array The asset group asset operations.
	 */
	public function edit_operations_assets_group_assets( int $asset_group_id, array $assets ): array {
		if ( empty( $assets ) ) {
			return [];
		}

		$assets_operations                    = [];
		$asset_group_assets_operations        = [];
		$delete_asset_group_assets_operations = [];

		foreach ( $assets as $asset ) {

			// If content exists, create asset and asset group asset.
			if ( ! empty( $asset['content'] ) ) {
				$assets_operations[]             = $this->asset->create_operation( $asset, self::$temporary_id );
				$asset_group_assets_operations[] = $this->create_operation( $asset_group_id, $asset['field_type'], self::$temporary_id-- );
			}

			// As Assets are inmmutable, we delete the old link between the asset and the asset group if exists.
			if ( ! empty( $asset['id'] ) ) {
				$delete_asset_group_assets_operations[] = $this->delete_operation( $asset_group_id, $asset['field_type'], $asset['id'] );
			}
		}

		// Delete asset group assets operations must be executed last so we are never under the minimum quantity.
		$operations = array_merge( $assets_operations, $asset_group_assets_operations, $delete_asset_group_assets_operations );

		return $operations;

	}


	/**
	 * Creates an operation for an asset group asset.
	 *
	 * @param int    $asset_group_id The ID of the asset group.
	 * @param string $asset_field_type The field type of the asset.
	 * @param int    $asset_id The ID of the asset.
	 *
	 * @return AssetGroupAssetOperation The create operation for the asset group asset.
	 */
	protected function create_operation( int $asset_group_id, string $asset_field_type, int $asset_id ): MutateOperation {
		$operation             = new AssetGroupAssetOperation();
		$new_asset_group_asset = new AssetGroupAsset(
			[
				'asset'       => ResourceNames::forAsset( $this->options->get_ads_id(), $asset_id ),
				'asset_group' => ResourceNames::forAssetGroup( $this->options->get_ads_id(), $asset_group_id ),
				'field_type'  => AssetFieldType::number( $asset_field_type ),
			]
		);

		return ( new MutateOperation() )->setAssetGroupAssetOperation( $operation->setCreate( $new_asset_group_asset ) );
	}

	/**
	 * Returns a campaign delete operation.
	 *
	 * @param int    $asset_group_id The ID of the asset group.
	 * @param string $asset_field_type The field type of the asset.
	 * @param int    $asset_id The ID of the asset.
	 *
	 * @return MutateOperation The remove operation for the asset group asset.
	 */
	protected function delete_operation( int $asset_group_id, string $asset_field_type, int $asset_id ): MutateOperation {
		$asset_group_asset_resource_name = ResourceNames::forAssetGroupAsset( $this->options->get_ads_id(), $asset_group_id, $asset_id, AssetFieldType::name( $asset_field_type ) );
		$operation                       = ( new AssetGroupAssetOperation() )->setRemove( $asset_group_asset_resource_name );
		return ( new MutateOperation() )->setAssetGroupAssetOperation( $operation );
	}




}
