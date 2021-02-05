/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useAudienceSelectedCountryCodes from '../../../../../../../hooks/useAudienceSelectedCountryCodes';
import { STORE_KEY } from '../../../../../../../data';

/**
 * Get the country codes that do not have any shipping rate setup yet.
 * This is done by comparing the selected country codes in Step 2 Choose Audience page
 * and the shipping rate setup in Step 3.
 */
const useGetRemainingCountryCodes = () => {
	const [ selectedCountryCodes ] = useAudienceSelectedCountryCodes();
	const actual = useSelect( ( select ) => {
		return select( STORE_KEY )
			.getShippingRates()
			.map( ( el ) => el.countryCode );
	} );

	const actualSet = new Set( actual );
	const remaining = selectedCountryCodes.filter(
		( el ) => ! actualSet.has( el )
	);

	return remaining;
};

export default useGetRemainingCountryCodes;
