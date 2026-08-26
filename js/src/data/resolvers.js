/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	REPORT_SOURCE_PAID,
	REPORT_SOURCE_FREE,
	ISSUE_TYPE_ACCOUNT,
} from '~/constants';
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';
import {
	getReportKey,
	getCountryCodesKey,
	getAdsBudgetMetricsKey,
	arrayToUnderscoreKey,
} from './utils';
import { handleApiError } from '~/utils/handleError';
import {
	adaptAdsBudgetRecommendation,
	adaptAdsBudgetMetrics,
	adaptAdsCampaign,
	adaptAssetGroup,
	adaptRaiseAdsBudgetRecommendations,
} from './adapters';
import { fetchWithHeaders, awaitPromise, recordGlaDataEvent } from './controls';
import {
	fetchShippingRates,
	fetchShippingTimes,
	fetchSettings,
	fetchJetpackAccount,
	fetchGoogleAccount,
	fetchGoogleMCAccount,
	fetchExistingGoogleMCAccounts,
	fetchGoogleAdsAccount,
	fetchGoogleAdsAccountBillingStatus,
	fetchExistingGoogleAdsAccounts,
	fetchGoogleAdsAccountStatus,
	receiveGoogleMCContactInformation,
	fetchTargetAudience,
	fetchMCSetup,
	fetchYouTubeAccount,
	fetchGoogleTagManagerAccount,
	fetchMarkets,
	receiveGoogleAccountAccess,
	receiveReport,
	receiveMCProductStatistics,
	receiveMCIssues,
	receiveMCProductFeed,
	receiveMCReviewRequest,
	receiveMappingSources,
	receiveMappingAttributes,
	receiveMappingRules,
	receiveStoreCategories,
	receiveTours,
	receiveGtinMigrationStatus,
	receiveAdsRecommendations,
	receiveCYOIncentives,
	receiveEnhancedConversionsStatus,
	receiveMcLanguagesCurrencies,
	receiveAdsSettings,
	receiveNotifications,
} from './actions';

/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 * @typedef {import('./selectors').PriceBenchmarkQueryParams} PriceBenchmarkQueryParams
 */

function* handleResponseError( response, leadingMessage ) {
	const bodyPromise = response?.json() || response?.text();
	const error = yield awaitPromise( bodyPromise );

	handleApiError( error, leadingMessage );
}

export function* getShippingRates() {
	yield fetchShippingRates();
}

export function* getShippingTimes() {
	yield fetchShippingTimes();
}

export function* getSettings() {
	yield fetchSettings();
}

export function* getJetpackAccount() {
	yield fetchJetpackAccount();
}

export function* getGoogleAccount() {
	yield fetchGoogleAccount();
}

getGoogleAccount.shouldInvalidate = ( action ) => {
	return action.type === TYPES.DISCONNECT_ACCOUNTS_GOOGLE;
};

export function* getGoogleAccountAccess() {
	try {
		const data = yield apiFetch( {
			path: `${ API_NAMESPACE }/google/reconnected`,
		} );

		yield receiveGoogleAccountAccess( data );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading Google account access info.',
				'google-listings-and-ads'
			)
		);
	}
}

getGoogleAccountAccess.shouldInvalidate = ( action ) => {
	return action.type === TYPES.DISCONNECT_ACCOUNTS_GOOGLE;
};

export function* getGoogleMCAccount() {
	yield fetchGoogleMCAccount();
}

export function* getExistingGoogleMCAccounts() {
	yield fetchExistingGoogleMCAccounts();
}

export function* getGoogleAdsAccount() {
	yield fetchGoogleAdsAccount();
}

getGoogleAdsAccount.shouldInvalidate = ( action ) => {
	return (
		action.type === TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS &&
		action.invalidateRelatedState
	);
};

export function* getGoogleAdsAccountBillingStatus() {
	yield fetchGoogleAdsAccountBillingStatus();
}

getGoogleAdsAccountBillingStatus.shouldInvalidate = ( action ) => {
	return action.type === TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS;
};

export function* getExistingGoogleAdsAccounts() {
	yield fetchExistingGoogleAdsAccounts();
}

getExistingGoogleAdsAccounts.shouldInvalidate =
	getGoogleAdsAccount.shouldInvalidate;

