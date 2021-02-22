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
const useAutoSaveTargetAudienceEffect = ( value ) => {
	const { saveTargetAudience } = useAppDispatch();

	useDebouncedCallbackEffect( value, saveTargetAudience );
};

export default useAutoSaveTargetAudienceEffect;
