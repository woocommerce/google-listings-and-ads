/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useSearchConsoleConnectRedirect from './useSearchConsoleConnectRedirect';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

describe( 'useSearchConsoleConnectRedirect', () => {
	let fetchSearchConsoleConnect;
	let createNotice;

	beforeEach( () => {
		fetchSearchConsoleConnect = jest
			.fn()
			.mockName( 'fetchSearchConsoleConnect' );
		useApiFetchCallback.mockReturnValue( [
			fetchSearchConsoleConnect,
			{ loading: false, data: undefined },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		delete window.location;
		window.location = { href: '' };
	} );

	it( 'requests a connect URL at the plain connect path by default', () => {
		renderHook( () => useSearchConsoleConnectRedirect() );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/search-console/connect',
		} );
	} );

	it( 'appends the given query args to the connect path', () => {
		renderHook( () =>
			useSearchConsoleConnectRedirect( {
				next_page_name: 'setup-search-console',
			} )
		);

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/search-console/connect?next_page_name=setup-search-console',
		} );
	} );

	it( 'redirects the browser to the returned URL on success', async () => {
		fetchSearchConsoleConnect.mockResolvedValue( {
			url: 'https://accounts.google.com/o/oauth2/auth',
		} );

		const { result } = renderHook( () =>
			useSearchConsoleConnectRedirect()
		);

		await act( async () => {
			await result.current.connect();
		} );

		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'shows a generic error notice when the request fails', async () => {
		fetchSearchConsoleConnect.mockRejectedValue( new Error( 'failed' ) );

		const { result } = renderHook( () =>
			useSearchConsoleConnectRedirect()
		);

		await act( async () => {
			await result.current.connect();
		} );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to connect' )
		);
	} );

	it( 'reports loading while the request is in flight or has just resolved', () => {
		useApiFetchCallback.mockReturnValue( [
			fetchSearchConsoleConnect,
			{ loading: false, data: { url: 'https://example.com/' } },
		] );

		const { result } = renderHook( () =>
			useSearchConsoleConnectRedirect()
		);

		expect( result.current.loading ).toBeTruthy();
	} );
} );
