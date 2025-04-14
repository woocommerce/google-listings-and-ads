/**
 * External dependencies
 */
import { renderHook, waitFor } from '@testing-library/react';
import { getQuery, getHistory } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useUpdateRestAPIAuthorizeStatusByUrlQuery from './useUpdateRestAPIAuthorizeStatusByUrlQuery';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

jest.mock( '@woocommerce/navigation' );
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn().mockName( 'recordEvent' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );

describe( 'useUpdateRestAPIAuthorizeStatusByUrlQuery', () => {
	let historyReplace;
	let fetchUpdateRestAPIAuthorize;
	let consoleErrorSpy;

	beforeEach( () => {
		recordEvent.mockClear();

		historyReplace = jest.fn().mockName( 'getHistory().replace' );
		getHistory.mockReturnValue( { replace: historyReplace } );

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

	it( 'should call fetchUpdateRestAPIAuthorize if query param is approved', async () => {
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
		await waitFor( () => {
			expect( historyReplace ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if query param is disapproved', async () => {
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
		await waitFor( () => {
			expect( historyReplace ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	it( 'should call fetchUpdateRestAPIAuthorize if query param is error', async () => {
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
		await waitFor( () => {
			expect( historyReplace ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	it( 'should not call fetchUpdateRestAPIAuthorize if query param is unknown', () => {
		getQuery.mockReturnValue( {
			google_wpcom_app_status: 'does-not-exist',
			nonce: 'nonce-123',
		} );
		renderHook( () => useUpdateRestAPIAuthorizeStatusByUrlQuery() );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 0 );
		expect( recordEvent ).toHaveBeenCalledTimes( 0 );
	} );

	it.each( [ 'approved', 'disapproved', 'error' ] )(
		"Should record an event if the auth status is '%s'",
		( status ) => {
			getQuery.mockReturnValue( {
				google_wpcom_app_status: status,
				nonce: 'nonce-123',
			} );

			renderHook( () =>
				useUpdateRestAPIAuthorizeStatusByUrlQuery( 'jest-testing' )
			);

			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( recordEvent ).toHaveBeenCalledWith(
				'gla_product_sync_status_callback',
				{ page: 'jest-testing', status }
			);
		}
	);

	it( 'Should only call API one time', async () => {
		getQuery.mockClear().mockReturnValue( {
			google_wpcom_app_status: 'approved',
			nonce: 'nonce-123',
		} );

		expect( fetchUpdateRestAPIAuthorize ).not.toHaveBeenCalled();

		const hook = renderHook( ( page ) =>
			useUpdateRestAPIAuthorizeStatusByUrlQuery( page )
		);

		hook.rerender();
		hook.rerender( 'jest-testing' );
		hook.rerender();

		await waitFor( () => expect( getQuery ).toHaveBeenCalledTimes( 4 ) );
		expect( fetchUpdateRestAPIAuthorize ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
	} );
} );
