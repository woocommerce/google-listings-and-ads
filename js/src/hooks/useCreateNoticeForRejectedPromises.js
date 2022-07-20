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
 *
 * @callback CreateNoticeForRejectedPromises
 * @param  {Array<Promise>} promises Promises to be called.
 * @param  {Array<string>} promiseNames Name of each provided promise in the first argument. It will be used to create the notice and needs to follow the same order that in the first argument.
 * @return {Array<string>} The error messages that have been displayed.
 */

/**
 * A hook to create an error notice for rejected promises.
 *
 * @return {CreateNoticeForRejectedPromises} - A function that will create the error notice for the rejected promises.
 */
export default function useCreateNoticeForRejectedPromises() {
	const { createNotice } = useDispatchCoreNotices();

	/**
	 * @type {CreateNoticeForRejectedPromises} CreateNoticeForRejectedPromises	 *
	 */
	const createNoticeForRejectedPromises = async (
		promises,
		promiseNames
	) => {
		const results = await Promise.allSettled( promises );
		const rejectedMessages = results.reduce( ( acc, item, index ) => {
			if ( item.status === 'rejected' && promiseNames[ index ] ) {
				acc.push( promiseNames[ index ] );
			}
			return acc;
		}, [] );

		if ( rejectedMessages.length ) {
			const listErrors = concatenateListOfWords( rejectedMessages );

			const content = sprintf(
				// translators: 1: list of errors 2: optional text if some promises have been invoked successfully.
				__(
					'There are errors in the following actions: %1$s. %2$s Please try again later.',
					'google-listings-and-ads'
				),
				listErrors,
				rejectedMessages.length < promises.length
					? 'Other changes have been saved.'
					: ''
			);

			createNotice( 'error', content );
		}

		return rejectedMessages;
	};

	return { createNoticeForRejectedPromises };
}
