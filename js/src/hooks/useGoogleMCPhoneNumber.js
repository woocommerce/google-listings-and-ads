/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { parsePhoneNumberFromString as parsePhoneNumber } from 'libphonenumber-js';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const selectorName = 'getGoogleMCPhoneNumber';
const emptyData = {
	country: '',
	countryCallingCode: '',
	nationalNumber: '',
	isValid: false,
	display: '',
};

export default function useGoogleMCPhoneNumber() {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const phone = selector[ selectorName ]();
		let data = emptyData;

		if ( phone ) {
			const parsed = parsePhoneNumber( phone.phone_number );
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
			loaded: selector.hasFinishedResolution( selectorName ),
			data,
		};
	}, [] );
}
