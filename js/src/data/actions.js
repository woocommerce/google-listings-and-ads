/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import {
	API_NAMESPACE,
	REQUEST_ACTIONS,
	EMPTY_ASSET_ENTITY_GROUP,
	STORE_KEY,
} from './constants';
import { EU_POLITICAL_ADVERTISING_DECLARATION_REQUIRED_ERROR_CODE } from '~/constants';
import { handleApiError } from '~/utils/handleError';
import { adaptAdsCampaign, adaptGenAIAssets } from './adapters';
import { isWCIos, isWCAndroid } from '~/utils/isMobileApp';
import { convertKeysFromSnakeCaseToCamelCase } from './utils';

/**
 * @typedef {import('~/data/types.js').AssetEntityGroupUpdateBody} AssetEntityGroupUpdateBody
 * @typedef {import('~/data/types.js').AdsIncentiveCredits} AdsIncentiveCredits
 * @typedef {import('./selectors').Tour} Tour
 * @typedef {import('./selectors').PriceBenchmarkQueryParams} PriceBenchmarkQueryParams
 */

/**
 * CountryCode
 *
 * @typedef {string} CountryCode Two-letter country code in ISO 3166-1 alpha-2 format. Example: 'US'.
 */

/**
 * Individual shipping rate.
 *
 * @typedef {Object} ShippingRate
 * @property {string} id id.
 * @property {CountryCode} country Destination country code.
 * @property {string} currency Currency of the price.
 * @property {number} rate Shipping price.
 * @property {Object} options Options, such as `free_shipping_threshold`.
 */

/**
 * Error object returned from the API.
 *
 * @typedef {Object} ApiError
 * @property {string} code Error code.
 * @property {string} message Error message.
 */

/**
 * Campaign data.
 *
 * @typedef {Object} Campaign
 * @property {number} id Campaign ID.
 * @property {string} name Campaign name.
 * @property {'enabled'|'paused'|'removed'} status Campaign is currently running, has been paused or removed.
 * @property {number} amount Amount of daily budget for running ads.
 * @property {CountryCode} country The sales country of this campaign.
 *   Please note that this is a targeting country for advertising,
 *   but it is NOT set up via the location-based method.
 * @property {Array<CountryCode>} targeted_locations The location-based targeting countries associated with this campaign for advertising.
 *   Please note that only multi-country campaigns will have at least one country in this array.
 *   For single-country campaigns, it will an empty array.
 * @property {boolean} allowMultiple Indicate whether this campaign allows multi-country targeting.
 *   This can be used to distinguish this campaign is multi-country or single-country targeting.
 * @property {Array<CountryCode>} displayCountries Campaign's targeting countries used to present on the UI without making merchants feel ambiguous.
 *   Please refer to the descriptions of `country`, `targeted_locations` and `allowMultiple` for more context about this property.
 */

/**
 * Account status data. Indicates the current status for the Google MC account.
 *
 * @typedef {Object} AccountStatus
 * @property {string} status Derived account review status.
 * @property {Array} issues Titles of the account issues blocking approval.
 * @property {Object|null} reviewAction The account-review action (in-app or redirect), or null when none is available.
 */

/**
 * @typedef {Object} TargetAudienceData
 * @property {string} locale The locale for the site. Example: 'en_US'.
 * @property {string} language The language to use for product listings. Example: 'English'.
 * @property {string} location Type of location, There are two possible values: 'selected' countries or 'all' countries.
 * @property {Array<CountryCode>} countries Array of audience countries.
 */

/**
 * Settings Data
 *
 * @typedef {Object} SettingsData
 * @property {boolean} [offer_free_shipping] Whether if the merchant offers free shipping.
 * @property {'automatic'|'flat'|'manual'} [shipping_rate] Type of the shipping rate.
 * @property {'flat'|'manual'} [shipping_time] Type of the shipping time.
 * @property {string|null} [tax_rate] Type of tax rate, There are two possible values if US is selected: 'destination' and 'manual' otherwise will be null.
 */

/**
 * @typedef {Object} ProductStatisticsDetails
 * @property {number} active Number of active products.
 * @property {number} expiring Number of expiring products.
 * @property {number} pending Number of pending products.
 * @property {number} disapproved Number of disapproved products.
 * @property {number} not_synced Number of not synced products.
 */

