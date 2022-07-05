/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useDebouncedCallbackEffect from '.~/hooks/useDebouncedCallbackEffect';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

/**
 * @typedef { import(".~/data/actions").TargetAudienceData } TargetAudienceData
 */

/**
 * Automatically save settings value upon value change with a debounce delay.
 *
 * This will save the target audience data on first render,
 * because the data might be coming from the target audience suggestion API
 * which has not been saved before yet.
 *
 * @param {TargetAudienceData} targetAudience Target audience value object to be saved.
 */
const useAutoSaveTargetAudienceEffect = ( targetAudience ) => {
	const { saveTargetAudience } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	/**
	 * A `saveTargetAudienceCallback` callback that catches error and throws the error notice.
	 *
	 * @param {TargetAudienceData} value Target audience value object to be saved.
	 */
	const saveTargetAudienceCallback = async ( value ) => {
		try {
			await saveTargetAudience( value );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'There was an error saving target audience data.',
					'google-listings-and-ads'
				)
			);
		}
	};

	useDebouncedCallbackEffect( targetAudience, saveTargetAudienceCallback, {
		callOnFirstRender: true,
	} );
};

export default useAutoSaveTargetAudienceEffect;
