/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useAudienceSelectedCountryCodes from '../../../../../../../hooks/useAudienceSelectedCountryCodes';
import { STORE_KEY } from '../../../../../../../data';

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