/**
 * Product status statistics on Google Merchant Center
 *
 * @typedef {Object} ProductStatistics
 * @property {number} scheduled_sync Number of scheduled jobs which will sync products to Google.
 * @property {number} timestamp Timestamp reflecting when the product status statistics were last generated.
 * @property {boolean} loading Whether the product status statistics are being loaded.
 * @property {string | null} error In case of error, it will contain the error message.
 * @property {ProductStatisticsDetails | null } statistics Statistics information of product status on Google Merchant Center or null if the stats are loading.
 */

/**
 * A market's shipping configuration.
 *
 * @typedef {Object} MarketShipping
 * @property {?string} rate_type The global shipping rate method type.
 * @property {?string} time_type The global shipping time method type.
 * @property {?number} flat_rate Flat shipping rate amount, or null when not configured for this market's country.
 * @property {?number} free_shipping_threshold Order amount above which shipping is free, or null when not configured.
 * @property {?number} flat_time Minimum shipping days, or null when not configured.
 * @property {?number} flat_max_time Maximum shipping days, or null when not configured.
 */

/**
 * @typedef {Object} Market
 * @property {string} id The market ID.
 * @property {string} label The market label.
 * @property {Array<CountryCode>} countries Array of audience countries.
 * @property {string[]} language Language codes in ISO 639-1 format. Example: ['en'].
 * @property {string[]} currency Currency codes in ISO 4217 format. Example: ['USD'].
 * @property {'automatic'|'flat'|'manual'} shipping_rate Shipping rate type.
 * @property {'flat'|'manual'} shipping_time Shipping time type.
 * @property {MarketShipping} shipping This market's shipping configuration.
 */

/**
 * Hydrate the prefetched data to store.
 *
 * @param {Object} data The prefetched data.
 * @param {string} [data.version] The version of this extension.
 * @param {number | null} [data.mcId] The ID of the connected Google Merchant Center account. Set to null if not yet connected.
 * @param {number | null} [data.adsId] The ID of the connected Google Ads account. Set to null if not yet connected.
 * @return {Object} Action object to hydrate the prefetched data.
 */
export function hydratePrefetchedData( data ) {
	return {
		type: TYPES.HYDRATE_PREFETCHED_DATA,
		data,
	};
}

/**
 *
 * @return {Array<ShippingRate>} Array of individual shipping rates.
 */
