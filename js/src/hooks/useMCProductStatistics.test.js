/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useMCProductStatistics from '.~/hooks/useMCProductStatistics';
import useCountdown from '.~/hooks/useCountdown';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import { useAppDispatch } from '.~/data';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';

jest.mock( '.~/hooks/useAppSelectDispatch' );
jest.mock( '.~/hooks/useCountdown' );
jest.mock( '.~/data' );
jest.mock( '.~/hooks/useApiFetchCallback', () => ( {
	__esModule: true,
	default: jest.fn().mockImplementation( () => {
		return [ jest.fn(), null ];
	} ),
} ) );

describe( 'useMCProductStatistics', () => {
	const startCountdown = jest.fn();
	const invalidateResolution = jest.fn();
	const invalidateResolutionForStoreSelector = jest.fn();

	useAppDispatch.mockImplementation( () => {
		return {
			invalidateResolutionForStoreSelector,
		};
	} );

	const statsData = {
		loading: false,
		statistics: {
			active: 10,
			expiring: 20,
			pending: 30,
			disapproved: 2132,
			not_synced: 1074,
		},
		error: null,
	};

	describe( 'The update_merchant_product_statuses job is completed', () => {
		beforeAll( () => {
			jest.clearAllMocks();
		} );

		it( 'The response contains the statistics', () => {
			useCountdown.mockImplementation( () => {
				return {
					second: 0,
					callCount: 0,
					startCountdown,
				};
			} );

			useAppSelectDispatch.mockImplementation( () => {
				return {
					hasFinishedResolution: true,
					invalidateResolution,
					data: statsData,
				};
			} );

			const { result } = renderHook( () => useMCProductStatistics() );

			expect( startCountdown ).not.toHaveBeenCalled();
			expect( invalidateResolution ).not.toHaveBeenCalled();
			expect( result.current.data ).toEqual( statsData );
			expect( result.current.hasFinishedResolution ).toEqual( true );
		} );
	} );

	describe( 'Retry two times until the update_merchant_product_statuses job is completed', () => {
		beforeAll( () => {
			jest.clearAllMocks();

			useCountdown.mockImplementation( () => {
				return {
					second: 0,
					callCount: 0,
					startCountdown,
				};
			} );
		} );
		it( "The first time the job is in progress/scheduled and the response doesn't contains statistics", () => {
			useAppSelectDispatch.mockImplementation( () => {
				return {
					hasFinishedResolution: true,
					invalidateResolution,
					data: {
						loading: true,
						statistics: null,
						error: null,
					},
				};
			} );

			renderHook( () => useMCProductStatistics() );

			expect( startCountdown ).toHaveBeenCalledTimes( 1 );
			expect( startCountdown ).toHaveBeenCalledWith( 15 );
			expect( invalidateResolution ).not.toHaveBeenCalled();
		} );
		it( 'The countdown is completed, so we invalidate the call and try again', () => {
			useCountdown.mockImplementation( () => {
				return {
					second: 0,
					callCount: 1,
					startCountdown,
				};
			} );

			renderHook( () => useMCProductStatistics() );

			expect( startCountdown ).toHaveBeenCalledTimes( 2 );
			expect( startCountdown ).toHaveBeenCalledWith( 15 );
			expect( invalidateResolution ).toHaveBeenCalledTimes( 1 );
			expect( invalidateResolution ).toHaveBeenCalledWith();
		} );
		it( 'The third time the job is completed and the response contains the statistics', () => {
			useAppSelectDispatch.mockImplementation( () => {
				return {
					hasFinishedResolution: true,
					invalidateResolution,
					data: statsData,
				};
			} );

			renderHook( () => useMCProductStatistics() );

			expect( startCountdown ).toHaveBeenCalledTimes( 3 );
			expect( startCountdown ).toHaveBeenLastCalledWith( 0 );
			expect( invalidateResolution ).toHaveBeenCalledTimes( 1 );
			expect( invalidateResolution ).toHaveBeenCalledWith();
			expect(
				invalidateResolutionForStoreSelector
			).toHaveBeenCalledTimes( 1 );
			expect( invalidateResolutionForStoreSelector ).toHaveBeenCalledWith(
				'getMCProductFeed'
			);
		} );
	} );
	describe( 'Refresh the product statistics', () => {
		beforeAll( () => {
			jest.clearAllMocks();
		} );
		it( 'The user clicks the refresh button', async () => {
			const refreshProductStats = jest.fn();
			useCountdown.mockImplementation( () => {
				return {
					second: 0,
					callCount: 0,
					startCountdown,
				};
			} );

			useAppSelectDispatch.mockImplementation( () => {
				return {
					hasFinishedResolution: true,
					invalidateResolution,
					data: statsData,
				};
			} );

			useApiFetchCallback.mockImplementation( () => {
				return [ refreshProductStats, null ];
			} );

			const { result } = renderHook( () => useMCProductStatistics() );

			await result.current.refreshStats();

			expect( refreshProductStats ).toHaveBeenCalledTimes( 1 );
			expect( invalidateResolution ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
