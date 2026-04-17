/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useApplyCYOIncentive from './useApplyCYOIncentive';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleAdsAccountBillingStatus from './useGoogleAdsAccountBillingStatus';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( './useGoogleAdsAccountBillingStatus' );

describe( 'useApplyCYOIncentive', () => {
	let fetchApplyIncentive;

	beforeEach( () => {
		jest.clearAllMocks();
		fetchApplyIncentive = jest.fn().mockName( 'fetchApplyIncentive' );
		useApiFetchCallback.mockReturnValue( [ fetchApplyIncentive, {} ] );
	} );

	it( 'initializes useApiFetchCallback with the correct path and method', () => {
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: undefined,
		} );

		renderHook( () => useApplyCYOIncentive() );

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/ads/incentive`,
			method: 'POST',
		} );
	} );

	it( 'exposes result from useApiFetchCallback', () => {
		const mockResult = { loading: false, error: null };
		useApiFetchCallback.mockReturnValue( [
			fetchApplyIncentive,
			mockResult,
		] );
		useGoogleAdsAccountBillingStatus.mockReturnValue( {
			billingStatus: undefined,
		} );

		const { result } = renderHook( () => useApplyCYOIncentive() );

		expect( result.current.result ).toBe( mockResult );
	} );

	describe( 'handleApplyIncentive', () => {
		describe( 'when it should skip applying the incentive', () => {
			it( 'returns true without fetching when incentiveId is falsy', async () => {
				useGoogleAdsAccountBillingStatus.mockReturnValue( {
					billingStatus: {
						status: GOOGLE_ADS_BILLING_STATUS.APPROVED,
					},
				} );

				const { result } = renderHook( () => useApplyCYOIncentive() );
				const returnValue =
					await result.current.handleApplyIncentive( undefined );

				expect( returnValue ).toBe( true );
				expect( fetchApplyIncentive ).not.toHaveBeenCalled();
			} );

			it( 'returns true without fetching when billing status is not yet loaded', async () => {
				useGoogleAdsAccountBillingStatus.mockReturnValue( {
					billingStatus: undefined,
				} );

				const { result } = renderHook( () => useApplyCYOIncentive() );
				const returnValue =
					await result.current.handleApplyIncentive(
						'incentive-123'
					);

				expect( returnValue ).toBe( true );
				expect( fetchApplyIncentive ).not.toHaveBeenCalled();
			} );

			it( 'returns true without fetching when billing is not approved', async () => {
				useGoogleAdsAccountBillingStatus.mockReturnValue( {
					billingStatus: { status: 'pending' },
				} );

				const { result } = renderHook( () => useApplyCYOIncentive() );
				const returnValue =
					await result.current.handleApplyIncentive(
						'incentive-123'
					);

				expect( returnValue ).toBe( true );
				expect( fetchApplyIncentive ).not.toHaveBeenCalled();
			} );
		} );

		describe( 'when billing is approved and incentiveId is provided', () => {
			beforeEach( () => {
				useGoogleAdsAccountBillingStatus.mockReturnValue( {
					billingStatus: {
						status: GOOGLE_ADS_BILLING_STATUS.APPROVED,
					},
				} );
			} );

			it( 'calls fetchApplyIncentive with the incentive id', async () => {
				const { result } = renderHook( () => useApplyCYOIncentive() );

				await result.current.handleApplyIncentive( 'incentive-123' );

				expect( fetchApplyIncentive ).toHaveBeenCalledWith( {
					data: { id: 'incentive-123' },
				} );
			} );

			it( 'propagates errors thrown by fetchApplyIncentive', async () => {
				const error = new Error( 'API error' );
				fetchApplyIncentive.mockRejectedValue( error );

				const { result } = renderHook( () => useApplyCYOIncentive() );

				await expect(
					result.current.handleApplyIncentive( 'incentive-123' )
				).rejects.toThrow( 'API error' );
			} );
		} );
	} );
} );
