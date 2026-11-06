/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY, useAppDispatch } from '~/data';

/**
 * Returns an object `{ settings, saveSettings, syncSettings }` to
 * be used in the Setup Free Listing page.
 *
 * `settings` is the saved values retrieved from API.
 * `saveSettings` action to save the plugin settings.
 * `syncSettings` action to sync the shipping settings to Google Merchant Center.
 */
const useSettings = () => {
	const { saveSettings, syncSettings } = useAppDispatch();

	const settings = useSelect( ( select ) => {
		return select( STORE_KEY ).getSettings();
	}, [] );

	return {
		settings,
		saveSettings,
		syncSettings,
	};
};

export default useSettings;