export function* getGoogleMCContactInformation() {
	try {
		const data = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/contact-information`,
		} );
		yield receiveGoogleMCContactInformation( data );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading Google Merchant Center contact information.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getMCCountriesAndContinents() {
	try {
		const query = { continents: true };
		const path = addQueryArgs( `${ API_NAMESPACE }/mc/countries`, query );
		const data = yield apiFetch( { path } );

		return {
			type: TYPES.RECEIVE_MC_COUNTRIES_AND_CONTINENTS,
			data,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading supported country details.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getTargetAudience() {
	yield fetchTargetAudience();
}

export function* getAdsCampaigns( query ) {
	try {
		const campaigns = yield apiFetch( {
			path: addQueryArgs( `${ API_NAMESPACE }/ads/campaigns`, query ),
		} );

		return {
			type: TYPES.RECEIVE_ADS_CAMPAIGNS,
			query,
			adsCampaigns: campaigns.map( adaptAdsCampaign ),
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading ads campaigns.',
				'google-listings-and-ads'
			)
		);
	}
}

getAdsCampaigns.shouldInvalidate = ( action, query ) => {
	return (
		( action.type === TYPES.UPDATE_ADS_CAMPAIGN ||
			action.type === TYPES.DELETE_ADS_CAMPAIGN ||
			action.type === TYPES.CREATE_ADS_CAMPAIGN ) &&
		query?.exclude_removed === false
	);
};

export function* getAdsCampaignsMissingEuDeclaration() {
	try {
		const campaigns = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/campaigns/missing-eu-political-declaration`,
		} );

		return {
			type: TYPES.RECEIVE_ADS_CAMPAIGNS_MISSING_EU_DECLARATION,
			campaigns,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading campaigns missing EU political declaration.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getCampaignAssetGroups( campaignId ) {
	const endpoint = `${ API_NAMESPACE }/ads/campaigns/asset-groups`;
	const query = { campaign_id: campaignId };
	const path = addQueryArgs( endpoint, query );

	try {
		const assetGroups = yield apiFetch( { path } );

		return {
			type: TYPES.RECEIVE_CAMPAIGN_ASSET_GROUPS,
			campaignId,
			assetGroups: assetGroups.map( adaptAssetGroup ),
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading the assets of the campaign.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getMCSetup() {
	yield fetchMCSetup();
}

export function* getMCProductStatistics() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/product-statistics`,
		} );

		yield receiveMCProductStatistics( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading your merchant center product statistics.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getMCReviewRequest() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/review`,
		} );

		yield receiveMCReviewRequest( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading your merchant center product review request status.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getMCIssues( query ) {
	try {
		const { issue_type: issueType, ...args } = query;

		const response = yield apiFetch( {
			path: addQueryArgs(
				`${ API_NAMESPACE }/mc/issues/${
					issueType || ISSUE_TYPE_ACCOUNT
				}`,
				args
			),
		} );

		yield receiveMCIssues( query, response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading issues to resolve.',
				'google-listings-and-ads'
			)
		);
	}
}

getMCIssues.shouldInvalidate = ( action ) => {
	return action.type === TYPES.UPDATE_MC_PRODUCTS_VISIBILITY;
};

export function* getMCProductFeed( query ) {
	try {
		const response = yield apiFetch( {
			path: addQueryArgs( `${ API_NAMESPACE }/mc/product-feed`, query ),
		} );

		yield receiveMCProductFeed( query, response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading product feed.',
				'google-listings-and-ads'
			)
		);
	}
}

getMCProductFeed.shouldInvalidate = ( action, query ) => {
	if ( action.type === TYPES.UPDATE_MC_PRODUCTS_VISIBILITY ) {
		return true;
	}

	return (
		action.type === TYPES.RECEIVE_MC_PRODUCT_FEED &&
		( action.query.per_page !== query.per_page ||
			action.query.orderby !== query.orderby ||
			action.query.order !== query.order )
	);
};

const reportTypeMap = new Map( [
	[ REPORT_SOURCE_FREE, 'mc' ],
	[ REPORT_SOURCE_PAID, 'ads' ],
] );

export function* getReportByApiQuery( category, type, reportQuery ) {
	const reportType = reportTypeMap.get( type );
	const url = `${ API_NAMESPACE }/${ reportType }/reports/${ category }`;
	const path = addQueryArgs( url, reportQuery );

	try {
		const data = yield apiFetch( { path } );
		const reportKey = getReportKey( category, type, reportQuery );
		yield receiveReport( reportKey, data );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading report.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getMappingAttributes() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/mapping/attributes`,
		} );

		yield receiveMappingAttributes( response.data );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading the mapping attributes.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getMappingSources( attributeKey ) {
	try {
		if ( ! attributeKey ) {
			return;
		}

		const response = yield apiFetch( {
			path: addQueryArgs( `${ API_NAMESPACE }/mc/mapping/sources`, {
				attribute: attributeKey,
			} ),
		} );

		yield receiveMappingSources( response.data, attributeKey );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading the mapping sources for the selected attribute.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Fetches the Attribute Mapping Rules and calls receive action
 *
 * @param {Object} pagination Object containing client pagination parameters like page and perPage
 * @see AttributeMappingRulesController.php
 */
export function* getMappingRules( pagination ) {
	try {
		const response = yield fetchWithHeaders( {
			path: addQueryArgs( `${ API_NAMESPACE }/mc/mapping/rules`, {
				page: pagination.page,
				per_page: pagination.perPage,
			} ),
		} );

		const total = parseInt( response.headers.get( 'x-wp-total' ), 10 );
		const pages = parseInt( response.headers.get( 'x-wp-totalpages' ), 10 );
		const rules = response.data;

		yield receiveMappingRules( rules, {
			...pagination,
			total,
			pages,
		} );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading the mapping rules.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Refresh if some UPSERT or DELETE action for mapping rules happens in the APP.
 *
 * @param {Object} action The performed action
 * @return {boolean} True if the action should be invalidated
 */
getMappingRules.shouldInvalidate = ( action ) => {
	return (
		action.type === TYPES.UPSERT_MAPPING_RULE ||
		action.type === TYPES.DELETE_MAPPING_RULE
	);
};

/**
 * Resolver for getting the Store categories.
 */
export function* getStoreCategories() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/mapping/categories`,
		} );

		yield receiveStoreCategories( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting the store categories.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Resolver for getting all tours.
 */
export function* getTours() {
	try {
		const { data } = yield fetchWithHeaders( {
			path: `${ API_NAMESPACE }/tours`,
		} );

		yield receiveTours( data );
	} catch ( response ) {
		yield handleResponseError(
			response,
			__(
				'There was an error getting the tours.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getGoogleAdsAccountStatus() {
	yield fetchGoogleAdsAccountStatus();
}

/**
 * Refresh account status if Google Ads account is updated.
 *
 * @param {Object} action The performed action
 * @return {boolean} True if the action should be invalidated
 */
getGoogleAdsAccountStatus.shouldInvalidate = ( action ) => {
	return action.type === TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS;
};

/**
 * Fetch ad budget recommendations for the specified country codes.
 *
 * @param {Array<CountryCode>} [countryCodes] An array of country codes for which to fetch budget recommendations.
 */
export function* getAdsBudgetRecommendations( countryCodes ) {
	if ( ! countryCodes || ! countryCodes.length ) {
		return;
	}

	const countryCodesKey = getCountryCodesKey( countryCodes );
	const endpoint = `${ API_NAMESPACE }/ads/campaigns/budget-recommendation`;
	const query = { country_codes: countryCodes };
	const path = addQueryArgs( endpoint, query );

	try {
		let { data } = yield fetchWithHeaders( { path } );
		data = adaptAdsBudgetRecommendation( data );

		yield recordGlaDataEvent(
			TYPES.RECEIVE_ADS_BUDGET_RECOMMENDATIONS,
			data
		);

		return {
			type: TYPES.RECEIVE_ADS_BUDGET_RECOMMENDATIONS,
			countryCodesKey,
			data,
		};
	} catch ( response ) {
		// Intentionally silence the specific in case the no budget recommendations are found from the API.
		if ( response.status === 404 ) {
			return;
		}

		yield handleResponseError(
			response,
			__(
				'There was an error getting the budget recommendation.',
				'google-listings-and-ads'
			)
		);
	}
}

getAdsBudgetRecommendations.shouldInvalidate = ( action ) => {
	return action.type === TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS;
};

export function* getAdsBudgetMetrics( countryCodes, budget ) {
	try {
		const { data } = yield fetchWithHeaders( {
			path: addQueryArgs(
				`${ API_NAMESPACE }/ads/campaigns/budget-metrics`,
				{ country_codes: countryCodes, budget }
			),
		} );

		yield recordGlaDataEvent( TYPES.RECEIVE_ADS_BUDGET_METRICS, data );

		return {
			type: TYPES.RECEIVE_ADS_BUDGET_METRICS,
			key: getAdsBudgetMetricsKey( countryCodes, budget ),
			data: adaptAdsBudgetMetrics( data ),
		};
	} catch ( response ) {
		// No related budget metrics.
		if ( response.status === 404 ) {
			// Records the case of data unavailability.
			yield recordGlaDataEvent(
				TYPES.RECEIVE_ADS_BUDGET_METRICS,
				response
			);
			return;
		}

		yield handleResponseError(
			response,
			__(
				'There was an error getting the budget metrics.',
				'google-listings-and-ads'
			)
		);
	}
}

getAdsBudgetMetrics.shouldInvalidate = ( action ) => {
	return action.type === TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS;
};

/**
 * Resolver for getting the GTIN Migration status.
 */
export function* getGtinMigrationStatus() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/gtin-migration`,
		} );

		yield receiveGtinMigrationStatus( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting the GTIN Migration Status.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Resolver to fetch the enhanced conversions status.
 */
export function* getEnableEnhancedConversions() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/settings`,
		} );

		yield receiveEnhancedConversionsStatus(
			Boolean( response.enhanced_conversions_enabled )
		);
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting the enhanced conversions status.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Resolver to fetch the full ads settings object.
 */
export function* getAdsSettings() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/settings`,
		} );

		yield receiveAdsSettings( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting the ads settings.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Resolver for getting the Price Benchmark summary.
 */
export function* getPriceBenchmarkSummary() {
	try {
		const { data } = yield fetchWithHeaders( {
			path: `${ API_NAMESPACE }/mc/price-benchmarks/summary`,
		} );

		return {
			type: TYPES.RECEIVE_PRICE_BENCHMARK_SUMMARY,
			data,
		};
	} catch ( response ) {
		// Intentionally silence the specific in case the the account is not authorized to view the price benchmark suggestions.
		if ( response.status === 403 ) {
			return {
				type: TYPES.RECEIVE_PRICE_BENCHMARK_SUMMARY,
				data: [],
			};
		}

		const bodyPromise = response?.json() || response?.text();
		const error = yield awaitPromise( bodyPromise );

		handleApiError(
			error,
			__(
				'There was an error getting the price benchmark summary.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Resolver for getting the Price Benchmark suggestions.
 *
 * @param {PriceBenchmarkQueryParams} args The query parameters for fetching price benchmark suggestions.
 */
export function* getPriceBenchmarkSuggestions( args ) {
	try {
		let path = `${ API_NAMESPACE }/mc/price-benchmarks`;
		if ( args.product_id ) {
			path = `${ path }/${ args.product_id }`;
		} else {
			path = addQueryArgs( path, args );
		}

		const { data } = yield fetchWithHeaders( {
			path,
		} );

		return {
			type: TYPES.RECEIVE_PRICE_BENCHMARK_SUGGESTIONS,
			data,
			args,
		};
	} catch ( response ) {
		// Intentionally silence the specific in case the the account is not authorized to view the price benchmark suggestions.
		if ( response.status === 403 ) {
			return {
				type: TYPES.RECEIVE_PRICE_BENCHMARK_SUGGESTIONS,
				data: [],
			};
		}

		const bodyPromise = response?.json() || response?.text();
		const error = yield awaitPromise( bodyPromise );

		handleApiError(
			error,
			__(
				'There was an error getting the price benchmark suggestions.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Resolver for getting the Ads recommendations.
 */
export function* getAdsRecommendations( types, campaign_id = null ) {
	try {
		const params = {
			types,
		};

		// campaign_id should be added to the query only if it's defined.
		if ( campaign_id ) {
			params.campaign_id = campaign_id;
		}

		const response = yield apiFetch( {
			path: addQueryArgs(
				`${ API_NAMESPACE }/ads/recommendations`,
				params
			),
		} );

		const key = [ campaign_id, ...types ].filter( Boolean );
		const typesKey = arrayToUnderscoreKey( key );
		const data = campaign_id
			? adaptRaiseAdsBudgetRecommendations( response )
			: response;
		yield receiveAdsRecommendations( data, typesKey );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting the Ads recommendations.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getCYOIncentives() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/incentives`,
		} );

		yield receiveCYOIncentives( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting the CYO incentives.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getYouTubeAccount() {
	yield fetchYouTubeAccount();
}

getYouTubeAccount.shouldInvalidate = ( action ) => {
	return (
		action.type === TYPES.DISCONNECT_ACCOUNTS_YOUTUBE &&
		action.invalidateRelatedState
	);
};

export function* getGoogleTagManagerAccount() {
	yield fetchGoogleTagManagerAccount();
}

getGoogleTagManagerAccount.shouldInvalidate = ( action ) => {
	return (
		action.type === TYPES.DISCONNECT_ACCOUNTS_GOOGLE_TAG_MANAGER &&
		action.invalidateRelatedState
	);
};

export function* getMarkets() {
	yield fetchMarkets();
}

export function* getAvailableLanguagesCurrencies() {
	try {
		const data = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/markets/languages-currencies`,
		} );
		return receiveMcLanguagesCurrencies( data );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading supported languages and currencies.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* getNotifications() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/notifications`,
		} );

		yield receiveNotifications( response.notifications ?? [] );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading notifications.',
				'google-listings-and-ads'
			)
		);
	}
}

getNotifications.shouldInvalidate = ( action ) => {
	return action.type === TYPES.DISMISS_NOTIFICATION;
};
