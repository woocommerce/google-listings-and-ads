/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TYPES from './action-types';
import { API_NAMESPACE } from './constants';
import { adaptAdsCampaign } from './adapters';

export function handleFetchError( error, message ) {
	const { createNotice } = dispatch( 'core/notices' );

	// Only show errors that are not authorization issues.
	if ( error.statusCode !== 401 ) {
		createNotice( 'error', message );
	}

	// eslint-disable-next-line no-console
	console.log( error );
}

/**
 * CountryCode
 *
 * @typedef {string} CountryCode Two-letter country code in ISO 3166-1 alpha-2 format. Example: 'US'.
 */

/**
 * Individual shipping rate.
 *
 * @typedef {Object} ShippingRate
 * @property {CountryCode} countryCode Destination country code.
 * @property {string} currency Currency of the price.
 * @property {number} rate Shipping price.
 */

/**
 * Campaign data.
 *
 * @typedef {Object} Campaign
 * @property {number} id Campaign ID.
 * @property {string} name Campaign name.
 * @property {'enabled'|'paused'} status Campaign is currently running or has been paused.
 * @property {number} amount Amount of daily budget for running ads.
 * @property {CountryCode} country The sales country of this campain.
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
 *
 * @return {Array<ShippingRate>} Array of individual shipping rates.
 */
