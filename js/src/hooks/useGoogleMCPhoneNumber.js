/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { parsePhoneNumberFromString as parsePhoneNumber } from 'libphonenumber-js';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useGoogleMCAccount from './useGoogleMCAccount';

const emptyData = {
	country: '',
	countryCallingCode: '',
	nationalNumber: '',
	isValid: false,
	isVerified: false,
	display: '',
};

/**
 * @typedef {Object} PhoneNumber
 * @property {boolean} loaded Whether the data have been loaded.
 * @property {PhoneNumberData} data User's phone number data fetched from Google Merchant Center.
 */

/**
 * @typedef {Object} PhoneNumberData
 * @property {string} country The country code. Example: 'US'.
 * @property {string} countryCallingCode The country calling code. Example: '1'.
 * @property {string} nationalNumber The national (significant) number. Example: '2133734253'.
 * @property {boolean} isValid Whether the phone number is valid.
 * @property {boolean} isVerified Whether the phone number is verified.
 * @property {string} display The phone number string in international format. Example: '+1 213 373 4253'.
 */

/**
 * A hook to load user's phone number data from Google Merchant Center.
 *
 * @return {PhoneNumber} The payload of parsed phone number associated with the Google Merchant Center account and its loaded state.
 */
export default function useGoogleMCPhoneNumber() {
	const { googleMCAccount } = useGoogleMCAccount();

	return useSelect(
		( select ) => {
			let data = emptyData;

			// If there is no MC account then there is no phone number data to fetch.
			if ( ! googleMCAccount?.id ) {
				return {
					loaded: false,
					data,
				};
			}

			const { getGoogleMCContactInformation, hasFinishedResolution } =
				select( STORE_KEY );

			const contact = getGoogleMCContactInformation();
			const loaded = hasFinishedResolution(
				'getGoogleMCContactInformation'
			);

			if ( contact && loaded ) {
				// Prevent to call parsePhoneNumber with null.
				const parsed = parsePhoneNumber( contact.phone_number || '' );
				if ( parsed ) {
					data = {
						...parsed,
						isValid: parsed.isValid(),
						isVerified:
							contact.phone_verification_status === 'verified',
						display: parsed.formatInternational(),
					};
					delete data.metadata;
				}
			}

			return {
				loaded,
				data,
			};
		},
		[ googleMCAccount?.id ]
	);
}
