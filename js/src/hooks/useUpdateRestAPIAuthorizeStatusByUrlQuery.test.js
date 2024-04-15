/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useUpdateRestAPIAuthorizeStatusByUrlQuery from './useUpdateRestAPIAuthorizeStatusByUrlQuery';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

jest.mock( '@woocommerce/navigation' );
jest.mock( '.~/hooks/useApiFetchCallback' );

describe( 'useUpdateRestAPIAuthorizeStatusByUrlQuery', () => {
	let fetchUpdateRestAPIAuthorize;
	let consoleErrorSpy;

	beforeEach( () => {
		fetchUpdateRestAPIAuthorize = jest.fn();
		useApiFetchCallback.mockImplementation( () => [
			fetchUpdateRestAPIAuthorize,
		] );
		consoleErrorSpy = jest.spyOn( global.console, 'error' );
		consoleErrorSpy.mockImplementation( jest.fn() );
	} );

	afterEach( () => {
		consoleErrorSpy.mockRestore();
	} );

	it( 'should not call fetchUpdateRestAPIAuthorize if both nonce from URL query and nonce from the option are not defined', () => {
		const nonceFromOptions = '';
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'approved',
		} );
		renderHook( () =>
			useUpdateRestAPIAuthorizeStatusByUrlQuery( nonceFromOptions )
		);
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'should print console error if nonces mismatch', () => {
		const nonceFromOptions = 'nonce-123';
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'approved',
			nonce: 'nonce-456',
		} );
		renderHook( () =>
			useUpdateRestAPIAuthorizeStatusByUrlQuery( nonceFromOptions )
		);
		expect( consoleErrorSpy ).toHaveBeenCalledWith(
			new Error(
				'Nonces mismatch when updating REST API authorize status.'
			)
		);
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if nonces match and query param is approved', () => {
		const nonceFromOptions = 'nonce-123';
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'approved',
			nonce: 'nonce-123',
		} );
		renderHook( () =>
			useUpdateRestAPIAuthorizeStatusByUrlQuery( nonceFromOptions )
		);
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'approved',
			},
		} );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if nonces match and query param is disapproved', () => {
		const nonceFromOptions = 'nonce-123';
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'disapproved',
			nonce: 'nonce-123',
		} );
		renderHook( () =>
			useUpdateRestAPIAuthorizeStatusByUrlQuery( nonceFromOptions )
		);
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'disapproved',
			},
		} );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if nonces match and query param is error', () => {
		const nonceFromOptions = 'nonce-123';
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'error',
			nonce: 'nonce-123',
		} );
		renderHook( () =>
			useUpdateRestAPIAuthorizeStatusByUrlQuery( nonceFromOptions )
		);
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'error',
			},
		} );
	} );

	it( 'should not call fetchUpdateRestAPIAuthorize if nonces match but query param is unknown', () => {
		const nonceFromOptions = 'nonce-123';
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'does-not-exist',
			nonce: 'nonce-123',
		} );
		renderHook( () =>
			useUpdateRestAPIAuthorizeStatusByUrlQuery( nonceFromOptions )
		);
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 0 );
	} );
} );
