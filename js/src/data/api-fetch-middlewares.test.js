/**
 * Internal dependencies
 */
import { createErrorResponseCatcher } from './api-fetch-middlewares';
import { API_NAMESPACE } from './constants';

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

	let optionsDefaultParse;
	let optionsDontParse;
	let optionsOtherNamespace;

	beforeEach( () => {
		optionsDefaultParse = { path: `${ API_NAMESPACE }/hi` };
		optionsDontParse = { path: `${ API_NAMESPACE }/hi`, parse: false };
		optionsOtherNamespace = { path: '' };
	} );

	describe( `don't watch requests if the path doesn't belong to this plugin's namespace`, () => {
		it( 'should call next middleware with the original options', async () => {
			const mockNext = jest.fn();
			const middleware = createErrorResponseCatcher();
			await middleware( optionsOtherNamespace, mockNext );

			expect( mockNext ).toHaveBeenCalledTimes( 1 );
			expect( mockNext ).toHaveBeenCalledWith( optionsOtherNamespace );
		} );

		it( `should not invoke this middleware's callback`, async () => {
			const errorMessage = {};
			const next = () => Promise.reject( errorMessage );
			const mockCallback = jest.fn().mockImplementation( passReject );
			const middleware = createErrorResponseCatcher( mockCallback );
			const promise = middleware( optionsOtherNamespace, next );

			await expect( promise ).rejects.toBe( errorMessage );
			expect( mockCallback ).toHaveBeenCalledTimes( 0 );
		} );
	} );

	describe( 'force overwrite `parse` option to `false` and return the same result', () => {
		let middleware;

		beforeEach( () => {
			middleware = createErrorResponseCatcher( passReject );
		} );

		it( 'should resolve parsed response body by default in a successful response', async () => {
			const handler = createFetchHandler( 200, { data: 'hi' } );
			const result = await middleware( optionsDefaultParse, handler );

			expect( result ).toEqual( { data: 'hi' } );
		} );

		it( 'should reject parsed response body by default in a failed response', async () => {
			const handler = createFetchHandler( 418, { message: 'oops' } );
			const promise = middleware( optionsDefaultParse, handler );

			await expect( promise ).rejects.toEqual( { message: 'oops' } );
		} );

		it( 'should resolve response instance when indicating `parse: false` in a successful response', async () => {
			// The response instance should be passed through directly without any parse process of this middleware.
			const responseInstance = {};
			const next = () => Promise.resolve( responseInstance );
			const promise = middleware( optionsDontParse, next );

			await expect( promise ).resolves.toBe( responseInstance );
		} );

		it( 'should reject response instance when indicating `parse: false` in a failed response', async () => {
			// The response instance should be passed through directly without any parse process of this middleware.
			const responseInstance = {};
			const next = () => Promise.reject( responseInstance );
			const promise = middleware( optionsDontParse, next );

			await expect( promise ).rejects.toBe( responseInstance );
		} );
	} );

	describe( 'Catch error response', () => {
		it( 'should invoke callback function with response instance regardless of the original `parse` option', async () => {
			const mockCallback = jest.fn().mockImplementation( passReject );
			const middleware = createErrorResponseCatcher( mockCallback );

			let handler = createFetchHandler( 418, { message: 'oops' } );
			let promise = middleware( optionsDefaultParse, handler );

			await expect( promise ).rejects.toMatchObject( expect.anything() );
			expect( mockCallback ).toHaveBeenCalledTimes( 1 );
			expect( mockCallback ).toHaveBeenCalledWith( {
				status: 418,
				json: expect.any( Function ),
			} );

			handler = createFetchHandler( 500, { message: 'ouch' } );
			promise = middleware( optionsDontParse, handler );

			await expect( promise ).rejects.toMatchObject( expect.anything() );
			expect( mockCallback ).toHaveBeenCalledTimes( 2 );
			expect( mockCallback ).toHaveBeenCalledWith( {
				status: 500,
				json: expect.any( Function ),
			} );
		} );
	} );
} );
