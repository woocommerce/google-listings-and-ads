/**
 * Internal dependencies
 */
import { ASSET_TEXT_SPECS } from '~/components/paid-ads/assetSpecs';
import getCharacterCounter from '~/utils/getCharacterCounter';
import { convertKeysFromSnakeCaseToCamelCase } from './utils';

/**
 * @typedef {import('~/data/actions').Campaign} Campaign
 * @typedef {import('~/data/types.js').AssetEntityGroup} AssetEntityGroup
 * @typedef {import('~/data/types.js').AdsBudgetRecommendation} AdsBudgetRecommendation
 * @typedef {import('~/data/types.js').AdsBudgetMetrics} AdsBudgetMetrics
 */

/**
 * Adapts the ads budget recommendation data received from API.
 *
 * @param {Object} data The ads budget recommendation data to be adapted.
 * @return {AdsBudgetRecommendation} Ads budget recommendation data.
 */
export function adaptAdsBudgetRecommendation( data ) {
	const validLevelKeys = [ 'recommended', 'high', 'low' ];
	const { currency, source } = data;
	const eventProps = { source, metrics_availability: 'all' };
	const availabilities = [];

	const reducer = ( payload, item ) => {
		const { level, ...adaptingData } = item;
		const key = level.toLowerCase();

		if ( validLevelKeys.includes( key ) ) {
			availabilities.push( adaptingData.metrics );
			adaptingData.currency = currency;
			payload[ key ] =
				convertKeysFromSnakeCaseToCamelCase( adaptingData );
		}

		return payload;
	};

	const result = data.recommendations.reduce( reducer, { eventProps } );

	if ( availabilities.filter( Boolean ).length === 0 ) {
		eventProps.metrics_availability = 'none';
	} else if ( ! availabilities.every( Boolean ) ) {
		eventProps.metrics_availability = 'partial';
	}

	eventProps.recommended_budget = result.recommended.dailyBudget;

	return result;
}

/**
 * Adapts the ads budget metrics data received from API.
 *
 * @param {Object} data The ads budget metrics data to be adapted.
 * @return {AdsBudgetMetrics} Ads budget metrics data.
 */
export function adaptAdsBudgetMetrics( data ) {
	const { budget, ...adaptingData } = data;
	adaptingData.dailyBudget = budget;
	return convertKeysFromSnakeCaseToCamelCase( adaptingData );
}

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
