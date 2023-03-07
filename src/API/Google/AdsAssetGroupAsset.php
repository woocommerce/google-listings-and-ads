<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Query\AdsAssetGroupAssetQuery;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads\GoogleAdsClient;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Google\Ads\GoogleAds\V12\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V12\Resources\AssetGroupAsset;
use Google\ApiCore\ApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Google\Ads\GoogleAds\V12\Services\MutateOperation;
use Google\Ads\GoogleAds\V12\Services\AssetGroupAssetOperation;
use Google\Ads\GoogleAds\Util\V12\ResourceNames;




/**
 * Class AdsAssetGroupAsset
 *
 * Use to get assets group assets for specific asset groups.
 * https://developers.google.com/google-ads/api/fields/v12/asset_group_asset
 *
 * @since 2.4.0
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
			AssetFieldType::name( AssetFieldType::PORTRAIT_MARKETING_IMAGE ),
		];
	}

	/**
	 * Get Assets for specific asset groups ids.
	 *
	 * @param array $asset_groups_ids The asset groups ids.
	 * @param array $fields           The asset field types to get.
	 *
	 * @return array The assets for the asset groups.
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function get_assets_by_asset_group_ids( array $asset_groups_ids, array $fields = [] ): array {
		try {
			if ( empty( $asset_groups_ids ) ) {
				return [];
			}

			if ( empty( $fields ) ) {
				$fields = $this->get_asset_field_types_query();
			}

			$asset_group_assets = [];
			$asset_results      = ( new AdsAssetGroupAssetQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->add_columns( [ 'asset_group.id' ] )
				->where( 'asset_group.id', $asset_groups_ids, 'IN' )
				->where( 'asset_group_asset.field_type', $fields, 'IN' )
				->where( 'asset_group_asset.status', 'REMOVED', '!=' )
				->get_results();

			/** @var GoogleAdsRow $row */
			foreach ( $asset_results->iterateAllElements() as $row ) {

				/** @var AssetGroupAsset $asset_group_asset */
				$asset_group_asset = $row->getAssetGroupAsset();
				$field_type        = AssetFieldType::label( $asset_group_asset->getFieldType() );

				switch ( $field_type ) {
					case AssetFieldType::BUSINESS_NAME:
					case AssetFieldType::CALL_TO_ACTION_SELECTION:
						$asset_group_assets[ $row->getAssetGroup()->getId() ][ $field_type ] = $this->asset->convert_asset( $row );
						break;
					default:
						$asset_group_assets[ $row->getAssetGroup()->getId() ][ $field_type ][] = $this->asset->convert_asset( $row );
				}
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
	 * @param bool   $only_first_asset_group Whether to return only the first asset group found.
	 *
	 * @return array The assets for the asset groups with a specific final url.
	 * @throws ExceptionWithResponseData When an ApiException is caught.
	 */
	public function get_assets_by_final_url( string $url, bool $only_first_asset_group = false ): array {
		try {

			$asset_group_assets = [];

			// Search urls with and without trailing slash.
			$asset_results = ( new AdsAssetGroupAssetQuery() )
				->set_client( $this->client, $this->options->get_ads_id() )
				->add_columns( [ 'asset_group.id', 'asset_group.path1', 'asset_group.path2' ] )
				->where( 'asset_group.final_urls', [ trailingslashit( $url ), untrailingslashit( $url ) ], 'CONTAINS ANY' )
				->where( 'asset_group_asset.field_type', $this->get_asset_field_types_query(), 'IN' )
				->where( 'asset_group_asset.status', 'REMOVED', '!=' )
				->where( 'asset_group.status', 'REMOVED', '!=' )
				->where( 'campaign.status', 'REMOVED', '!=' )
				->get_results();

			/** @var GoogleAdsRow $row */
			foreach ( $asset_results->iterateAllElements() as $row ) {

				/** @var AssetGroupAsset $asset_group_asset */
				$asset_group_asset = $row->getAssetGroupAsset();

				$field_type = AssetFieldType::label( $asset_group_asset->getFieldType() );
				switch ( $field_type ) {
					case AssetFieldType::BUSINESS_NAME:
					case AssetFieldType::CALL_TO_ACTION_SELECTION:
						$asset_group_assets[ $row->getAssetGroup()->getId() ][ $field_type ] = $this->asset->convert_asset( $row )['content'];
						break;
					default:
						$asset_group_assets[ $row->getAssetGroup()->getId() ][ $field_type ][] = $this->asset->convert_asset( $row )['content'];
				}

				$asset_group_assets[ $row->getAssetGroup()->getId() ]['display_url_path'] = [
					$row->getAssetGroup()->getPath1(),
					$row->getAssetGroup()->getPath2(),
				];
			}

			if ( $only_first_asset_group ) {
				return reset( $asset_group_assets ) ?: [];
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
	 * Get assets to be deleted.
	 *
	 * @param array $assets A list of assets.
	 *
	 * @return array The assets to be deleted.
	 */
	public function get_assets_to_be_deleted( array $assets ): array {
		return array_values(
			array_filter(
				$assets,
				function( $asset ) {
					return ! empty( $asset['id'] );
				}
			)
		);
	}

	/**
	 * Get assets to be created.
	 *
	 * @param array $assets A list of assets.
	 *
	 * @return array The assets to be created.
	 */
	public function get_assets_to_be_created( array $assets ): array {
		return array_values(
			array_filter(
				$assets,
				function( $asset ) {
					return ! empty( $asset['content'] );
				}
			)
		);
	}

	/**
	 * Get specific assets by asset types.
	 *
	 * @param int   $asset_group_id The asset group id.
	 * @param array $asset_field_types The asset field types types.
	 *
	 * @return array The assets.
	 */
	protected function get_specific_assets( int $asset_group_id, array $asset_field_types ): array {
		$result             = $this->get_assets_by_asset_group_ids( [ $asset_group_id ], $asset_field_types );
		$asset_group_assets = $result[ $asset_group_id ] ?? [];
		$specific_assets    = [];

		foreach ( $asset_group_assets as $field_type => $assets ) {
			foreach ( $assets as $asset ) {
				$specific_assets[] = array_merge( $asset, [ 'field_type' => $field_type ] );
			}
		}

		return $specific_assets;
	}

	/**
	 * Check if a asset type will be edited.
	 *
	 * @param string $field_type The asset field type.
	 * @param array  $assets The assets.
	 *
	 * @return bool True if the asset type is edited.
	 */
	protected function maybe_asset_type_is_edited( string $field_type, array $assets ): bool {
		return in_array( $field_type, array_column( $assets, 'field_type' ), true );
	}

	/**
	 * Get override asset operations.
	 *
	 * @param int   $asset_group_id The asset group id.
	 * @param array $asset_field_types The asset field types.
	 *
	 * @return array The asset group asset operations.
	 */
	protected function get_override_operations( int $asset_group_id, array $asset_field_types ): array {
		return array_map(
			function( $asset ) use ( $asset_group_id ) {
				return $this->delete_operation( $asset_group_id, $asset['field_type'], $asset['id'] );
			},
			$this->get_specific_assets( $asset_group_id, $asset_field_types )
		);
	}

	/**
	 * Edit assets group assets.
	 *
	 * @param int   $asset_group_id The asset group id.
	 * @param array $assets The assets to create.
	 *
	 * @return array The asset group asset operations.
	 * @throws Exception If the asset type is not supported.
	 */
	public function edit_operations( int $asset_group_id, array $assets ): array {
		if ( empty( $assets ) ) {
			return [];
		}

		$asset_group_assets_operations        = [];
		$assets_for_creation                  = $this->get_assets_to_be_created( $assets );
		$asset_arns                           = $this->asset->create_assets( $assets_for_creation );
		$total_assets                         = count( $assets_for_creation );
		$delete_asset_group_assets_operations = [];

		if ( $this->maybe_asset_type_is_edited( AssetFieldType::LOGO, $assets ) ) {
			// As we are not working with the LANDSCAPE_LOGO, we delete it so it does not interfere with the maximum quantities of logos.
			$delete_asset_group_assets_operations = $this->get_override_operations( $asset_group_id, [ AssetFieldType::name( AssetFieldType::LANDSCAPE_LOGO ) ] );
		}

		// The asset mutation operation results (ARNs) are returned in the same order as the operations are specified.
		// See: https://youtu.be/9KaVjqW5tVM?t=103
		for ( $i = 0; $i < $total_assets; $i++ ) {
			$asset_group_assets_operations[] = $this->create_operation( $asset_group_id, $assets_for_creation[ $i ]['field_type'], $asset_arns[ $i ] );
		}

		foreach ( $this->get_assets_to_be_deleted( $assets ) as $asset ) {
			$delete_asset_group_assets_operations[] = $this->delete_operation( $asset_group_id, $asset['field_type'], $asset['id'] );
		}

		// The delete operations must be executed first otherwise will cause a conflict with existing assets with identical content.
		// See here: https://github.com/woocommerce/google-listings-and-ads/pull/1870
		return array_merge( $delete_asset_group_assets_operations, $asset_group_assets_operations );

	}


	/**
	 * Creates an operation for an asset group asset.
	 *
	 * @param int    $asset_group_id The ID of the asset group.
	 * @param string $asset_field_type The field type of the asset.
	 * @param string $asset_arn The the asset ARN.
	 *
	 * @return MutateOperation The mutate create operation for the asset group asset.
	 */
	protected function create_operation( int $asset_group_id, string $asset_field_type, string $asset_arn ): MutateOperation {
		$operation             = new AssetGroupAssetOperation();
		$new_asset_group_asset = new AssetGroupAsset(
			[
				'asset'       => $asset_arn,
				'asset_group' => ResourceNames::forAssetGroup( $this->options->get_ads_id(), $asset_group_id ),
				'field_type'  => AssetFieldType::number( $asset_field_type ),
			]
		);

		return ( new MutateOperation() )->setAssetGroupAssetOperation( $operation->setCreate( $new_asset_group_asset ) );
	}

	/**
	 * Returns a delete operation for asset group asset.
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
