/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useDebouncedCallbackEffect from '.~/hooks/useDebouncedCallbackEffect';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

/**
 * @typedef { import(".~/data/actions").SettingsData } SettingsData
 */

/**
 * Automatically save settings value upon value change with a debounce delay.
 * It does not save on first render since the first render is the loading of value from API.
 *
 * @param {SettingsData} settings Settings value object to be saved.
 */
const useAutoSaveSettingsEffect = ( settings ) => {
	const { saveSettings } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	/**
	 * A `saveSettingsCallback` callback that catches error and throws the error notice.
	 *
	 * @param {SettingsData} value Target audience value object to be saved.
	 */
	const saveSettingsCallback = async ( value ) => {
		try {
			await saveSettings( value );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'There was an error trying to save settings. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	useDebouncedCallbackEffect( settings, saveSettingsCallback );
};

export default useAutoSaveSettingsEffect;
