/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../../../data';

/**
 * Returns an object `{ settings }` to be used in the Setup Free Listing page.
 *
 * `settings` is the saved values retrieved from API.
 */
const useSetupFreeListingsSelect = () => {
	return useSelect( ( select ) => {
		const settings = select( STORE_KEY ).getSettings();

		return {
			settings,
		};
	} );
};

export default useSetupFreeListingsSelect;
