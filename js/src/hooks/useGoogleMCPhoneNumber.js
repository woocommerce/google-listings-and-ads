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
