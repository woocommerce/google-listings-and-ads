/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleTagManagerConnectRedirect from './useGoogleTagManagerConnectRedirect';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);

jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);

describe( 'useGoogleTagManagerConnectRedirect', () => {
	let fetchGoogleTagManagerConnect;
	let createNotice;

	beforeEach( () => {
		fetchGoogleTagManagerConnect = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerConnect' );
		useApiFetchCallback.mockReturnValue( [
			fetchGoogleTagManagerConnect,
			{ loading: false, data: undefined },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		delete window.location;
		window.location = { href: '' };
	} );

	it( 'requests the connect URL with no query params', () => {
		renderHook( () => useGoogleTagManagerConnectRedirect() );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/tag-manager/connect',
		} );
	} );

	it( 'redirects the browser to the returned URL on success', async () => {
		fetchGoogleTagManagerConnect.mockResolvedValue( {
			url: 'https://accounts.google.com/o/oauth2/auth',
		} );

		const { result } = renderHook( () =>
			useGoogleTagManagerConnectRedirect()
		);

		await act( async () => {
			await result.current.connect();
		} );

		expect( window.location.href ).toBe(
			'https://accounts.google.com/o/oauth2/auth'
		);
	} );

	it( 'shows a generic error notice when the request fails', async () => {
		fetchGoogleTagManagerConnect.mockRejectedValue( new Error( 'failed' ) );

		const { result } = renderHook( () =>
			useGoogleTagManagerConnectRedirect()
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
			fetchGoogleTagManagerConnect,
			{ loading: false, data: { url: 'https://example.com/' } },
		] );

		const { result } = renderHook( () =>
			useGoogleTagManagerConnectRedirect()
		);

		expect( result.current.loading ).toBeTruthy();
	} );
} );
