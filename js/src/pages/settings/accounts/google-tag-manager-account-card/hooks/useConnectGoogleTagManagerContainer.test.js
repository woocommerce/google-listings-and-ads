/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useConnectGoogleTagManagerContainer from './useConnectGoogleTagManagerContainer';
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

describe( 'useConnectGoogleTagManagerContainer', () => {
	let fetchSelectContainer;
	let createNotice;
	let fetchGoogleTagManagerAccount;

	beforeEach( () => {
		fetchSelectContainer = jest.fn().mockName( 'fetchSelectContainer' );
		useApiFetchCallback.mockReturnValue( [
			fetchSelectContainer,
			{ loading: false },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		fetchGoogleTagManagerAccount = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerAccount' );
		useAppDispatch.mockReturnValue( { fetchGoogleTagManagerAccount } );
	} );

	it( 'requests the container endpoint with the picked container ID and refetches only the connection', async () => {
		fetchSelectContainer.mockResolvedValue( undefined );

		const { result } = renderHook( () =>
			useConnectGoogleTagManagerContainer()
		);

		await act( async () => {
			await result.current.selectContainer( '98765432' );
		} );

		expect( fetchSelectContainer ).toHaveBeenCalledWith( '98765432' );
		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'shows a generic error notice when the request fails', async () => {
		fetchSelectContainer.mockRejectedValue( new Error( 'failed' ) );

		const { result } = renderHook( () =>
			useConnectGoogleTagManagerContainer()
		);

		await act( async () => {
			await result.current.selectContainer( '98765432' );
		} );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to select' )
		);
	} );
} );
