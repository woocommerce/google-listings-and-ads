/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useCountryKeyNameMap from './useCountryKeyNameMap';
import useGoogleMCAccount from './useGoogleMCAccount';

/**
 * @typedef {import('.~/hooks/types.js').StoreAddress} StoreAddress
 */

const emptyData = {
	address: '',
	address2: '',
	city: '',
	state: '',
	country: '',
	postcode: '',
	isMCAddressDifferent: null,
	isAddressFilled: null,
	missingRequiredFields: [],
};

/**
 * @typedef {Object} StoreAddressResult
 * @property {Function} refetch Dispatch a refetch action to reload store address.
 * @property {boolean} loaded Whether the data have been loaded.
 * @property {StoreAddress} data Store address data.
 */

/**
 * Get store address data and refectch function.
 *
 * @param {'wc'|'mc'} [source='wc'] The data source of store address. 'wc' by default.
 *     'wc': get from WooCommerce Settings.
 *     'mc': get from Google Merchant Center.
 * @return {StoreAddressResult} Store address result.
 */
export default function useStoreAddress( source = 'wc' ) {
	const { googleMCAccount } = useGoogleMCAccount();
	const countryNameDict = useCountryKeyNameMap();

	return useSelect(
		( select ) => {
			let data = emptyData;

			// If there is no MC account then there is no store address data to fetch.
			if ( ! googleMCAccount?.id ) {
				return {
					refetch: () => {},
					loaded: false,
					data,
				};
			}

			const {
				getGoogleMCContactInformation,
				hasFinishedResolution,
				refetch,
			} = select( STORE_KEY );

			const contact = getGoogleMCContactInformation();
			const loaded = hasFinishedResolution(
				'getGoogleMCContactInformation'
			);

			if ( contact && loaded ) {
				const {
					is_mc_address_different: isMCAddressDifferent,
					wc_address_errors: missingRequiredFields,
				} = contact;

				const storeAddress =
					source === 'wc' ? contact.wc_address : contact.mc_address;

				// Handle fallback for `null` fields to make sure the returned data types are consistent.
				const streetAddress = storeAddress?.street_address || '';
				const city = storeAddress?.locality || '';
				const state = storeAddress?.region || '';
				const postcode = storeAddress?.postal_code || '';

				const [ address, address2 = '' ] = streetAddress.split( '\n' );
				const country = countryNameDict[ storeAddress?.country ] || '';
				const countryCode = storeAddress?.country || '';
				const isAddressFilled = ! missingRequiredFields.length;

				data = {
					countryCode,
					address,
					address2,
					city,
					state,
					country,
					postcode,
					isAddressFilled,
					isMCAddressDifferent,
					missingRequiredFields,
				};
			}

			return {
				refetch,
				loaded,
				data,
			};
		},
		[ countryNameDict, googleMCAccount?.id, source ]
	);
}
