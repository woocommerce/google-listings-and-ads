/**
 * External dependencies
 */
import { __, sprintf, _n, _x } from '@wordpress/i18n';

/**
 * Creates a message for a list of error messages. For example for a list of error messages: createMessageForMultipleErrors(['Target Audience', 'Shipping Rates']) will create the following message:
 * 'There are errors in the following actions: Target Audience and Shipping Rates. Please try again later.'
 *
 * @param {Array<string>} errorMessages Error messages to be joined.
 * @param {boolean} [isPartiallySuccessful='true'] Whether some actions were successful.
 * @return {string|null} - Return the error message for a list of errors otherwise null if there are no error messages
 */
export default function createMessageForMultipleErrors(
	errorMessages,
	isPartiallySuccessful = true
) {
	if ( errorMessages.length ) {
		const separator = _x(
			', ',
			'the separator for concatenating the messages of failed actions',
			'google-listings-and-ads'
		);
		const listErrors = sprintf(
			// translators: 1: optional string when there are multiple failed actions, and it's a concatenated text of failed actions except for the last one. 2: the last one or the only failed action.
			_n(
				'There is an error in the following action: %1$s%2$s.',
				'There are errors in the following actions: %1$s and %2$s.',
				errorMessages.length,
				'google-listings-and-ads'
			),
			errorMessages.slice( 0, -1 ).join( separator ),
			errorMessages.at( -1 )
		);

		const content = sprintf(
			// translators: 1: text for the failed action(s). 2: optional text if some promises have been invoked successfully.
			__(
				'%1$s %2$s Please try again later.',
				'google-listings-and-ads'
			),
			listErrors,
			isPartiallySuccessful
				? __(
						'Other changes have been saved.',
						'google-listings-and-ads'
				  )
				: ''
		);

		return content;
	}

	return null;
}
