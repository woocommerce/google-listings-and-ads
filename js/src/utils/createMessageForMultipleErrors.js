/**
 * External dependencies
 */
import { __, sprintf, _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import concatenateListOfWords from '.~/utils/concatenateListOfWords';

/**
 * Creates a message for a list of error messages. For example for a list of error messages: createMessageForMultipleErrors(['Target Audience', 'Shipping Rates']) will create the following message:
 * 'There are errors in the following actions: Target Audience and Shipping Rates. Please try again later.'
 *
 * @param {Array<string>} errorMessages Error messages to be displayed in the notice.
 * @param {boolean} [isPartiallySuccessful='true'] Whether some actions were successful.
 * @return {string|null} - Return the error message for a list of error otherwise null if there are not error messages
 */
export default function createMessageForMultipleErrors(
	errorMessages,
	isPartiallySuccessful = true
) {
	if ( errorMessages.length ) {
		const listErrors = concatenateListOfWords( errorMessages );

		const content = sprintf(
			// translators: 1: list of errors 2: optional text if some promises have been invoked successfully.
			_n(
				'There is an error in the following action: %1$s. %2$s Please try again later.',
				'There are errors in the following actions: %1$s. %2$s Please try again later.',
				errorMessages.length,
				'google-listings-and-ads'
			),
			listErrors,
			isPartiallySuccessful ? __( 'Other changes have been saved.' ) : ''
		);

		return content;
	}

	return null;
}
