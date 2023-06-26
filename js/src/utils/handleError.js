/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NOTICES_STORE_KEY } from '.~/data/constants';

/**
 * @typedef {Object} ApiError
 * @property {string} [message] The error reason.
 * @property {number} [statusCode] The HTTP response status code.
 */

// Functions in this module use optional chaining to access `error` because the
// thrown error has various possibilities and there is no way to ensure
// that it's always an object type or an error instance.

/**
 * Resolves the final error message.
 *
 * @param {ApiError} error The error data or instance.
 * @param {string} [leadingMessage] Optional leading message.
 * @param {string} [fallbackMessage] Optional fallback message if API error message is not a string.
 */
export function resolveErrorMessage( error, leadingMessage, fallbackMessage ) {
	const messages = [];
	const apiMessage = error?.message;

	if ( leadingMessage ) {
		messages.push( leadingMessage );
	}

	if ( apiMessage && typeof apiMessage === 'string' ) {
		messages.push( apiMessage );
	} else if ( fallbackMessage ) {
		messages.push( fallbackMessage );
	}

	if ( messages.length === 0 ) {
		messages.push(
			__( 'Unknown error occurred.', 'google-listings-and-ads' )
		);
	}

	return messages.join(
		_x(
			' ',
			`The spacing between sentences. It's a space in English. Please use an empty string if no spacing is needed in that language.`,
			'google-listings-and-ads'
		)
	);
}

/**
 * Handles the API error by showing the error message via a notification for users
 * and also via `console.error` for developers.
 *
 * Please note that authorization errors will be only printed by `console.error`.
 *
 * @param {ApiError} error The error got from an API response.
 * @param {string} [leadingMessage] Optional leading message.
 * @param {string} [fallbackMessage] Optional fallback message if API error message is not available.
 */
export function handleApiError( error, leadingMessage, fallbackMessage ) {
	// In this extension, errors with the 401 `statusCode` are used to indicate
	// authorization errors, and they were already handled in the middleware of
	// the `apiFetch` when runtime reaches here.
	//
	// Ref: The call of `apiFetch.use`` in js/src/data/index.js
	if ( error?.statusCode !== 401 ) {
		const args = [ error, leadingMessage, fallbackMessage ];
		const message = resolveErrorMessage( ...args );

		dispatch( NOTICES_STORE_KEY ).createNotice( 'error', message );
	}

	// eslint-disable-next-line no-console
	console.error( error );
}
