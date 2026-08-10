/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useSearchConsoleConnectRedirect from './useSearchConsoleConnectRedirect';
import useApiFetchCallback from './useApiFetchCallback';
import useDispatchCoreNotices from './useDispatchCoreNotices';

jest.mock( './useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( './useDispatchCoreNotices', () =>
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

	it( 'requests a connect URL at the plain connect path when no query is given', () => {
		renderHook( () => useSearchConsoleConnectRedirect( 'error message' ) );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/search-console/connect',
		} );
	} );

	it( 'appends the given query args to the connect path', () => {
		renderHook( () =>
			useSearchConsoleConnectRedirect( 'error message', {
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
			useSearchConsoleConnectRedirect( 'error message' )
		);

		await act( async () => {
			await result.current.onClick();
		} );

		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'shows the given error notice when the request fails', async () => {
		fetchSearchConsoleConnect.mockRejectedValue( new Error( 'failed' ) );

		const { result } = renderHook( () =>
			useSearchConsoleConnectRedirect( 'Custom error message' )
		);

		await act( async () => {
			await result.current.onClick();
		} );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			'Custom error message'
		);
	} );

	it( 'reports loading while the request is in flight or has just resolved', () => {
		useApiFetchCallback.mockReturnValue( [
			fetchSearchConsoleConnect,
			{ loading: false, data: { url: 'https://example.com/' } },
		] );

		const { result } = renderHook( () =>
			useSearchConsoleConnectRedirect( 'error message' )
		);

		expect( result.current.loading ).toBeTruthy();
	} );
} );
