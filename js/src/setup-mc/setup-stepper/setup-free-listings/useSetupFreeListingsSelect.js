/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../../../data';

const useSetupFreeListingsSelect = () => {
	return useSelect( ( select ) => {
		const { getSettings, getAudienceSelectedCountryCodes } = select(
			STORE_KEY
		);
		const settings = getSettings();
		const audienceCountryCodes = getAudienceSelectedCountryCodes() || [];
		const displayTaxRate = audienceCountryCodes.includes( 'US' );

		return {
			settings,
			displayTaxRate,
		};
	} );
};

export default useSetupFreeListingsSelect;
