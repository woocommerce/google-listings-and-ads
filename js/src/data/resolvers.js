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
} from '.~/constants';
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';
import { getReportKey } from './utils';
import { adaptAdsCampaign } from './adapters';
import { fetchWithHeaders, awaitPromise } from './controls';

import {
	handleFetchError,
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
	receiveGoogleMCContactInformation,
	fetchTargetAudience,
	fetchMCSetup,
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
	receiveTour,
} from './actions';

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
		yield handleFetchError(
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
		yield handleFetchError(
			error,
			__(
				'There was an error loading Google Merchant Center contact information.',
				'google-listings-and-ads'
			)
		);
	}
}

getGoogleMCContactInformation.shouldInvalidate = ( action ) => {
	return action.type === TYPES.VERIFIED_MC_PHONE_NUMBER;
};

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
		yield handleFetchError(
			error,
			__(
				'There was an error loading supported country details.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Fetch policy info for checking merchant onboarding policy setting.
 */
export function* getPolicyCheck() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/policy_check`,
		} );

		return {
			type: TYPES.POLICY_CHECK,
			data: response,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading policy check details.',
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
		yield handleFetchError(
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

export function* getCampaignAssetGroups( campaignId ) {
	const endpoint = `${ API_NAMESPACE }/ads/campaigns/asset-groups`;
	const query = { campaign_id: campaignId };
	const path = addQueryArgs( endpoint, query );

	try {
		const assetGroups = yield apiFetch( { path } );

		return {
			type: TYPES.RECEIVE_CAMPAIGN_ASSET_GROUPS,
			campaignId,
			assetGroups,
		};
	} catch ( error ) {
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
			error,
			__(
				'There was an error getting the store categories.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Resolver for getting the tour.
 *
 * @param {string} tourId The tour to get
 */
export function* getTour( tourId ) {
	try {
		const { data } = yield fetchWithHeaders( {
			path: `${ API_NAMESPACE }/tours/${ tourId }`,
		} );

		yield receiveTour( data );
	} catch ( response ) {
		// Intentionally silence the specific error since the tour API will respond with
		// a 404 error if the querying tour ID doesn't exist.
		if ( response.status === 404 ) {
			return;
		}

		const bodyPromise = response?.json() || response?.text();
		const error = yield awaitPromise( bodyPromise );

		yield handleFetchError(
			error,
			__(
				'There was an error getting the tour.',
				'google-listings-and-ads'
			)
		);
	}
}
