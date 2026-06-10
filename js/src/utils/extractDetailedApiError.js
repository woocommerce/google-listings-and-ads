/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Extracts detailed error information from a fetch Response object or error-like object.
 *
 * @async
 * @param {Response|Object} responseOrError - The fetch Response object or error-like object to extract error details from.
 * @param {Object} options - Configuration options.
 * @param {number[]} [options.ignoredStatusCodes=[403, 503]] - HTTP status codes to ignore when extracting errors.
 * @return {Promise<Object|Error|null>} The parsed error object if it matches the API_ERROR code and is not in ignoredStatusCodes,
 *                                         an Error object if JSON parsing fails,
 *                                         or null if the input is not a Response or no error details are found.
 */
export default async function extractDetailedApiError(
	responseOrError,
	{ ignoredStatusCodes = [ 403, 503 ] } = {}
) {
	// Handle non-fetch errors (plain objects or error-like objects)
	if ( ! ( responseOrError instanceof Response ) ) {
		if ( responseOrError && typeof responseOrError === 'object' ) {
			if ( responseOrError.code === 'API_ERROR' ) {
				return responseOrError;
			}

			return {
				data: {
					statusCode: responseOrError.data?.status || 500,
					error: responseOrError.code || null,
					message:
						responseOrError.message ||
						__(
							'An unknown error occurred.',
							'google-listings-and-ads'
						),
				},
			};
		}

		return null;
	}

	const { status: statusCode } = responseOrError;

	if ( ignoredStatusCodes.includes( statusCode ) ) {
		return null;
	}

	try {
		const clonedResponse = responseOrError.clone();
		const parsedError = await clonedResponse.json();

		// This is the new format for API errors, which includes a 'code' property to identify it as an API error.
		if ( parsedError?.code === 'API_ERROR' ) {
			return parsedError;
		}

		// For backward compatibility, we also check for the old format where the status code is directly on the error data.
		if ( parsedError?.message ) {
			return {
				data: {
					statusCode,
					error:
						parsedError.code ||
						parsedError.error ||
						parsedError.message,
					message: parsedError.message,
				},
			};
		}

		// Surface unrecognised but successfully-parsed bodies instead of silently returning null
		return {
			data: {
				statusCode,
				error: 'UNKNOWN_ERROR',
				message: __(
					'An unrecognised error response was received.',
					'google-listings-and-ads'
				),
			},
		};
	} catch ( err ) {
		return new Error(
			__( 'Error parsing response.', 'google-listings-and-ads' ),
			{ cause: err }
		);
	}
}
