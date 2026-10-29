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
import useGoogleAccount from '~/hooks/useGoogleAccount';
import { redirectTo } from '~/utils/urls';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

jest.mock( '~/utils/urls', () => ( {
	redirectTo: jest.fn().mockName( 'redirectTo' ),
} ) );

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

		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
	} );

	it( 'requests the connect URL with the connected account email as login_hint', () => {
		renderHook( () => useGoogleSearchConsoleConnectRedirect() );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/search-console/connect?login_hint=merchant%40example.com',
		} );
	} );

	it( 'omits login_hint when the connected account email is not yet known', () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		renderHook( () => useGoogleSearchConsoleConnectRedirect() );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/search-console/connect?',
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

		expect( redirectTo ).toHaveBeenCalledWith(
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
