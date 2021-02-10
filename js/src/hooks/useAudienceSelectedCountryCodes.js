/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data';

/**
 * Returns `[value, setValue]` for the selected country codes in the Step 2 Choose Audience page.
 *
 * Example format of `value`: `['US', 'AU', 'CN']`
 */
const useAudienceSelectedCountryCodes = () => {
	const value = useSelect( ( select ) => {
		return select( STORE_KEY ).getAudienceSelectedCountryCodes();
	} );

	const { setAudienceSelectedCountryCodes } = useDispatch( STORE_KEY );

	return [ value, setAudienceSelectedCountryCodes ];
};

export default useAudienceSelectedCountryCodes;
