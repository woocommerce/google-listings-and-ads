/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../../../data';

/**
 * Returns an object `{ settings, displayTaxRate }` to be used in the Setup Free Listing page.
 *
 * `settings` is the saved values retrieved from API.
 *
 * `displayTaxRate` is a boolean to indicate whether the tax rate section should be shown or not.
 * Tax rate section should be shown when the audience countries contain `'US'`.
 */
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
