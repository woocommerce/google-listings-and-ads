/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { SETTINGS_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';

const optionNames = [
	'woocommerce_store_address',
	'woocommerce_store_address_2',
	'woocommerce_store_city',
	'woocommerce_default_country',
	'woocommerce_store_postcode',
];

const selectorName = 'getOption';

/**
 * @typedef {Object} StoreAddress
 * @property {string} address Store address line 1.
 * @property {string} address2 Address line 2.
 * @property {string} city Store city.
 * @property {string} state Store country state if available.
 * @property {string} country Store country.
 * @property {string} postcode Store postcode.
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
	const { invalidateResolution } = useDispatch( OPTIONS_STORE_NAME );
	const refetch = useCallback( () => {
		optionNames.forEach( ( name ) =>
			invalidateResolution( selectorName, [ name ] )
		);
	}, [ invalidateResolution ] );

	return useSelect(
		( select ) => {
			const selector = select( OPTIONS_STORE_NAME );
			const { hasFinishedResolution } = selector;

			const [
				address,
				address2,
				city,
				countryAndState,
				postcode,
			] = optionNames.map( ( name ) => selector[ selectorName ]( name ) );

			const loaded = optionNames
				.map( ( name ) =>
					hasFinishedResolution( selectorName, [ name ] )
				)
				.every( Boolean );

			let country = '';
			let state = '';

			if ( loaded ) {
				const { getSetting } = select( SETTINGS_STORE_NAME );
				const { countries } = getSetting( 'wc_admin', 'dataEndpoints' );
				const [ countryCode, stateCode ] = countryAndState.split( ':' );

				const countryMeta = countries.find(
					( el ) => el.code === countryCode
				);
				if ( countryMeta ) {
					country = countryMeta.name;

					const stateMeta = countryMeta.states.find(
						( el ) => el.code === stateCode
					);
					if ( stateMeta ) {
						state = stateMeta.name;
					}
				}
			}

			return {
				refetch,
				loaded,
				data: {
					address,
					address2,
					city,
					state,
					country,
					postcode,
				},
			};
		},
		[ refetch ]
	);
}
