/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useSearchConsoleReconnectConfirmation from './useSearchConsoleReconnectConfirmation';

jest.mock( '~/data' );
jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( '@woocommerce/navigation' );

describe( 'useSearchConsoleReconnectConfirmation', () => {
	let invalidateResolution;
	let fetchConfirmReconnect;
	let historyReplace;

	beforeEach( () => {
		invalidateResolution = jest.fn();
		fetchConfirmReconnect = jest.fn().mockResolvedValue( undefined );
		historyReplace = jest.fn();

		useAppDispatch.mockReturnValue( { invalidateResolution } );
		useApiFetchCallback.mockReturnValue( [
			fetchConfirmReconnect,
			{ loading: false, error: undefined },
		] );
		getHistory.mockReturnValue( { replace: historyReplace } );
		getNewPath.mockReturnValue( '/new-path' );
	} );

	it( 'confirms the reconnect, invalidates the account resolution, and cleans up the URL', async () => {
		const { result } = renderHook( () =>
			useSearchConsoleReconnectConfirmation()
		);

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( fetchConfirmReconnect ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
		expect( getNewPath ).toHaveBeenCalledWith( { 'google-mc': undefined } );
		expect( historyReplace ).toHaveBeenCalledWith( '/new-path' );
	} );

	it( 'does not invalidate the resolution or touch the URL when the confirm request fails', async () => {
		fetchConfirmReconnect.mockRejectedValue( new Error( 'network error' ) );

		const { result } = renderHook( () =>
			useSearchConsoleReconnectConfirmation()
		);

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( invalidateResolution ).not.toHaveBeenCalled();
		expect( historyReplace ).not.toHaveBeenCalled();
	} );
} );
