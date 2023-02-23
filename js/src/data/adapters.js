/**
 * Internal dependencies
 */
import { ASSET_TEXT_SPECS } from '.~/components/paid-ads/assetSpecs';
import getCharacterCounter from '.~/utils/getCharacterCounter';

/**
 * @typedef {import('.~/data/actions').Campaign} Campaign
 * @typedef {import('.~/data/types.js').AssetEntityGroup} AssetEntityGroup
 */

/**
 * Adapts the campaign entity received from API.
 *
 * @param {Object} campaign The campaign entity to be adapted.
 * @return {Campaign} Campaign data.
 */
export function adaptAdsCampaign( campaign ) {
	const allowMultiple = campaign.targeted_locations.length > 0;
	const displayCountries = allowMultiple
		? campaign.targeted_locations
		: [ campaign.country ];
	return {
		...campaign,
		allowMultiple,
		displayCountries,
	};
}

/**
 * Adapts the asset entity group received from API.
 *
 * The multi-value assets may not be sorted by their creation time in descending
 * order when fetching the data from Google Ads API, and there are some text-type
 * assets that have a smaller maximum character count for the first text.
 *
 * This may cause the fetched first text to exceed the smaller maximum count.
 * For example, udpating headline assets with
 * [
 *   'My Shop',
 *   '12345678901234567890 Foo Shop',
 *   '12345678901234567890 Bar Shop',
 * ],
 * but getting
 * [
 *   '12345678901234567890 Foo Shop', // exceeds the 15-character-count limit
 *   '12345678901234567890 Bar Shop',
 *   'My Shop',
 * ]
 *
 * When the case happens, this function will try to move an asset text that has a
 * valid character count to the first index of the asset text array.
 *
 * @param {AssetEntityGroup} assetGroup The asset entity group to be adapted.
 * @return {AssetEntityGroup} Adapted asset entity group.
 */
export function adaptAssetGroup( assetGroup ) {
	const smallerMaxMap = new Map();

	ASSET_TEXT_SPECS.forEach( ( spec ) => {
		const { maxCharacterCounts } = spec;

		if ( Array.isArray( maxCharacterCounts ) ) {
			const [ first, second ] = maxCharacterCounts;

			if ( first < second ) {
				smallerMaxMap.set( spec.key, first );
			}
		}
	} );

	const countCharacter = getCharacterCounter( 'google-ads' );
	const assets = { ...assetGroup.assets };

	smallerMaxMap.forEach( ( smallerMax, key ) => {
		const textEntities = assets[ key ];

		if ( ! textEntities || textEntities.length < 2 ) {
			return;
		}

		if ( countCharacter( textEntities[ 0 ].content ) > smallerMax ) {
			const validIndex = textEntities.findIndex(
				( { content } ) => countCharacter( content ) <= smallerMax
			);

			if ( validIndex > 0 ) {
				textEntities.unshift( ...textEntities.splice( validIndex, 1 ) );
				assets[ key ] = textEntities;
			}
		}
	} );

	return {
		...assetGroup,
		assets,
	};
}
