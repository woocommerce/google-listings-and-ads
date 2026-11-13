/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useApplyCYOIncentive from './useApplyCYOIncentive';
import useGoogleAdsAccountBillingStatus from './useGoogleAdsAccountBillingStatus';
import useCYOIncentives from './useCYOIncentives';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { useAppDispatch } from '~/data';

jest.mock( './useGoogleAdsAccountBillingStatus' );
jest.mock( './useCYOIncentives' );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );

describe( 'useApplyCYOIncentive', () => {
	const applyCYOIncentive = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		applyCYOIncentive.mockResolvedValue( {} );
		useCYOIncentives.mockReturnValue( { data: [] } );
		useAppDispatch.mockReturnValue( { applyCYOIncentive } );
	} );

	it( 'exposes result with loading state', async () => {
		let resolveApiFetch;
		const fetchPromise = new Promise( ( resolve ) => {
			resolveApiFetch = resolve;
		} );
		applyCYOIncentive.mockReturnValue( fetchPromise );
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: { status: GOOGLE_ADS_BILLING_STATUS.APPROVED },
		} );
		useCYOIncentives.mockReturnValue( {
			data: [ { offer: 'incentive-123', id: 'incentive-123' } ],
		} );

		const { result } = renderHook( () => useApplyCYOIncentive() );

		expect( result.current.loading ).toBe( false );

		act( () => {
			result.current.applyIncentive( 'incentive-123' );
		} );
		expect( result.current.loading ).toBe( true );

		await act( async () => {
			resolveApiFetch( {} );
			await fetchPromise;
		} );
		expect( result.current.loading ).toBe( false );
	} );

	describe( 'applyIncentive', () => {
		beforeEach( () => {
			useGoogleAdsAccountBillingStatus.mockReturnValue( {
				billingStatus: {
					status: GOOGLE_ADS_BILLING_STATUS.APPROVED,
				},
			} );
			useCYOIncentives.mockReturnValue( {
				data: [ { offer: 'incentive-123', id: 'incentive-123' } ],
			} );
		} );

		it( 'applies the offer with its incentive id', async () => {
			const { result } = renderHook( () => useApplyCYOIncentive() );

			await act( () => result.current.applyIncentive( 'incentive-123' ) );

			expect( applyCYOIncentive ).toHaveBeenCalledWith( 'incentive-123' );
		} );

		it( 'returns false when no matching incentive offer is found', async () => {
			const { result } = renderHook( () => useApplyCYOIncentive() );

			const returnValue = await act( () =>
				result.current.applyIncentive( undefined )
			);

			expect( returnValue ).toBe( false );
			expect( applyCYOIncentive ).not.toHaveBeenCalled();
		} );

		it( 'returns false when billing status is not yet loaded', async () => {
			useGoogleAdsAccountBillingStatus.mockReturnValue( {
				billingStatus: undefined,
			} );

			const { result } = renderHook( () => useApplyCYOIncentive() );

			const returnValue = await act( () =>
				result.current.applyIncentive( 'incentive-123' )
			);

			expect( returnValue ).toBe( false );
			expect( applyCYOIncentive ).not.toHaveBeenCalled();
		} );

		it( 'returns false when billing is not approved', async () => {
			useGoogleAdsAccountBillingStatus.mockReturnValue( {
				billingStatus: { status: 'pending' },
			} );

			const { result } = renderHook( () => useApplyCYOIncentive() );

			const returnValue = await act( () =>
				result.current.applyIncentive( 'incentive-123' )
			);

			expect( returnValue ).toBe( false );
			expect( applyCYOIncentive ).not.toHaveBeenCalled();
		} );

		it( 'returns false when an offer was already applied', async () => {
			useCYOIncentives.mockReturnValue( {
				data: [ { offer: 'incentive-123', id: 'incentive-123' } ],
				hasAppliedIncentive: true,
			} );

			const { result } = renderHook( () => useApplyCYOIncentive() );

			const returnValue = await act( () =>
				result.current.applyIncentive( 'incentive-123' )
			);

			expect( returnValue ).toBe( false );
			expect( applyCYOIncentive ).not.toHaveBeenCalled();
		} );

		it( 'returns true on success', async () => {
			const { result } = renderHook( () => useApplyCYOIncentive() );

			const returnValue = await act( () =>
				result.current.applyIncentive( 'incentive-123' )
			);

			expect( returnValue ).toBe( true );
			expect( applyCYOIncentive ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'swallows API errors and returns false', async () => {
			applyCYOIncentive.mockRejectedValue( new Error( 'API error' ) );

			const { result } = renderHook( () => useApplyCYOIncentive() );

			const returnValue = await act( () =>
				result.current.applyIncentive( 'incentive-123' )
			);

			expect( returnValue ).toBe( false );
		} );
	} );
} );