export function* fetchShippingRates() {
	try {
		const data = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates`,
		} );

		const shippingRates = data.map( ( el ) => {
			return {
				...el,
				rate: Number( el.rate ),
			};
		} );

		return {
			type: TYPES.RECEIVE_SHIPPING_RATES,
			shippingRates,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading shipping rates.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Upsert shipping rates.
 *
 * @param {Array<ShippingRate>} shippingRates Shipping rates to be upserted.
 * @return {Object} Action object to update shipping rates.
 * @throws Will throw an error if the request failed.
 */
export function* upsertShippingRates( shippingRates ) {
	const data = yield apiFetch( {
		path: `${ API_NAMESPACE }/mc/shipping/rates/batch`,
		method: 'POST',
		data: {
			rates: shippingRates,
		},
	} );

	const successShippingRates = data.success.map( ( el ) => {
		return {
			...el.rate,
			rate: Number( el.rate.rate ),
		};
	} );

	return {
		type: TYPES.UPSERT_SHIPPING_RATES,
		shippingRates: successShippingRates,
	};
}

/**
 * Delete shipping rates.
 *
 * @param {Array<string>} ids IDs of shipping rates to be deleted.
 * @return {Object} Action object to delete shipping rates.
 * @throws Will throw an error if the request failed.
 */
export function* deleteShippingRates( ids ) {
	yield apiFetch( {
		path: `${ API_NAMESPACE }/mc/shipping/rates/batch`,
		method: 'DELETE',
		data: {
			ids,
		},
	} );

	return {
		type: TYPES.DELETE_SHIPPING_RATES,
		ids,
	};
}

/**
 * Individual shipping time.
 *
 * @typedef {Object} ShippingTime
 * @property {CountryCode} countryCode Destination country code.
 * @property {number} time Shipping time.
 * @property {number} maxTime Max shipping time.
 */

/**
 *
 * @return {Array<ShippingTime>} Array of individual shipping times.
 */
export function* fetchShippingTimes() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/times`,
		} );

		const shippingTimes = Object.values( response ).map( ( el ) => {
			return {
				countryCode: el.country_code,
				time: Number( el.time ),
				maxTime: Number( el.max_time ),
			};
		} );

		return {
			type: TYPES.RECEIVE_SHIPPING_TIMES,
			shippingTimes,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading shipping times.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Aggregated shipping time.
 *
 * @typedef {Object} AggregatedShippingTime
 * @property {Array<CountryCode>} countries Array of destination country codes.
 * @property {number} time Shipping time.
 * @property {number} maxTime Max shipping time.
 */

/**
 * Updates or inserts given aggregated shipping rate.
 *
 * @param {AggregatedShippingTime} shippingTime
 * @throws Will throw an error if the request failed.
 */
export function* upsertShippingTimes( shippingTime ) {
	const { countries, time, maxTime } = shippingTime;

	yield apiFetch( {
		path: `${ API_NAMESPACE }/mc/shipping/times/batch`,
		method: 'POST',
		data: {
			country_codes: countries,
			time,
			max_time: maxTime,
		},
	} );

	return {
		type: TYPES.UPSERT_SHIPPING_TIMES,
		shippingTime,
	};
}

/**
 * Deletes shipping times associated with given country codes.
 *
 * @param {Array<CountryCode>} countryCodes
 * @throws Will throw an error if the request failed.
 */
export function* deleteShippingTimes( countryCodes ) {
	yield apiFetch( {
		path: `${ API_NAMESPACE }/mc/shipping/times/batch`,
		method: 'DELETE',
		data: {
			country_codes: countryCodes,
		},
	} );

	return {
		type: TYPES.DELETE_SHIPPING_TIMES,
		countryCodes,
	};
}

export function* fetchSettings() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/settings`,
		} );

		return {
			type: TYPES.RECEIVE_SETTINGS,
			settings: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading merchant center settings.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Save the plugin settings.
 *
 * @param {SettingsData} settings Plugin settings
 * @return {Object} Action object to save the plugin settings.
 */
export function* saveSettings( settings ) {
	yield apiFetch( {
		path: `${ API_NAMESPACE }/mc/settings`,
		method: 'POST',
		data: settings,
	} );

	// The markets and target audience are derived server-side from settings
	// such as the shipping rate method, so they can no longer be trusted.
	yield controls.dispatch(
		STORE_KEY,
		'invalidateResolution',
		'getMarkets',
		[]
	);
	yield controls.dispatch(
		STORE_KEY,
		'invalidateResolution',
		'getTargetAudience',
		[]
	);

	return {
		type: TYPES.SAVE_SETTINGS,
		settings,
	};
}

/**
 * Sync the shipping and tax rate settings to Google Merchant Center.
 *
 * @throws Will throw an error if the request failed.
 */
export function* syncSettings() {
	yield apiFetch( {
		path: `${ API_NAMESPACE }/mc/settings/sync`,
		method: 'POST',
	} );
}

/**
 * Mark onboarding as complete for service-based merchants.
 *
 * @throws Will throw an error if the request failed.
 */
export function* completeOnboarding() {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/google/onboarding/complete`,
			method: 'POST',
		} );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error completing onboarding.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchJetpackAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/jetpack/connected`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_JETPACK,
			account: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading Jetpack account info.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchGoogleAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/google/connected`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE,
			account: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading Google account info.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Fetch the URL for the user to grant Google's WPCOM app access to WooCommerce product data etc.
 *
 * @param {'settings'|'setup-mc'} nextPageName The name of the next page to redirect to after authorization.
 * @return {string} The URL for the user to continue authorization.
 * @throws Will throw an error if the request failed.
 */
export function* fetchWPComAppAuthorizationUrl( nextPageName ) {
	const query = { next_page_name: nextPageName };
	const path = addQueryArgs( `${ API_NAMESPACE }/rest-api/authorize`, query );

	const response = yield apiFetch( { path } );
	return response.auth_url;
}

export function receiveGoogleAccountAccess( data ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ACCESS,
		data,
	};
}

export function* fetchGoogleMCAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/connection`,
		} );

		const mcId = response.id || null;
		yield hydratePrefetchedData( { mcId } );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC,
			account: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading Google Merchant Center account info.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchExistingGoogleMCAccounts() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/accounts`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC_EXISTING,
			accounts: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting your Google Merchant Center accounts.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchGoogleAdsAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/connection`,
		} );

		const adsId = response.id || null;
		yield hydratePrefetchedData( { adsId } );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS,
			account: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading Google Ads account info.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* disconnectGoogleAccount() {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/google/connect`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'Unable to disconnect your Google account.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

