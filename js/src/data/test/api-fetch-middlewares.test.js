/**
 * Internal dependencies
 */
import { createErrorResponseCatcher } from '../api-fetch-middlewares';
import { API_NAMESPACE } from '../constants';

describe( 'createErrorResponseCatcher', () => {
	// Ref: https://github.com/WordPress/gutenberg/blob/%40wordpress/api-fetch%405.1.1/packages/api-fetch/src/index.js#L68-L81
	const checkStatus = ( response ) => {
		if ( response.status >= 200 && response.status < 300 ) {
			return response;
		}

		throw response;
	};

	const createFetchHandler = ( status, jsonParsedData ) => () => {
		const response = {
			status,
			json() {
				return Promise.resolve( jsonParsedData );
			},
		};
		return Promise.resolve( response ).then( checkStatus );
	};

	const passReject = ( response ) => Promise.reject( response );

	describe( `When the path doesn't belong to this plugin's namespace, shouldn't watch requests`, () => {
		let optionsOtherNamespace;
		beforeEach( () => {
			optionsOtherNamespace = { path: '' };
		} );

		it( `should not invoke this middleware's callback`, async () => {
			const errorMessage = {};
			const next = () => Promise.reject( errorMessage );
			const callback = jest
				.fn()
				.mockImplementation( passReject )
				.mockName( "middleware's callback" );
			const middleware = createErrorResponseCatcher( callback );

			const promise = middleware( optionsOtherNamespace, next );

			await expect( promise ).rejects.toBe( errorMessage );
			expect( callback ).toHaveBeenCalledTimes( 0 );
		} );

		it( 'should call next middleware with the original options', async () => {
			const next = jest.fn().mockName( 'next middleware' );
			const middleware = createErrorResponseCatcher();
			await middleware( optionsOtherNamespace, next );

			expect( next ).toHaveBeenCalledTimes( 1 );
			expect( next ).toHaveBeenCalledWith( optionsOtherNamespace );
		} );
	} );

	describe( `When the path belongs to this plugin's namespace`, () => {
		let optionsDefaultParse;
		let optionsDontParse;

		beforeEach( () => {
			optionsDefaultParse = { path: `${ API_NAMESPACE }/hi` };
			optionsDontParse = { path: `${ API_NAMESPACE }/hi`, parse: false };
		} );

		it( 'should force the value of `parse` option to `false` for the next middleware', async () => {
			const middleware = createErrorResponseCatcher( passReject );
			const next = jest
				.fn()
				.mockImplementation( createFetchHandler( 200, {} ) )
				.mockName( 'next middleware' );
			await middleware( optionsDefaultParse, next );

			expect( next ).toHaveBeenCalledWith( optionsDontParse );
		} );

		describe( 'should parse final result according to the given `parse` option', () => {
			let middleware;

			beforeEach( () => {
				middleware = createErrorResponseCatcher( passReject );
			} );

			it( 'For a successful response, by default, should resolve with parsed response body', async () => {
				const handler = createFetchHandler( 200, { data: 'hi' } );
				const result = await middleware( optionsDefaultParse, handler );

				expect( result ).toEqual( { data: 'hi' } );
			} );

			it( 'For a failed response, by default, should reject with parsed response body', async () => {
				const handler = createFetchHandler( 418, { message: 'oops' } );
				const promise = middleware( optionsDefaultParse, handler );

				await expect( promise ).rejects.toEqual( { message: 'oops' } );
			} );

			it( 'When given `parse: false` option, for a successful response, should resolve with the response instance', async () => {
				// The response instance should be passed through directly without any parse process of this middleware.
				const responseInstance = {};
				const next = () => Promise.resolve( responseInstance );
				const promise = middleware( optionsDontParse, next );

				await expect( promise ).resolves.toBe( responseInstance );
			} );

			it( 'when given `parse: false` option, for a failed response, should reject with the response instance', async () => {
				// The response instance should be passed through directly without any parse process of this middleware.
				const responseInstance = {};
				const next = () => Promise.reject( responseInstance );
				const promise = middleware( optionsDontParse, next );

				await expect( promise ).rejects.toBe( responseInstance );
			} );
		} );

		describe( 'should catch error response', () => {
			it( 'Regardless of the original `parse` option, should invoke callback function with response instance', async () => {
				const callback = jest
					.fn()
					.mockImplementation( passReject )
					.mockName( "middleware's callback" );
				const middleware = createErrorResponseCatcher( callback );

				let handler = createFetchHandler( 418, { message: 'oops' } );
				let promise = middleware( optionsDefaultParse, handler );

				await expect( promise ).rejects.toMatchObject(
					expect.anything()
				);
				expect( callback ).toHaveBeenCalledTimes( 1 );
				expect( callback ).toHaveBeenCalledWith( {
					status: 418,
					json: expect.any( Function ),
				} );

				handler = createFetchHandler( 500, { message: 'ouch' } );
				promise = middleware( optionsDontParse, handler );

				await expect( promise ).rejects.toMatchObject(
					expect.anything()
				);
				expect( callback ).toHaveBeenCalledTimes( 2 );
				expect( callback ).toHaveBeenCalledWith( {
					status: 500,
					json: expect.any( Function ),
				} );
			} );
		} );
	} );
} );
