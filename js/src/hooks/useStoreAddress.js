/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';
import useCountryKeyNameMap from './useCountryKeyNameMap';

const emptyData = {
	address: '',
	address2: '',
	city: '',
	state: '',
	country: '',
	postcode: '',
	isMCAddressDifferent: null,
	isAddressFilled: null,
};

/**
 * @typedef {Object} StoreAddress
 * @property {string} address Store address line 1.
 * @property {string} address2 Address line 2.
 * @property {string} city Store city.
 * @property {string} state Store country state if available.
 * @property {string} country Store country.
 * @property {string} postcode Store postcode.
 * @property {boolean|null} isAddressFilled Whether the minimum address data is filled in.
 *                          `null` if data have not loaded yet.
 * @property {boolean|null} isMCAddressDifferent Whether the address data from WC store and GMC are the same.
 *                          `null` if data have not loaded yet.
 */
/**
 * @typedef {Object} StoreAddressResult
 * @property {Function} refetch Dispatch a refetch action to reload store address.
 * @property {boolean} loaded Whether the data have been loaded.
 * @property {StoreAddress} data Store address data.
 */
/**
 * Get store address data and refectch function.
 *
 * @return {StoreAddressResult} Store address result.
 */
export default function useStoreAddress() {
	const {
		data: contact,
		hasFinishedResolution: loaded,
		invalidateResolution: refetch,
	} = useAppSelectDispatch( 'getGoogleMCContactInformation' );

	const countryNameDict = useCountryKeyNameMap();

	let data = emptyData;

	if ( loaded && contact ) {
		const {
			wc_address: {
				country: countryCode,
				locality: city,
				postal_code: postcode,
				region: state,
				street_address: streetAddress,
			},
			is_mc_address_different: isMCAddressDifferent,
		} = contact;

		const [ address, address2 = '' ] = streetAddress.split( '\n' );
		const country = countryNameDict[ countryCode ];
		const isAddressFilled = !! ( address && city && country && postcode );

		data = {
			address,
			address2,
			city,
			state,
			country,
			postcode,
			isAddressFilled,
			isMCAddressDifferent,
		};
	}

	return {
		refetch,
		loaded,
		data,
	};
}
