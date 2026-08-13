/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useVerifySearchConsoleProperty from './useVerifySearchConsoleProperty';
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
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

describe( 'useVerifySearchConsoleProperty', () => {
	let fetchVerify;
	let createNotice;
	let invalidateResolution;

	beforeEach( () => {
		fetchVerify = jest.fn().mockName( 'fetchVerify' );
		useApiFetchCallback.mockReturnValue( [
			fetchVerify,
			{ loading: false },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useAppDispatch.mockReturnValue( { invalidateResolution } );
	} );

	it( 'POSTs to the verify endpoint', () => {
		renderHook( () => useVerifySearchConsoleProperty() );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/search-console/verify',
			method: 'POST',
		} );
	} );

	it( 'invalidates the account resolution after a successful verify', async () => {
		fetchVerify.mockResolvedValue( {} );

		const { result } = renderHook( () => useVerifySearchConsoleProperty() );

		await act( async () => {
			await result.current.onClick();
		} );

		expect( fetchVerify ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getSearchConsoleAccount',
			[]
		);
	} );

	it( 'shows an error notice when the verify request fails', async () => {
		fetchVerify.mockRejectedValue( new Error( 'failed' ) );

		const { result } = renderHook( () => useVerifySearchConsoleProperty() );

		await act( async () => {
			await result.current.onClick();
		} );

		expect( invalidateResolution ).not.toHaveBeenCalled();
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'Unable to verify' )
		);
	} );

	it( 'reports the loading state from the underlying fetch', () => {
		useApiFetchCallback.mockReturnValue( [
			fetchVerify,
			{ loading: true },
		] );

		const { result } = renderHook( () => useVerifySearchConsoleProperty() );

		expect( result.current.loading ).toBe( true );
	} );
} );
