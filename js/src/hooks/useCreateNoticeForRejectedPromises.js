/**
 * Internal dependencies
 */
import useCreateNoticeForMultipleErrors from '.~/hooks/useCreateNoticeForMultipleErrors';

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
	const {
		createNoticeForMultipleErrors,
	} = useCreateNoticeForMultipleErrors();

	/**
	 * @type {CreateNoticeForRejectedPromises} CreateNoticeForRejectedPromises
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

		const isPartiallySuccessful = rejectedMessages.length < promises.length;

		createNoticeForMultipleErrors(
			rejectedMessages,
			isPartiallySuccessful
		);

		return rejectedMessages;
	};

	return { createNoticeForRejectedPromises };
}
