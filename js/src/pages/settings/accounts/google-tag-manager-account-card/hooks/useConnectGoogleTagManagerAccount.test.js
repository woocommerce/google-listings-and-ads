/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useConnectGoogleTagManagerAccount from './useConnectGoogleTagManagerAccount';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );

describe( 'useConnectGoogleTagManagerAccount', () => {
	let fetchConnect;
	let createNotice;
	let fetchGoogleTagManagerAccount;

	beforeEach( () => {
		fetchConnect = jest.fn().mockName( 'fetchConnect' );
		useApiFetchCallback.mockReturnValue( [
			fetchConnect,
			{ loading: false },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		fetchGoogleTagManagerAccount = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerAccount' );
		useAppDispatch.mockReturnValue( { fetchGoogleTagManagerAccount } );
	} );

	it( 'requests the connect endpoint with the picked account ID and refetches only the connection', async () => {
		fetchConnect.mockResolvedValue( undefined );

		const { result } = renderHook( () =>
			useConnectGoogleTagManagerAccount()
		);

		await act( async () => {
			await result.current.connect( '6002847391' );
		} );

		expect( fetchConnect ).toHaveBeenCalledWith( '6002847391' );
		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'shows a generic error notice when the request fails', async () => {
		fetchConnect.mockRejectedValue( new Error( 'failed' ) );

		const { result } = renderHook( () =>
			useConnectGoogleTagManagerAccount()
		);

		await act( async () => {
			await result.current.connect( '6002847391' );
		} );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to connect' )
		);
	} );
} );