/**
 * Disconnect the connected Google Ads account.
 *
 * @param {boolean} [invalidateRelatedState=false] Whether to invalidate related state in wp-data store.
 * @throws Will throw an error if the request failed.
 */
export function* disconnectGoogleAdsAccount( invalidateRelatedState = false ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/connection`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS,
			invalidateRelatedState,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'Unable to disconnect your Google Ads account.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

export function* disconnectAllAccounts() {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/connections`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DISCONNECT_ACCOUNTS_ALL,
		};
	} catch ( error ) {
		// Skip any error related to revoking WPCOM token.
		if ( error.errors[ `${ API_NAMESPACE }/rest-api/authorize` ] ) {
			return {
				type: TYPES.DISCONNECT_ACCOUNTS_ALL,
			};
		}

		handleApiError(
			error,
			__(
				'Unable to disconnect all your accounts.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

export function receiveGoogleAdsAccountBillingStatus( billingStatus ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_BILLING_STATUS,
		billingStatus,
	};
}

export function* fetchGoogleAdsAccountBillingStatus() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/billing-status`,
		} );

		return receiveGoogleAdsAccountBillingStatus( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting the billing status of your Google Ads account.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchExistingGoogleAdsAccounts() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/accounts`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_EXISTING,
			accounts: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting your Google Ads accounts.',
				'google-listings-and-ads'
			)
		);
	}
}

export function receiveGoogleMCContactInformation( data ) {
	return {
		type: TYPES.RECEIVE_MC_CONTACT_INFORMATION,
		data,
	};
}

/**
 * Update the contact information to user's account of Google Merchant Center.
 * It will update the store address of WooCommerce Settings to Google Merchant Center if they are different.
 */
export function* updateGoogleMCContactInformation() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/contact-information`,
			method: 'POST',
		} );

		yield receiveGoogleMCContactInformation( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'Unable to update your Google Merchant Center contact information.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

export function* fetchTargetAudience() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/target_audience`,
		} );

		return {
			type: TYPES.RECEIVE_TARGET_AUDIENCE,
			target_audience: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading target audience.',
				'google-listings-and-ads'
			)
		);
	}
}

export function receiveMCAccount( account ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC,
		account,
	};
}

export function receiveAdsAccount( account ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS,
		account,
	};
}

/**
 * Save the target audience countries.
 *
 * @param {TargetAudienceData} targetAudience audience countries
 * @return {Object} Action object to save target audience.
 */
export function* saveTargetAudience( targetAudience ) {
	yield apiFetch( {
		path: `${ API_NAMESPACE }/mc/target_audience`,
		method: 'POST',
		data: targetAudience,
	} );

	return {
		type: TYPES.SAVE_TARGET_AUDIENCE,
		target_audience: targetAudience,
	};
}

/**
 * Fetch the incentive credits of Google Ads.
 *
 * @return {Promise<AdsIncentiveCredits>} The incentive credits of Google Ads.
 * @throws Will throw an error if the request failed.
 */
export function* fetchAdsIncentiveCredits() {
	const path = `${ API_NAMESPACE }/ads/incentive-credits`;
	const response = yield apiFetch( { path } );
	return convertKeysFromSnakeCaseToCamelCase( response );
}

/**
 * Create a new ads campaign.
 *
 * @param {number} amount Daily average cost of the paid ads campaign.
 * @param {Array<CountryCode>} countryCodes Country code of the paid ads campaign audience country. Example: 'US'.
 * @param {boolean} [hasConfirmedEuPoliticalContent=false] Whether the user has confirmed that the ads campaign contains EU political content.
 *
 * @throws { { message: string } } Will throw an error if the campaign creation fails.
 */
