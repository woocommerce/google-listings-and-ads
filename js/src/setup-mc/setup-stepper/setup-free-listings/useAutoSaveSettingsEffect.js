/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useDebouncedCallbackEffect from '.~/hooks/useDebouncedCallbackEffect';

/**
 * Automatically save settings value upon value change with a debounce delay.
 * It does not save on first render since the first render is the loading of value from API.
 *
 * @param {Object} value Settings value object to be saved.
 */
const useAutoSaveSettingsEffect = ( value ) => {
	const { saveSettings } = useAppDispatch();

	useDebouncedCallbackEffect( value, saveSettings );
};

export default useAutoSaveSettingsEffect;
