/*
 * This module is intended to make it easier to mock `console` in Jest tests.
 */

export function logError( ...args ) {
	// eslint-disable-next-line no-console
	console.error( ...args );
}
