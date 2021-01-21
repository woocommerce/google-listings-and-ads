/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../../../data';

const useAudienceSelectedCountryCodes = () => {
	const value = useSelect( ( select ) => {
		return select( STORE_KEY ).getAudienceSelectedCountryCodes();
	} );

	const { setAudienceSelectedCountryCodes } = useDispatch( STORE_KEY );

	return [ value, setAudienceSelectedCountryCodes ];
};

export default useAudienceSelectedCountryCodes;
