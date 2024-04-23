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
		fetchUpdateRestAPIAuthorize.mockRestore();
	} );

	it( 'should call fetchUpdateRestAPIAuthorize', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'approved',
			nonce: 'nonce-123',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'approved',
				nonce: 'nonce-123',
			},
		} );
	} );

	it( 'should print console error if fetchUpdateRestAPIAuthorize throws error', () => {
		fetchUpdateRestAPIAuthorize.mockImplementation( () => {
			throw new Error( 'Nonces mismatch' );
		} );
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'approved',
			nonce: 'nonce-123',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( consoleErrorSpy ).toHaveBeenCalledWith( 'Nonces mismatch' );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'approved',
				nonce: 'nonce-123',
			},
		} );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if query param is approved', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'approved',
			nonce: 'nonce-123',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'approved',
				nonce: 'nonce-123',
			},
		} );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if query param is disapproved', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'disapproved',
			nonce: 'nonce-123',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'disapproved',
				nonce: 'nonce-123',
			},
		} );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if query param is error', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'error',
			nonce: 'nonce-123',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledWith( {
			data: {
				status: 'error',
				nonce: 'nonce-123',
			},
		} );
	} );

	it( 'should not call fetchUpdateRestAPIAuthorize if query param is unknown', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'does-not-exist',
			nonce: 'nonce-123',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 0 );
	} );
} );
