/**
 * Internal dependencies
 */
import createMessageForMultipleErrors from '.~/utils/createMessageForMultipleErrors';

/**
 * A function to create an error message for rejected promises.
 *
 * @param  {Array<Promise>} promises Promises to be called.
 * @param  {Array<string>} promiseNames Name of each promise provided in the first argument. It will be used to create the error message and needs to be in same order that in the first argument.
 * @return {Promise<string|null>} The error message for rejected promises, otherwise will be null if there are no rejected promises.
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

	return createMessageForMultipleErrors(
		rejectedMessages,
		isPartiallySuccessful
	);
}
