/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { parsePhoneNumberFromString as parsePhoneNumber } from 'libphonenumber-js';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const emptyData = {
	country: '',
	countryCallingCode: '',
	nationalNumber: '',
	isValid: false,
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
 * @property {string} display The phone number string in international format. Example: '+1 213 373 4253'.
 */

/**
 * A hook to load user's phone number data from Google Merchant Center.
 *
 * @return {PhoneNumber} [description]
 */
export default function useGoogleMCPhoneNumber() {
	return useSelect( ( select ) => {
		const { getGoogleMCPhoneNumber } = select( STORE_KEY );
		const { data: contact, loaded } = getGoogleMCPhoneNumber();
		let data = emptyData;

		if ( contact ) {
			// Prevent to call parsePhoneNumber with null.
			const parsed = parsePhoneNumber( contact.phone_number || '' );
			if ( parsed ) {
				data = {
					...parsed,
					isValid: parsed.isValid(),
					display: parsed.formatInternational(),
				};
				delete data.metadata;
			}
		}

		return {
			loaded,
			data,
		};
	}, [] );
}
