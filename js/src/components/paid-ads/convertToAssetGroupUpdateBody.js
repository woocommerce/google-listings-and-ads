/**
 * Internal dependencies
 */
import { ASSET_KEY, ASSET_FORM_KEY } from '.~/constants';

/**
 * @typedef {import('.~/components/types.js').AssetGroupFormValues} AssetGroupFormValues
 * @typedef {import('.~/data/types.js').AssetEntityGroup} AssetEntityGroup
 * @typedef {import('.~/data/types.js').AssetOperations} AssetOperations
 * @typedef {import('.~/data/types.js').AssetEntityGroupUpdateBody} AssetEntityGroupUpdateBody
 */

/**
 * Differences the assets form values against the existing asset entity group and
 * returns the updating operations of the asset group.
 *
 * @param  {AssetEntityGroup} assetGroup The existing asset entity group as the base data for comparing the differences.
 * @param  {AssetGroupFormValues} values The assets form values to be compared.
 * @return {AssetOperations[]} The creation and deletion operations for updating the asset group.
 */
export function diffAssetOperations( assetGroup, values ) {
	const operations = [];
	const assets = assetGroup.assets;

	function pushDeletions( key, ...assetEntities ) {
		const deletions = assetEntities.map( ( assetEntity ) => ( {
			...assetEntity,
			// Setting the `content` to null indicates the asset deletion operation.
			content: null,
			field_type: key,
		} ) );
		operations.push( ...deletions );
	}

	// The assets of Google Ads are immutable once created. Therefore if it wants to update
	// a content of an existing asset, the processing will be made by two operations:
	// 1. Delete that existing asset.
	// 2. Create a new asset.
	//
	// In the general case, the creation order of the contents in an asset is also the order
	// of the asset's contents after fetching the asset group. Thus, this loop compares the
	// contents of form values and the contents of the assets in the existing entity group by
	// the same key and one by one in order, and then differences out the operations.
	//
	// The logics in this calculation:
	// - Loops all possible asset keys one by one and compares the contents of the form values
	//   and the contents of the assets by the same key with a nested loop. The index of each
	//   asset is compared by the one-way-walking-through in order.
	// - If a content of the form value is the same as the current indexed asset content,
	//   this means there is no needed operation.
	// - If a content of the form value is found the same content after walking through the
	//   rest asset contents, this means the walked but mismatched asset contents are deletions.
	//   (In order to make the same order of the asset contents in the updated asset group)
	// - If a content of the form value is not found in the asset contents, this means it
	//   could be an asset creation or the combined operations of asset deletion and creation.
	// - If an asset content is not found in the contents of the form value, this means it's a
	//   deletion.
	Object.values( ASSET_KEY ).forEach( ( key ) => {
		const contents = [ values[ key ] ].flat().filter( Boolean );
		const assetEntities = [ assets[ key ] ].flat().filter( Boolean );
		let entityIndex = 0;

		contents.forEach( ( content ) => {
			do {
				const assetEntity = assetEntities[ entityIndex ];

				if ( content === assetEntity?.content ) {
					break;
				}

				if ( assetEntity ) {
					pushDeletions( key, assetEntity );
				}

				entityIndex += 1;
			} while ( entityIndex < assetEntities.length );

			if ( entityIndex >= assetEntities.length ) {
				operations.push( {
					// Setting the `id` to null indicates the asset creation operation.
					id: null,
					content,
					field_type: key,
				} );
			}

			entityIndex += 1;
		} );

		pushDeletions( key, ...assetEntities.slice( entityIndex ) );
	} );

	return operations;
}

/**
 * Converts the assets form values into the request body based on an existing asset entity group
 * for updating that existing asset group.
 *
 * @param  {AssetEntityGroup} assetGroup The existing asset entity group as the base data for comparing the differences.
 * @param  {AssetGroupFormValues} values The assets form values to be converted.
 * @return {AssetEntityGroupUpdateBody} The request body for updating the asset group.
 */
export default function convertToAssetGroupUpdateBody( assetGroup, values ) {
	const [ path1, path2 ] = values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ];
	const assetOperations = diffAssetOperations( assetGroup, values );

	return {
		final_url: values[ ASSET_FORM_KEY.FINAL_URL ],
		path1,
		path2,
		assets: assetOperations,
	};
}
