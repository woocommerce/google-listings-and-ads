/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../../../../../../../data';

const useGetRemainingCountryCodes = () => {
	const expected = useSelect( ( select ) => {
		return select( STORE_KEY ).getAudienceSelectedCountryCodes();
	} );

	const actual = useSelect( ( select ) => {
		return select( STORE_KEY )
			.getShippingRates()
			.map( ( el ) => el.countryCode );
	} );

	const actualSet = new Set( actual );
	const remaining = expected.filter( ( el ) => ! actualSet.has( el ) );

	return remaining;
};

export default useGetRemainingCountryCodes;