export function* createAdsCampaign(
	amount,
	countryCodes,
	hasConfirmedEuPoliticalContent = false
) {
	let label = 'wc-web';

	if ( isWCIos() ) {
		label = 'wc-ios';
	} else if ( isWCAndroid() ) {
		label = 'wc-android';
	}

	try {
		const createdCampaign = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/campaigns`,
			method: 'POST',
			data: {
				amount,
				targeted_locations: countryCodes,
				eu_political_advertising_confirmation:
					hasConfirmedEuPoliticalContent,
				label,
			},
		} );

		return {
			type: TYPES.CREATE_ADS_CAMPAIGN,
			createdCampaign: adaptAdsCampaign( createdCampaign ),
		};
	} catch ( error ) {
		if ( error.code !== 'eu_political_advertising_declaration_required' ) {
			handleApiError( error );
		}

		throw error;
	}
}

/**
 * Create a new ads campaign with assets.
 *
 * @param {number} amount Daily average cost of the paid ads campaign.
 * @param {Array<CountryCode>} countryCodes Country code of the paid ads campaign audience country. Example: 'US'.
 * @param {AssetEntityGroupUpdateBody} assets Assets of the ads campaign.
 * @param {boolean} [hasConfirmedEuPoliticalContent=false] Whether the user has confirmed that the ads campaign contains EU political content.
 *
 * @throws { { message: string } } Will throw an error if the campaign creation fails.
 */
export function* createAdsWithAssetsCampaign(
	amount,
	countryCodes,
	assets,
	hasConfirmedEuPoliticalContent = false
) {
	let label = 'wc-web';

	if ( isWCIos() ) {
		label = 'wc-ios';
	} else if ( isWCAndroid() ) {
		label = 'wc-android';
	}

	try {
		const createdCampaign = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/campaigns`,
			method: 'POST',
			data: {
				amount,
				targeted_locations: countryCodes,
				eu_political_advertising_confirmation:
					hasConfirmedEuPoliticalContent,
				label,
				final_url: assets.final_url,
				assets: assets.assets,
				path1: assets.path1,
				path2: assets.path2,
			},
		} );

		return {
			type: TYPES.CREATE_ADS_CAMPAIGN,
			createdCampaign: adaptAdsCampaign( createdCampaign ),
		};
	} catch ( error ) {
		handleApiError( error );

		throw error;
	}
}

/**
 * Update the given data properties to an ads campaign.
 *
 * @param {number} id The ID of the ads campaign to be updated.
 * @param {Object} data The properties of the ads campaign to be updated.
 *   The valid properties are 'name', 'status', and 'amount'.
 *
 * @throws { { message: string } } Will throw an error if the campaign update fails.
 */
export function* updateAdsCampaign( id, data ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/campaigns/${ id }`,
			method: 'PATCH',
			data,
		} );

		return {
			type: TYPES.UPDATE_ADS_CAMPAIGN,
			id,
			data,
		};
	} catch ( error ) {
		if (
			error?.code !==
			EU_POLITICAL_ADVERTISING_DECLARATION_REQUIRED_ERROR_CODE
		) {
			handleApiError( error );
		}

		throw error;
	}
}

export function receiveEnhancedConversionsStatus( status ) {
	return {
		type: TYPES.RECEIVE_ADS_ENHANCED_CONVERSIONS,
		status,
	};
}

export function receiveAdsSettings( settings ) {
	return {
		type: TYPES.RECEIVE_ADS_SETTINGS,
		settings,
	};
}

/**
 * Update the enhanced conversions status.
 *
 * @param {boolean} status The status of the enhanced conversions.
 * @return {Object} Action object to update the enhanced conversions status.
 */
export function* updateEnhancedConversionsStatus( status ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/settings`,
			method: 'POST',
			data: {
				enhanced_conversions_enabled: status,
			},
		} );

		return receiveEnhancedConversionsStatus( status );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error updating the enhanced conversions status.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

/**
 * Delete an ads campaign by ID.
 *
 * @param {number} id The ID of the ads campaign to be deleted.
 * @throws Will throw an error if the request failed.
 */
export function* deleteAdsCampaign( id ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/campaigns/${ id }`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DELETE_ADS_CAMPAIGN,
			id,
		};
	} catch ( error ) {
		handleApiError( error );

		throw error;
	}
}

/**
 * Creates an asset group under the given Google Ads campaign.
 *
 * @param {number} campaignId The ID of the campaign to be created the asset group.
 * @yield {Object} The wp-data action with data payload.
 * @throws { { message: string } } Will throw an error if the creation fails.
 */
export function* createCampaignAssetGroup( campaignId ) {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/campaigns/asset-groups`,
			method: 'POST',
			data: { campaign_id: campaignId },
		} );

		return {
			type: TYPES.CREATE_CAMPAIGN_ASSET_GROUP,
			campaignId,
			assetGroup: {
				...EMPTY_ASSET_ENTITY_GROUP,
				id: response.id,
			},
		};
	} catch ( error ) {
		const fallbackMessage = __(
			'There was an error creating the assets of the campaign.',
			'google-listings-and-ads'
		);

		handleApiError( error, null, fallbackMessage );
		throw error;
	}
}