export function* fetchShippingRates() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates`,
		} );

		const shippingRates = Object.values( response ).map( ( el ) => {
			return {
				countryCode: el.country_code,
				currency: el.currency,
				rate: Number( el.rate ),
			};
		} );

		return {
			type: TYPES.RECEIVE_SHIPPING_RATES,
			shippingRates,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading shipping rates.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Aggregated shipping rate.
 *
 * @typedef {Object} AggregatedShippingRate
 * @property {Array<CountryCode>} countries Array of destination country codes.
 * @property {string} currency Currency of the price.
 * @property {number} rate Shipping price.
 */

/**
 * Updates or inserts given aggregated shipping rate.
 *
 * @param {AggregatedShippingRate} shippingRate
 */
export function* upsertShippingRates( shippingRate ) {
	const { countryCodes, currency, rate } = shippingRate;

	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates/batch`,
			method: 'POST',
			data: {
				country_codes: countryCodes,
				currency,
				rate,
			},
		} );

		return {
			type: TYPES.UPSERT_SHIPPING_RATES,
			shippingRate,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to add / update shipping rates. Please try again later.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Deletes shipping rates associated with given country codes.
 *
 * @param {Array<CountryCode>} countryCodes
 */
export function* deleteShippingRates( countryCodes ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/rates/batch`,
			method: 'DELETE',
			data: {
				country_codes: countryCodes,
			},
		} );

		return {
			type: TYPES.DELETE_SHIPPING_RATES,
			countryCodes,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to delete shipping rates. Please try again later.',
				'google-listings-and-ads'
			)
		);
	}
}
/**
 * Individual shipping time.
 *
 * @typedef {Object} ShippingTime
 * @property {CountryCode} countryCode Destination country code.
 * @property {number} time Shipping time.
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
			};
		} );

		return {
			type: TYPES.RECEIVE_SHIPPING_TIMES,
			shippingTimes,
		};
	} catch ( error ) {
		yield handleFetchError(
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
 */

/**
 * Updates or inserts given aggregated shipping rate.
 *
 * @param {AggregatedShippingTime} shippingTime
 */
export function* upsertShippingTimes( shippingTime ) {
	const { countryCodes, time } = shippingTime;

	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/shipping/times/batch`,
			method: 'POST',
			data: {
				country_codes: countryCodes,
				time,
			},
		} );

		return {
			type: TYPES.UPSERT_SHIPPING_TIMES,
			shippingTime,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to add / update shipping times. Please try again later.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Deletes shipping times associated with given country codes.
 *
 * @param {Array<CountryCode>} countryCodes
 */
export function* deleteShippingTimes( countryCodes ) {
	try {
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
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to delete shipping times. Please try again later.',
				'google-listings-and-ads'
			)
		);
	}
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
		yield handleFetchError(
			error,
			__(
				'There was an error loading merchant center settings.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* saveSettings( settings ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/settings`,
			method: 'POST',
			data: settings,
		} );

		return {
			type: TYPES.SAVE_SETTINGS,
			settings,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error trying to save settings. Please try again later.',
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
		yield handleFetchError(
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
		yield handleFetchError(
			error,
			__(
				'There was an error loading Google account info.',
				'google-listings-and-ads'
			)
		);
	}
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

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_MC,
			account: response,
		};
	} catch ( error ) {
		yield handleFetchError(
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
		yield handleFetchError(
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

		return {
			type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS,
			account: response,
		};
	} catch ( error ) {
		yield handleFetchError(
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
		yield handleFetchError(
			error,
			__(
				'Unable to disconnect your Google account. Please try again later.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

export function* disconnectGoogleAdsAccount() {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/connection`,
			method: 'DELETE',
		} );

		return {
			type: TYPES.DISCONNECT_ACCOUNTS_GOOGLE_ADS,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'Unable to disconnect your Google Ads account. Please try again later.',
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
		yield handleFetchError(
			error,
			__(
				'Unable to disconnect all your accounts. Please try again later.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

export function* fetchGoogleAdsAccountBillingStatus() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/billing-status`,
		} );

		return receiveGoogleAdsAccountBillingStatus( response );
	} catch ( error ) {
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
			error,
			__(
				'Unable to update your Google Merchant Center contact information. Please try again later.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}

/**
 * Requests a phone verification code and returns a `verificationId` which is used for the next verification step.
 *
 * Important note:
 *   This action communicates with Google's production API.
 *   It will REALLY send the verification code to the phone number via SMS/phone call.
 *   When developing/testing, please make sure the passed number is your own or belongs to someone you know.
 *
 * @param {CountryCode} country The country code. Example: 'US'.
 * @param {string} phoneNumber The phone number string in E.164 format. Example: '+12133734253'.
 * @param {'SMS'|'PHONE_CALL'} method The verification method.
 * @return { { verificationId: string } } Verification id to be used for another call.
 * @throws { { display: string } } Will throws an identifiable error with the next step instruction for users.
 */
export function* requestPhoneVerificationCode( country, phoneNumber, method ) {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/phone-verification/request`,
			method: 'POST',
			data: {
				phone_region_code: country,
				phone_number: phoneNumber,
				verification_method: method,
			},
		} );

		return {
			verificationId: response.verification_id,
		};
	} catch ( error ) {
		if ( error.reason === 'rateLimitExceeded' ) {
			throw {
				...error,
				display: __(
					'Unable to initiate the verification code request. A maximum of five attempts to verify the same phone number every four hours. Please try again later.',
					'google-listings-and-ads'
				),
			};
		}

		yield handleFetchError(
			error,
			__(
				'Unable to request the phone verification code. Please try again later.',
				'google-listings-and-ads'
			)
		);
	}
}

/**
 * Verifies the phone number for users by passing the corresponding data used from the `requestPhoneVerificationCode` action.
 *
 * @param {string} verificationId The verification ID got from the `requestPhoneVerificationCode` action.
 * @param {string} code The six-digit verification code sent/call to the user's phone.
 * @param {'SMS'|'PHONE_CALL'} method The verification method. It should correspond with the verification ID got from the `requestPhoneVerificationCode` action.
 * @throws { { display: string } } Will throws an identifiable error with the next step instruction for users.
 */
export function* verifyPhoneNumber( verificationId, code, method ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/phone-verification/verify`,
			method: 'POST',
			data: {
				verification_id: verificationId,
				verification_code: code,
				verification_method: method,
			},
		} );

		return {
			type: TYPES.VERIFIED_MC_PHONE_NUMBER,
		};
	} catch ( error ) {
		const { reason, message = '' } = error;

		if ( reason === 'badRequest' ) {
			// Example of message format: '[verificationCode] Wrong code.'
			const [ , errorCode ] = message.match( /^\[(\w+)\]/ ) || [];
			const displayDict = {
				verificationCode: __(
					'Wrong verification code. Please try again.',
					'google-listings-and-ads'
				),
				verificationId: __(
					'The verification code has expired. Please initiate a new verification request by the resend button.',
					'google-listings-and-ads'
				),
			};

			if ( errorCode in displayDict ) {
				throw {
					...error,
					display: displayDict[ errorCode ],
				};
			}
		}

		yield handleFetchError(
			error,
			__(
				'Unable to verify your phone number. Please try again later.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchCountries() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/countries`,
		} );

		return {
			type: TYPES.RECEIVE_COUNTRIES,
			countries: response,
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
		yield handleFetchError(
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

export function receiveGoogleAdsAccountBillingStatus( billingStatus ) {
	return {
		type: TYPES.RECEIVE_ACCOUNTS_GOOGLE_ADS_BILLING_STATUS,
		billingStatus,
	};
}

export function* saveTargetAudience( targetAudience ) {
	try {
		yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/target_audience`,
			method: 'POST',
			data: targetAudience,
		} );

		return {
			type: TYPES.SAVE_TARGET_AUDIENCE,
			target_audience: targetAudience,
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error saving target audience data.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* fetchAdsCampaigns() {
	try {
		const campaigns = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/campaigns`,
		} );

		return {
			type: TYPES.RECEIVE_ADS_CAMPAIGNS,
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

/**
 * Create a new ads campaign.
 *
 * @param {number} amount Daily average cost of the paid ads campaign.
 * @param {Array<CountryCode>} countryCodes Country code of the paid ads campaign audience country. Example: 'US'.
 *
 * @throws { { message: string } } Will throw an error if the campaign creation fails.
 */
export function* createAdsCampaign( amount, countryCodes ) {
	try {
		const createdCampaign = yield apiFetch( {
			path: `${ API_NAMESPACE }/ads/campaigns`,
			method: 'POST',
			data: {
				amount,
				targeted_locations: countryCodes,
			},
		} );

		return {
			type: TYPES.CREATE_ADS_CAMPAIGN,
			createdCampaign: adaptAdsCampaign( createdCampaign ),
		};
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'Unable to create your paid ads campaign. Please try again later.',
				'google-listings-and-ads'
			)
		);

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
		yield handleFetchError(
			error,
			__(
				'Unable to update your paid ads campaign. Please try again later.',
				'google-listings-and-ads'
			)
		);

		throw error;
	}
}

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
		yield handleFetchError(
			error,
			__(
				'Unable to delete your paid ads campaign. Please try again later.',
				'google-listings-and-ads'
			)
		);
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

export function* fetchMCSetup() {
	try {
		const response = yield apiFetch( {
			path: `${ API_NAMESPACE }/mc/setup`,
		} );

		return receiveMCSetup( response );
	} catch ( error ) {
		yield handleFetchError(
			error,
			__(
				'There was an error loading your merchant center setup status.',
				'google-listings-and-ads'
			)
		);
	}
}

export function* receiveMCSetup( mcSetup ) {
	return {
		type: TYPES.RECEIVE_MC_SETUP,
		mcSetup,
	};
}

export function* receiveMCProductStatistics( mcProductStatistics ) {
	return {
		type: TYPES.RECEIVE_MC_PRODUCT_STATISTICS,
		mcProductStatistics,
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
		yield handleFetchError(
			error,
			__(
				'Unable to update the channel visibility of products. Please try again later.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}
