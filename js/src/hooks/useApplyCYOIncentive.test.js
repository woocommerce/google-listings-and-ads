/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useApplyCYOIncentive from './useApplyCYOIncentive';
import useGoogleAdsAccountBillingStatus from './useGoogleAdsAccountBillingStatus';
import useCYOIncentives from './useCYOIncentives';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';

jest.mock( '@wordpress/api-fetch' );
jest.mock( './useGoogleAdsAccountBillingStatus' );
jest.mock( './useCYOIncentives' );

describe( 'useApplyCYOIncentive', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		apiFetch.mockResolvedValue( {} );
		useCYOIncentives.mockReturnValue( { data: [] } );
	} );

	it( 'exposes result with loading state', async () => {
		let resolveApiFetch;
		const fetchPromise = new Promise( ( resolve ) => {
			resolveApiFetch = resolve;
		} );
		apiFetch.mockReturnValue( fetchPromise );
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

		it( 'calls apiFetch with the correct path, method, and incentive id', async () => {
			const { result } = renderHook( () => useApplyCYOIncentive() );

			await act( () => result.current.applyIncentive( 'incentive-123' ) );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: `${ API_NAMESPACE }/ads/incentives`,
				method: 'POST',
				data: { id: 'incentive-123' },
			} );
		} );

		it( 'returns false when no matching incentive offer is found', async () => {
			const { result } = renderHook( () => useApplyCYOIncentive() );

			const returnValue = await act( () =>
				result.current.applyIncentive( undefined )
			);

			expect( returnValue ).toBe( false );
			expect( apiFetch ).not.toHaveBeenCalled();
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
			expect( apiFetch ).not.toHaveBeenCalled();
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
			expect( apiFetch ).not.toHaveBeenCalled();
		} );

		it( 'swallows API errors and returns false', async () => {
			apiFetch.mockRejectedValue( new Error( 'API error' ) );

			const { result } = renderHook( () => useApplyCYOIncentive() );

			const returnValue = await act( () =>
				result.current.applyIncentive( 'incentive-123' )
			);

			expect( returnValue ).toBe( false );
		} );
	} );
} );
