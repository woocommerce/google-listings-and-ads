/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../../../data';

const useDisplayTaxRate = () => {
	return useSelect( ( select ) => {
		const audienceCountryCodes =
			select( STORE_KEY ).getAudienceSelectedCountryCodes() || [];
		const displayTaxRate = audienceCountryCodes.includes( 'US' );

		return {
			displayTaxRate,
		};
	} );
};

export default useDisplayTaxRate;
