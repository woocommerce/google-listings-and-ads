/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../../../data';

const useAudienceSelectedCountries = () => {
	const value = useSelect( ( select ) => {
		return select( STORE_KEY ).getAudienceSelectedCountries();
	} );

	const { setAudienceSelectedCountries } = useDispatch( STORE_KEY );

	return [ value, setAudienceSelectedCountries ];
};

export default useAudienceSelectedCountries;