/**
 * Updates the asset group of the Google Ads campaign.
 *
 * @param {number} assetGroupId The ID of the asset group to be updated.
 * @param {AssetEntityGroupUpdateBody} body The body of the updating request.
 * @yield {Object} The wp-data action with data payload.
 * @throws { { message: string } } Will throw an error if the update fails.
 */
export function* updateCampaignAssetGroup( assetGroupId, body ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/campaigns/asset-groups/${ assetGroupId }`,
			method: 'PUT',
			data: body,
		} );

		return {
			type: TYPES.UPDATE_CAMPAIGN_ASSET_GROUP,
			assetGroupId,
		};
	} catch ( error ) {
		const fallbackMessage = __(
			'There was an error updating the assets of the campaign.',
			'google-listings-and-ads'
		);

		handleApiError( error, null, fallbackMessage );
		throw error;
	}
}

export function receiveReport( reportKey, data ) {
	return {
		type: TYPES.RECEIVE_REPORT,
		reportKey,
		data,
	};
}

export function* receiveMCSetup( mcSetup ) {
	return {
		type: TYPES.RECEIVE_MC_SETUP,
		mcSetup,
	};
}

export function* fetchMCSetup() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/setup`,
		} );

		return receiveMCSetup( response );
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading your merchant center setup status.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Creates a wp-data action with data payload to be dispatched the received
 * MC product statistics to wp-data store.
 *
 * @param {ProductStatistics} mcProductStatistics The received MC product statistics data.
 */
export function* receiveMCProductStatistics( mcProductStatistics ) {
	return {
		type: TYPES.RECEIVE_MC_PRODUCT_STATISTICS,
		mcProductStatistics,
	};
}

export function* receiveMCReviewRequest( mcReviewRequest ) {
	return {
		type: TYPES.RECEIVE_MC_REVIEW_REQUEST,
		mcReviewRequest,
	};
}

export function* receiveMCIssues( query, data ) {
	return {
		type: TYPES.RECEIVE_MC_ISSUES,
		query,
		data,
	};
}

export function* receiveMCProductFeed( query, data ) {
	return {
		type: TYPES.RECEIVE_MC_PRODUCT_FEED,
		query,
		data,
	};
}

/**
 * Update the channel visibility of products by product IDs.
 *
 * @param {Array<number>} ids Product IDs to be updated.
 * @param {boolean} visible Visibility of products to be updated.
 *                          `true` is "Sync and show" and `false` is "Don't sync and show".
 */
export function* updateMCProductVisibility( ids, visible ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/product-visibility`,
			method: 'POST',
			data: {
				ids,
				visible,
			},
		} );

		return {
			type: TYPES.UPDATE_MC_PRODUCTS_VISIBILITY,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'Unable to update the channel visibility of products.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

/**
 * Request a new review for the connected account
 */
export function* sendMCReviewRequest() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/review`,
			method: 'POST',
		} );

		return yield receiveMCReviewRequest( response );
	} catch ( error ) {
		// A 403 here means the account has an in-app review action rendered but is not on
		// Google's triggeraction allowlist; it currently surfaces as a generic error notice.
		handleApiError( error );
		throw error;
	}
}

/**
 * Receive Mapping Attributes action
 *
 * @param {Array} attributes The attributes to update in the state.
 */
export function* receiveMappingAttributes( attributes ) {
	return {
		type: TYPES.RECEIVE_MAPPING_ATTRIBUTES,
		attributes,
	};
}

/**
 * Receive Mapping Sources action
 *
 * @param {Array} sources The sources to update in the state.
 * @param {string} attributeKey The key for the attribute we are querying the sources.
 */
export function* receiveMappingSources( sources, attributeKey ) {
	return {
		type: TYPES.RECEIVE_MAPPING_SOURCES,
		sources,
		attributeKey,
	};
}

/**
 * Receive Mapping Rules action
 *
 * @param {Array} rules The rules to update in the state.
 * @param {Object} pagination Containing parameters like page or per_page.
 */
export function* receiveMappingRules( rules, pagination ) {
	return {
		type: TYPES.RECEIVE_MAPPING_RULES,
		rules,
		pagination,
	};
}

/**
 * Creates a Mapping Rule action
 *
 * @param {Object} rule The rule to create in the state.
 */
