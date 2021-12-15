/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useDebouncedCallbackEffect from '.~/hooks/useDebouncedCallbackEffect';

/**
 * Automatically save settings value upon value change with a debounce delay.
 *
 * This will save the target audience data on first render,
 * because the data might be coming from the target audience suggestion API
 * which has not been saved before yet.
 *
 * @param {Object} value Settings value object to be saved.
 */
const useAutoSaveTargetAudienceEffect = ( value ) => {
	const { saveTargetAudience } = useAppDispatch();

	useDebouncedCallbackEffect( value, saveTargetAudience, {
		callOnFirstRender: true,
	} );
};

export default useAutoSaveTargetAudienceEffect;
