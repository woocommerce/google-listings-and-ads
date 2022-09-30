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
import { API_NAMESPACE, REQUEST_ACTIONS } from './constants';
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
 * @property {string} id id.
 * @property {CountryCode} country Destination country code.
 * @property {string} currency Currency of the price.
 * @property {number} rate Shipping price.
 * @property {Object} options Options, such as `free_shipping_threshold`.
 */

/**
 * Campaign data.
 *
 * @typedef {Object} Campaign
 * @property {number} id Campaign ID.
 * @property {string} name Campaign name.
 * @property {'enabled'|'paused'|'removed'} status Campaign is currently running, has been paused or removed.
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
 * Account status data. Indicates the current status for the Google MC account.
 *
 * @typedef {Object} AccountStatus
 * @property {string} status Account status. See the available statuses here https://developers.google.com/shopping-content/reference/rest/v2.1/State
 * @property {number} cooldown Cooldown period timestamp indicating how long the user should wait until the next request
 * @property {Array} issues List of issue keys for this account
 * @property {Array} reviewEligibleRegions List of region codes available for review
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
 * @property {boolean} [website_live] Whether the store website is live.
 * @property {boolean} [checkout_process_secure] Whether the checkout process is complete and secure.
 * @property {boolean} [payment_methods_visible] Whether the payment methods are visible on the website.
 * @property {boolean} [refund_tos_visible] Whether the refund policy and terms of service are visible on the website.
 * @property {boolean} [contact_info_visible] Whether the phone number, email, and/or address are visible on the website.
 */

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
 * Upsert shipping rates.
 *
 * @param {Array<ShippingRate>} shippingRates Shipping rates to be upserted.
 * @return {Object} Action object to update shipping rates.
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
 * @param {Array<string>} ids IDs of shiping rates to be deleted.
 * @return {Object} Action object to delete shipping rates.
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

/**
 * Save the the MC settings.
 *
 * @param {SettingsData} settings settings
 * @return {Object} Action object to save target audience.
 */
export function* saveSettings( settings ) {
	yield apiFetch( {
		path: `${ API_NAMESPACE }/mc/settings`,
		method: 'POST',
		data: settings,
	} );

	return {
		type: TYPES.SAVE_SETTINGS,
		settings,
	};
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
		yield handleFetchError( error, error?.message );
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
 */
export function* receiveMappingRules( rules ) {
	return {
		type: TYPES.RECEIVE_MAPPING_RULES,
		rules,
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
		yield handleFetchError(
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
		yield handleFetchError(
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
		yield handleFetchError(
			error,
			__(
				'There was an error deleting the rule.',
				'google-listings-and-ads'
			)
		);
		throw error;
	}
}