export function* createMappingRule( rule ) {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/mapping/rules`,
			method: 'POST',
			data: rule,
		} );

		return {
			type: TYPES.UPSERT_MAPPING_RULE,
			rule: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error creating the rule.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

/**
 * Updates a Mapping Rule action
 *
 * @param {Object} rule The rule to update in the state.
 */
export function* updateMappingRule( rule ) {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/mapping/rules/${ rule.id }`,
			method: REQUEST_ACTIONS.POST,
			data: rule,
		} );

		return {
			type: TYPES.UPSERT_MAPPING_RULE,
			rule: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error updating the rule.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

/**
 * Delete Mapping Rule action
 *
 * @param {Object} rule The rule to be deleted.
 */
export function* deleteMappingRule( rule ) {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/mapping/rules/${ rule.id }`,
			method: REQUEST_ACTIONS.DELETE,
			data: rule,
		} );

		return {
			type: TYPES.DELETE_MAPPING_RULE,
			rule: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error deleting the rule.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

/**
 * Action to receive the Store categories.
 *
 * @param {Array} storeCategories List of categories
 */
export function* receiveStoreCategories( storeCategories ) {
	return {
		type: TYPES.RECEIVE_STORE_CATEGORIES,
		storeCategories,
	};
}

/**
 * Action to receive the tours.
 *
 * @param {Object.<string, Tour>} tours The tours to receive.
 */
export function* receiveTours( tours ) {
	return {
		type: TYPES.RECEIVE_TOURS,
		tours,
	};
}

/**
 * Updates/Inserts a Tour action
 *
 * @param {Tour} tour The tour to update in the state.
 * @param {boolean} [upsertingClientStoreFirst=false] Whether updating to the wp-data store first then the API.
 */
export function* upsertTour( tour, upsertingClientStoreFirst = false ) {
	const actions = [
		apiFetch( {
			path: `${ API_NAMESPACE }/tours`,
			method: REQUEST_ACTIONS.POST,
			data: tour,
		} ),
	];

	const updatingStoreAction = {
		type: TYPES.UPSERT_TOUR,
		tour,
	};

	// Explicitly compare to avoid miss-passing in a truthy value.
	if ( upsertingClientStoreFirst === true ) {
		actions.unshift( updatingStoreAction );
	} else {
		actions.push( updatingStoreAction );
	}

	try {
		for ( const action of actions ) {
			yield action;
		}
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error updating the tour.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Action to receive the GTIN Migration status.
 *
 * @param {string} status GTIN Migration status
 */
export function* receiveGtinMigrationStatus( status ) {
	return {
		type: TYPES.RECEIVE_GTIN_MIGRATION_STATUS,
		data: status,
	};
}

export function* fetchGoogleAdsAccountStatus() {
	try {
		const data = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/account-status`,
		} );

		return {
			type: TYPES.RECEIVE_GOOGLE_ADS_ACCOUNT_STATUS,
			data,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error getting the status of your Google Ads account.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* receivePriceBenchmarkSuggestionsProductPrice(
	productId,
	productPrice
) {
	return {
		type: TYPES.RECEIVE_PRICE_BENCHMARK_SUGGESTIONS_PRODUCT_PRICE,
		data: {
			productId,
			productPrice,
		},
	};
}

export function* receiveAdsRecommendations(
	recommendations,
	recommendationTypes
) {
	return {
		type: TYPES.RECEIVE_ADS_RECOMMENDATIONS,
		recommendations,
		recommendationTypes,
	};
}

/**
 * Action containing detailed error information.
 *
 * @param {string} slot - Unique key identifying the error (e.g., field name or error code).
 * @param {ApiError|null} error - The original error object or additional error details.
 * @return {{type: string, slot: string, error: ApiError|null}} Redux action with type `TYPES.RECEIVE_DETAILED_ERROR`.
 */
export function* receiveDetailedError( slot, error ) {
	return {
		type: TYPES.RECEIVE_DETAILED_ERROR,
		slot,
		error,
	};
}

/**
 * Clears error information for specific error slots.
 *
 * @param {Array<string>} slots - Array of unique keys identifying the errors to be cleared.
 * @return {{type: string, slots: Array<string>}} Redux action with type `TYPES.CLEAR_DETAILED_ERROR_BY_SLOT`.
 */
export function* clearDetailedErrorBySlots( slots ) {
	return {
		type: TYPES.CLEAR_DETAILED_ERROR_BY_SLOT,
		slots,
	};
}

export function receiveCYOIncentives( cyoIncentives ) {
	return {
		type: TYPES.RECEIVE_CYO_INCENTIVES,
		cyoIncentives,
	};
}

export function* receiveGenAIMediaAssets( url, data, assetType ) {
	if ( ! data?.items ) {
		return {
			type: TYPES.RECEIVE_GEN_AI_MEDIA_ASSETS,
			url,
			assetType,
			data: {},
		};
	}

	return {
		type: TYPES.RECEIVE_GEN_AI_MEDIA_ASSETS,
		url,
		assetType,
		data: adaptGenAIAssets( data.items, 'temporary_image_url', assetType ),
	};
}

export function* receiveGenAITextAssets( url, data, assetType ) {
	if ( ! data?.items ) {
		return {
			type: TYPES.RECEIVE_GEN_AI_TEXT_ASSETS,
			url,
			assetType,
			data: {},
		};
	}

	return {
		type: TYPES.RECEIVE_GEN_AI_TEXT_ASSETS,
		url,
		assetType,
		data: adaptGenAIAssets( data.items, 'text', assetType ),
	};
}

export function* fetchYouTubeAccount() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/youtube/connection`,
		} );

		return {
			type: TYPES.RECEIVE_ACCOUNTS_YOUTUBE,
			account: response,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error loading YouTube account info.',
				'google-listings-and-ads'
			)
		);

		// Set a default disconnected state to ensure loading state resolves
		return {
			type: TYPES.RECEIVE_ACCOUNTS_YOUTUBE,
			account: {
				status: 'disconnected',
				channel: [],
			},
		};
	}
}

/**
 * Disconnect the connected YouTube account.
 *
 * @throws Will throw an error if the request failed.
 */
export function* disconnectYouTubeAccount() {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/youtube/connection`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DISCONNECT_ACCOUNTS_YOUTUBE,
			invalidateRelatedState: true,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'Unable to disconnect your YouTube account.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

/**
 * Fetch the list of markets.
 *
 * @return {Object} Action object to receive the markets.
 * @throws Will throw an error if the request failed.
 */
export function* fetchMarkets() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/markets`,
		} );

		return { type: TYPES.RECEIVE_MARKETS, markets: response };
	} catch ( error ) {
		handleApiError( error );
	}
}

/**
 * Create a new market.
 *
 * @param {Market} args The market data to create.
 * @return {Object} Action object to receive the markets after creation.
 * @throws Will throw an error if the request failed.
 */
export function* createMarket( args ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/markets`,
			method: 'POST',
			data: args,
		} );
		return yield fetchMarkets();
	} catch ( error ) {
		handleApiError( error );
		throw error;
	}
}

