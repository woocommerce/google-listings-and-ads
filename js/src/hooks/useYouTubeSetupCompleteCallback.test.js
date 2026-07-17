/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useYouTubeSetupCompleteCallback from './useYouTubeSetupCompleteCallback';

jest.mock( '~/data' );
jest.mock( '~/hooks/useApiFetchCallback' );

describe( 'useYouTubeSetupCompleteCallback', () => {
	let invalidateResolution;
	let fetchCompleteYouTubeSetup;
	let fetchResult;

	beforeEach( () => {
		invalidateResolution = jest.fn();
		fetchCompleteYouTubeSetup = jest.fn().mockResolvedValue( undefined );
		fetchResult = {
			loading: false,
			error: undefined,
		};

		useAppDispatch.mockReturnValue( {
			invalidateResolution,
		} );
		useApiFetchCallback.mockReturnValue( [
			fetchCompleteYouTubeSetup,
			fetchResult,
		] );
	} );

	it( 'returns a stable completion callback across rerenders', () => {
		const { result, rerender } = renderHook( () =>
			useYouTubeSetupCompleteCallback()
		);

		const firstHandleFinishSetup = result.current[ 0 ];

		rerender();

		expect( result.current[ 0 ] ).toBe( firstHandleFinishSetup );
	} );

	it( 'completes setup and invalidates the YouTube account resolution', async () => {
		const { result } = renderHook( () =>
			useYouTubeSetupCompleteCallback()
		);

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( fetchCompleteYouTubeSetup ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getYouTubeAccount',
			[]
		);
	} );
} );
