/**
 * External dependencies
 */
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useAudienceSelectedCountries from '../../choose-audience/useAudienceSelectedCountries';

// from Step 2 Choose Your Audience.
// TODO: consider not to use this and remove this, because this is too coupled with the label logic.
const useGetAudienceCountries = () => {
	const [ value ] = useAudienceSelectedCountries();
	const keyNameMap = useSelect( ( select ) => {
		return select( SETTINGS_STORE_NAME ).getSetting(
			'wc_admin',
			'countries'
		);
	} );

	const result = value.map( ( el ) => {
		return {
			key: el,
			label: keyNameMap[ el ],
		};
	} );

	return result;
};

export default useGetAudienceCountries;
