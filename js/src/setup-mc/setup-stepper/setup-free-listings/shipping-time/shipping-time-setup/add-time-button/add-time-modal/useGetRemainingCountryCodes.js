/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';

/**
 * Get the country codes that do not have any shipping time setup yet.
 * This is done by comparing the selected country codes in Step 2 Choose Audience page
 * and the shipping time setup in Step 3.
 *
 * @return	{Array<string>} array of country codes that do not have any shipping time setup yet.
 */
const useGetRemainingCountryCodes = () => {
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	const actual = useSelect( ( select ) => {
		return select( STORE_KEY )
			.getShippingTimes()
			.map( ( el ) => el.countryCode );
	}, [] );

	if ( ! selectedCountryCodes ) {
		return [];
	}

	const actualSet = new Set( actual );
	const remaining = selectedCountryCodes.filter(
		( el ) => ! actualSet.has( el )
	);

	return remaining;
};

export default useGetRemainingCountryCodes;