/**
 * Update an existing market.
 *
 * @param {string} id The ID of the market to update.
 * @param {Partial<Market>} data The market fields to update (all fields optional).
 * @return {Object} Action object to receive the markets after update.
 * @throws Will throw an error if the request failed.
 */
export function* updateMarket( id, data ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/markets/${ id }`,
			method: 'PUT',
			data,
		} );
		return yield fetchMarkets();
	} catch ( error ) {
		handleApiError( error );
		throw error;
	}
}

/**
 * Delete a market.
 *
 * @param {string|number} id The ID of the market to delete.
 * @return {Object} Action object to receive the markets after deletion.
 * @throws Will throw an error if the request failed.
 */
export function* deleteMarket( id ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/markets/${ id }`,
			method: 'DELETE',
		} );
		return yield fetchMarkets();
	} catch ( error ) {
		handleApiError( error );
		throw error;
	}
}

/**
 * Returns an action object to receive supported languages and currencies data.
 *
 * @param {Object}        data           Response from the languages-currencies endpoint.
 * @param {Array<Object>} data.languages Available languages.
 * @param {Array<Object>} data.currencies Available currencies.
 * @return {Object} Action object.
 */
export function receiveMcLanguagesCurrencies( data ) {
	return { type: TYPES.RECEIVE_MC_LANGUAGES_CURRENCIES, data };
}

/**
 * @param {Array} notifications
 * @return {Object} Action object.
 */
export function receiveNotifications( notifications ) {
	return {
		type: TYPES.RECEIVE_NOTIFICATIONS,
		notifications,
	};
}

/**
 * Dismiss a notification by ID.
 *
 * @param {string} id Notification ID.
 * @throws Will throw an error if the request failed.
 */
export function* dismissNotification( id ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/notifications/${ id }`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DISMISS_NOTIFICATION,
			id,
		};
	} catch ( error ) {
		handleApiError(
			error,
			__(
				'There was an error dismissing the notification.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}
