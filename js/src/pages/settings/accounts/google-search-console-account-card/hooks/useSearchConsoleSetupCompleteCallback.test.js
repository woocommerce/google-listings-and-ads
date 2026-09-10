/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useSearchConsoleSetupCompleteCallback from './useSearchConsoleSetupCompleteCallback';

jest.mock( '~/data' );
jest.mock( '~/hooks/useApiFetchCallback' );

describe( 'useSearchConsoleSetupCompleteCallback', () => {
	let invalidateResolution;
	let fetchCompleteSetup;
	let fetchResult;

	beforeEach( () => {
		invalidateResolution = jest.fn();
		fetchCompleteSetup = jest.fn().mockResolvedValue( undefined );
		fetchResult = {
			loading: false,
			error: undefined,
		};

		useAppDispatch.mockReturnValue( { invalidateResolution } );
		useApiFetchCallback.mockReturnValue( [
			fetchCompleteSetup,
			fetchResult,
		] );
	} );

	it( 'completes setup and invalidates the Search Console account resolution', async () => {
		const { result } = renderHook( () =>
			useSearchConsoleSetupCompleteCallback()
		);

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( fetchCompleteSetup ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
	} );

	it( 'does not invalidate the resolution when the request fails', async () => {
		fetchCompleteSetup.mockRejectedValue( new Error( 'network error' ) );

		const { result } = renderHook( () =>
			useSearchConsoleSetupCompleteCallback()
		);

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( invalidateResolution ).not.toHaveBeenCalled();
	} );
} );
