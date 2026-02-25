/**
 * Extracts detailed error information from a fetch Response object.
 *
 * @async
 * @param {Response} error - The fetch Response object to extract error details from.
 * @param {Object} options - Configuration options.
 * @param {number[]} [options.ignoredStatusCodes=[403, 503]] - HTTP status codes to ignore when extracting errors.
 * @return {Promise<Object|Error|null>} The parsed error object if it matches the API_ERROR code and is not in ignoredStatusCodes,
 *                                         an Error object if JSON parsing fails,
 *                                         or null if the input is not a Response or no error details are found.
 * @throws {Error} Throws if the error parameter is not a Response instance.
 */
export default async function extractDetailedApiError(
	error,
	{ ignoredStatusCodes = [ 403, 503 ] } = {}
) {
	// Only handle fetch Response errors
	if ( ! ( error instanceof Response ) ) {
		return null;
	}

	try {
		const clonedError = error.clone();
		const parsedError = await clonedError.json();

		if (
			parsedError?.code === 'API_ERROR' &&
			! ignoredStatusCodes.includes( parsedError?.data?.statusCode )
		) {
			return parsedError;
		}
	} catch {
		return new Error( 'Error parsing response.' );
	}

	return null;
}
