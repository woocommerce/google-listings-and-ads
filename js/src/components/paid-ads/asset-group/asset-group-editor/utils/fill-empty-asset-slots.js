/**
 * Result returned by fillEmptyAssetSlots.
 *
 * @typedef {Object} FillEmptyAssetSlotsResult
 * @property {string[]} assets Updated asset list.
 * @property {number} updatedCount Number of empty ("") slots that were filled.
 */

/**
 * Fill empty asset slots (represented by empty strings "") with unique
 * generated values.
 *
 * Existing non-empty values are preserved.
 * Empty slots that cannot be filled remain as "".
 *
 * @param {string[]} currentAssets Current asset values, where "" represents an empty slot.
 * @param {string[]} generatedAssets Newly generated candidate asset values.
 *
 * @return {FillEmptyAssetSlotsResult} Result containing updated assets and count of filled slots.
 */
export default function fillEmptyAssetSlots( currentAssets, generatedAssets ) {
	const existingAssetValues = new Set( currentAssets.filter( Boolean ) );

	let generatedIndex = 0;
	let updatedCount = 0;

	const assets = currentAssets.map( ( assetValue ) => {
		if ( assetValue !== '' ) {
			return assetValue;
		}

		while (
			generatedIndex < generatedAssets.length &&
			existingAssetValues.has( generatedAssets[ generatedIndex ] )
		) {
			generatedIndex++;
		}

		if ( generatedIndex < generatedAssets.length ) {
			const nextGeneratedValue = generatedAssets[ generatedIndex ];
			existingAssetValues.add( nextGeneratedValue );
			generatedIndex++;
			updatedCount++;
			return nextGeneratedValue;
		}

		return '';
	} );

	return { assets, updatedCount };
}
