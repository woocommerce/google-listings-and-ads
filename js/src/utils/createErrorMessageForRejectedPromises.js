/**
 * Internal dependencies
 */
import createNoticeForMultipleErrors from '.~/utils/createMessageForMultipleErrors';

/**
 * A function to create an error message for rejected promises.
 *
 * @param  {Array<Promise>} promises Promises to be called.
 * @param  {Array<string>} promiseNames Name of each provided promise in the first argument. It will be used to create the error message and needs to follow the same order that in the first argument.
 * @return {Promise<string|null>} - The error message for rejected promises otherwise null if there are not rejected promises.
 */
export default async function createErrorMessageForRejectedPromises(
	promises,
	promiseNames
) {
	const results = await Promise.allSettled( promises );
	const rejectedMessages = results.reduce( ( acc, item, index ) => {
		if ( item.status === 'rejected' && promiseNames[ index ] ) {
			acc.push( promiseNames[ index ] );
		}
		return acc;
	}, [] );

	const isPartiallySuccessful = rejectedMessages.length < promises.length;

	return createNoticeForMultipleErrors(
		rejectedMessages,
		isPartiallySuccessful
	);
}
