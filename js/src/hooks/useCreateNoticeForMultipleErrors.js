/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import concatenateListOfWords from '.~/utils/concatenateListOfWords';

/**
 * Creates an error notice for a list of error messages. For example for a list of error messages: createNoticeForMultipleErrors(['Target Audience', 'Shipping Rates']) will create a notice with the following message:
 * 'There are errors in the following actions: Target Audience and Shipping Rates. Please try again later.'
 *
 * @callback CreateNoticeForMultipleErrors
 * @param  {Array<string>} errorMessages Error messages to be displayed in the notice.
 * @param {boolean} [isPartiallySuccessful] Whether some actions were successful.
 */

/**
 * @typedef {Object} UseCreateNoticeForMultipleErrors
 * @property {CreateNoticeForMultipleErrors} createNoticeForMultipleErrors Function to create a notice for a list of errors.
 */

/**
 * A hook to create an error notice for multiple messages.
 *
 * @return {UseCreateNoticeForMultipleErrors} - A function that will create an error notice for a list of messages.
 */
export default function useCreateNoticeForMultipleErrors() {
	const { createNotice } = useDispatchCoreNotices();

	/**
	 * @type {CreateNoticeForMultipleErrors} CreateNoticeForMultipleErrors
	 */
	const createNoticeForMultipleErrors = (
		errorMessages,
		isPartiallySuccessful = true
	) => {
		if ( errorMessages.length ) {
			const listErrors = concatenateListOfWords( errorMessages );

			const content = sprintf(
				// translators: 1: list of errors 2: optional text if some promises have been invoked successfully.
				__(
					'There are errors in the following actions: %1$s. %2$s Please try again later.',
					'google-listings-and-ads'
				),
				listErrors,
				isPartiallySuccessful
					? __( 'Other changes have been saved.' )
					: ''
			);

			createNotice( 'error', content );
		}
	};

	return { createNoticeForMultipleErrors };
}
