/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { resolveErrorMessage, handleApiError } from './handleError';

jest.mock( '@wordpress/data', () => {
	return {
		dispatch: jest.fn().mockName( 'dispatch' ),
	};
} );

describe( 'resolveErrorMessage', () => {
	it( 'When the arguments do not have any available messages, it should return "Unknown error occurred."', () => {
		const message = resolveErrorMessage( {}, null, null );

		expect( message ).toBe( 'Unknown error occurred.' );
	} );

	it( 'Should place the `leadingMessage` in front and `error.message` in the back', () => {
		const message = resolveErrorMessage(
			{ message: 'Something happened.' },
			'Oh no.'
		);

		expect( message ).toBe( 'Oh no. Something happened.' );
	} );

	it( 'Only when the `error.message` is a string, it should be used', () => {
		let message = resolveErrorMessage( { message: 'Something happened.' } );

		expect( message ).toBe( 'Something happened.' );

		message = resolveErrorMessage( new Error( 'Something happened.' ) );

		expect( message ).toBe( 'Something happened.' );

		message = resolveErrorMessage( null, 'a' );

		expect( message ).toBe( 'a' );

		message = resolveErrorMessage( undefined, 'b' );

		expect( message ).toBe( 'b' );

		message = resolveErrorMessage( {}, 'c' );

		expect( message ).toBe( 'c' );

		message = resolveErrorMessage( { message: NaN }, 'd' );

		expect( message ).toBe( 'd' );

		message = resolveErrorMessage( { message: 123 }, 'e' );

		expect( message ).toBe( 'e' );
	} );

	it( 'When the `error.message` is a string, it should not use the `fallbackMessage`', () => {
		const message = resolveErrorMessage(
			{ message: 'Something happened.' },
			null,
			'The code works well on my machine!'
		);

		expect( message ).toBe( 'Something happened.' );
	} );

	it( 'When the `error.message` is not available, it should replace it with the `fallbackMessage`', () => {
		let message = resolveErrorMessage(
			null,
			null,
			'The code works well on my machine!'
		);

		expect( message ).toBe( 'The code works well on my machine!' );

		message = resolveErrorMessage(
			null,
			'Hmm.',
			'The code works well on my machine!'
		);

		expect( message ).toBe( 'Hmm. The code works well on my machine!' );
	} );
} );

describe( 'handleApiError', () => {
	let createNotice;
	let consoleError;

	beforeEach( () => {
		createNotice = jest.fn().mockName( 'createNotice' );
		dispatch.mockImplementation( () => ( { createNotice } ) );

		consoleError = jest.spyOn( global.console, 'error' );
	} );

	afterEach( () => {
		consoleError.mockReset();
	} );

	it( 'Should call to `console.error` with the given error', () => {
		let error = new Error( 'Oops!' );

		handleApiError( error );

		expect( consoleError ).toHaveBeenCalledWith( error );

		error = { statusCode: 401, message: 'Oops!' };

		handleApiError( error );

		expect( consoleError ).toHaveBeenCalledWith( error );
	} );

	it( `Should use the 'core/notices' wp-data store to dispatch notification actions`, () => {
		handleApiError();

		expect( dispatch ).toHaveBeenCalledWith( 'core/notices' );
	} );

	it( 'When the error is not related to authorization, it should dispatch a notification action', () => {
		handleApiError( { message: 'Oops!' } );

		expect( createNotice ).toHaveBeenCalledWith( 'error', 'Oops!' );

		handleApiError( { statusCode: 404, message: 'Oops!' } );

		expect( createNotice ).toHaveBeenCalledWith( 'error', 'Oops!' );

		handleApiError( { statusCode: 500, message: 'Oops!' } );

		expect( createNotice ).toHaveBeenCalledWith( 'error', 'Oops!' );
	} );

	it( 'When the error is related to authorization, it should not dispatch any notification actions', () => {
		handleApiError( { statusCode: 401, message: 'Oops!' } );

		expect( createNotice ).toHaveBeenCalledTimes( 0 );
	} );
} );
