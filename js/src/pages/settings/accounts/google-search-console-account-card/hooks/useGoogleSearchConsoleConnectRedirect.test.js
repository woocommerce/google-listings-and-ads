/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleSearchConsoleConnectRedirect from './useGoogleSearchConsoleConnectRedirect';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

describe( 'useGoogleSearchConsoleConnectRedirect', () => {
	let fetchGoogleSearchConsoleConnect;
	let createNotice;

	beforeEach( () => {
		fetchGoogleSearchConsoleConnect = jest
			.fn()
			.mockName( 'fetchGoogleSearchConsoleConnect' );
		useApiFetchCallback.mockReturnValue( [
			fetchGoogleSearchConsoleConnect,
			{ loading: false, data: undefined },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		delete window.location;
		window.location = { href: '' };
	} );

	it( 'requests the connect URL with no query params', () => {
		renderHook( () => useGoogleSearchConsoleConnectRedirect() );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/search-console/connect',
		} );
	} );

	it( 'redirects the browser to the returned URL on success', async () => {
		fetchGoogleSearchConsoleConnect.mockResolvedValue( {
			url: 'https://accounts.google.com/o/oauth2/auth',
		} );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleConnectRedirect()
		);

		await act( async () => {
			await result.current.connect();
		} );

		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'shows a generic error notice when the request fails', async () => {
		fetchGoogleSearchConsoleConnect.mockRejectedValue(
			new Error( 'failed' )
		);

		const { result } = renderHook( () =>
			useGoogleSearchConsoleConnectRedirect()
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
			fetchGoogleSearchConsoleConnect,
			{ loading: false, data: { url: 'https://example.com/' } },
		] );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleConnectRedirect()
		);

		expect( result.current.loading ).toBeTruthy();
	} );
} );
