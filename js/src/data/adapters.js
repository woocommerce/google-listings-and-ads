/**
 * Internal dependencies
 */
import { ASSET_TEXT_SPECS } from '~/components/paid-ads/assetSpecs';
import getCharacterCounter from '~/utils/getCharacterCounter';
import {
	convertKeysFromSnakeCaseToCamelCase,
	applyAssetTextCharacterLimits,
} from './utils';

/**
 * @typedef {import('~/data/actions').Campaign} Campaign
 * @typedef {import('~/data/types.js').AssetEntityGroup} AssetEntityGroup
 * @typedef {import('~/data/types.js').AdsBudgetRecommendation} AdsBudgetRecommendation
 * @typedef {import('~/data/types.js').AdsBudgetRecommendationEntity} AdsBudgetRecommendationEntity
 * @typedef {import('~/data/types.js').AdsBudgetMetrics} AdsBudgetMetrics
 * @typedef {import('~/data/types.js').RaiseAdsBudgetRecommendations} RaiseAdsBudgetRecommendations
 */

/**
 * A workaround to eliminate recommendations when conversion-related metrics
 * are identical. It only keeps the lowest-budget one, taking its place as
 * "recommended" level. Otherwise, it returns the original recommendations.
 *
 * Currently, Google Ads API might return the exact same forecast metrics, so
 * this workaround is intended to avoid user confusion. It only keeps the
 * lowest budget since that would have the best ROAS.
 *
 * @param {Array<AdsBudgetRecommendationEntity>} rawRecommendations The raw budget recommendations.
 * @return {Array<AdsBudgetRecommendationEntity>} The eliminated or original recommendations.
 */
function eliminateIdenticalMetrics( rawRecommendations ) {
	const recommendations = rawRecommendations.filter(
		( item ) => item.metrics
	);

	if ( recommendations.length <= 1 ) {
		return rawRecommendations;
	}

	let lowest = recommendations[ 0 ];

	for ( let i = 1; i < recommendations.length; i += 1 ) {
		const item = recommendations[ i ];

		if (
			item.metrics.conversions === lowest.metrics.conversions &&
			item.metrics.conversionsValue === lowest.metrics.conversionsValue
		) {
			if ( item.dailyBudget < lowest.dailyBudget ) {
				lowest = item;
			}
		} else {
			return rawRecommendations;
		}
	}

	return [
		{
			...lowest,
			level: 'recommended',
		},
	];
}

/**
 * Adapts raw budget recommendation data.
 *
 * @param {Array<Object>} recommendations The array of recommendations to be adapted.
 * @return {Object} An object containing adapted recommendations by level and a list of available metrics.
 */
function adaptBudgetRecommendations( recommendations ) {
	const validLevelKeys = [ 'recommended', 'high', 'low', 'current' ];
	const adaptedData = {};
	const availabilities = [];

	eliminateIdenticalMetrics( recommendations ).forEach( ( item ) => {
		const { level, ...adaptingItem } = item;
		const key = level.toLowerCase();

		if ( validLevelKeys.includes( key ) ) {
			availabilities.push( adaptingItem.metrics );
			adaptedData[ key ] = adaptingItem;
		}
	} );

	return { adaptedData, availabilities };
}

/**
 * Adapts the ads budget recommendation data received from API.
 *
 * @param {Object} rawData The ads budget recommendation data to be adapted.
 * @return {AdsBudgetRecommendation} Ads budget recommendation data.
 */
export function adaptAdsBudgetRecommendation( rawData ) {
	const { currency, source, recommendations, ...data } =
		convertKeysFromSnakeCaseToCamelCase( rawData );

	const { adaptedData, availabilities } =
		adaptBudgetRecommendations( recommendations );

	// Add currency to each adapted item at the call site
	Object.keys( adaptedData ).forEach( ( key ) => {
		adaptedData[ key ].currency = currency;
	} );

	Object.assign( data, adaptedData );

	data.recommendedDailyBudget = data.recommended.dailyBudget;
	data.eventProps = {
		source,
		recommended_budget: data.recommendedDailyBudget,
		metrics_availability: 'all',
	};

	if ( availabilities.filter( Boolean ).length === 0 ) {
		data.eventProps.metrics_availability = 'none';
	} else if ( ! availabilities.every( Boolean ) ) {
		data.eventProps.metrics_availability = 'partial';
	}

	return data;
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
 * For example, updating headline assets with
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

/**
 * Adapts the raise ads budget recommendations data received from API.
 *
 * @param {Array<Object>} rawData The raise ads budget recommendations data to be adapted.
 * @return {Array<RaiseAdsBudgetRecommendations>} Raise ads budget recommendations data.
 */
export function adaptRaiseAdsBudgetRecommendations( rawData ) {
	if ( ! Array.isArray( rawData ) || rawData.length === 0 ) {
		return [];
	}

	const finalData = [];

	rawData.forEach( ( item ) => {
		const camelCaseItem = convertKeysFromSnakeCaseToCamelCase( item );
		const recommendations =
			camelCaseItem?.details?.campaignBudgetRecommendation?.budgetOptions;

		if (
			! Array.isArray( recommendations ) ||
			recommendations.length === 0
		) {
			return;
		}

		const { adaptedData } = adaptBudgetRecommendations( recommendations );

		// Add dailyBudget to each adapted item at the call site
		Object.keys( adaptedData ).forEach( ( key ) => {
			adaptedData[ key ].dailyBudget = adaptedData[ key ].budgetAmount;
		} );

		const { source, details, ...data } = camelCaseItem;
		Object.assign( data, adaptedData );

		data.recommendedDailyBudget = data.recommended.dailyBudget;

		finalData.push( data );
	} );

	return finalData;
}

/**
 * Formats raw API items into a grouped object by type.
 * @param {Array}  items The raw items array from API.
 * @param {string} valueKey The key to extract (e.g., 'text' or 'temporary_image_url').
 * @param {string} [filterType] Optional type to filter by. Possible values can be headline, description, long_headline, marketing_image, square_marketing_image, portrait_marketing_image.
 * @return {Object} Groups of assets keyed by their type.
 */
export function adaptGenAIAssets( items = [], valueKey, filterType ) {
	const data = {};

	for ( const item of items ) {
		const { type, [ valueKey ]: value } = item;

		// Skip if:
		// We have a filter and it doesn't match
		// The value for the specified key is empty/null
		if ( ( filterType && type !== filterType ) || ! value ) {
			continue;
		}

		if ( ! data[ type ] ) {
			data[ type ] = [];
		}

		data[ type ].push( value );
	}

	if ( valueKey === 'text' ) {
		return applyAssetTextCharacterLimits( data, ASSET_TEXT_SPECS );
	}

	return data;
}
