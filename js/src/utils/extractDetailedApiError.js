/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

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
		if ( error && typeof error === 'object' ) {
			return {
				data: {
					statusCode: error.data?.status || 500,
					error: error.code,
					message:
						error.message ||
						__(
							'An unknown error occurred.',
							'google-listings-and-ads'
						),
				},
			};
		}

		return null;
	}

	const statusCode = error.status;

	if ( ignoredStatusCodes.includes( error.status ) ) {
		return null;
	}

	try {
		const clonedError = error.clone();
		const parsedError = await clonedError.json();

		// This is the new format for API errors, which includes a 'code' property to identify it as an API error.
		if ( parsedError?.code === 'API_ERROR' ) {
			return parsedError;
		}

		// For backward compatibility, we also check for the old format where the status code is directly on the error data.
		if ( parsedError?.message ) {
			return {
				data: {
					statusCode,
					error: parsedError.message,
					message: parsedError.message,
				},
			};
		}
	} catch {
		return new Error(
			__( 'Error parsing response.', 'google-listings-and-ads' )
		);
	}

	return null;
}
