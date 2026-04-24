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
import useCYOIncentives from './useCYOIncentives';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';

jest.mock( '~/hooks/useApiFetchCallback' );
jest.mock( './useGoogleAdsAccountBillingStatus' );
jest.mock( './useCYOIncentives' );

describe( 'useApplyCYOIncentive', () => {
	let fetchApplyIncentive;

	beforeEach( () => {
		jest.clearAllMocks();
		fetchApplyIncentive = jest.fn().mockName( 'fetchApplyIncentive' );
		useApiFetchCallback.mockReturnValue( [ fetchApplyIncentive, {} ] );
		useCYOIncentives.mockReturnValue( { data: [] } );
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

		it( 'delegates to redeemIncentive when there is no prior error', async () => {
			useApiFetchCallback.mockReturnValue( [
				fetchApplyIncentive,
				{ error: null },
			] );
			const { result } = renderHook( () => useApplyCYOIncentive() );

			await result.current.applyIncentive( 'incentive-123' );

			expect( fetchApplyIncentive ).toHaveBeenCalledWith( {
				data: { id: 'incentive-123' },
			} );
		} );

		it( 'returns false without fetching when there is a prior error', async () => {
			useApiFetchCallback.mockReturnValue( [
				fetchApplyIncentive,
				{ error: new Error( 'prior error' ) },
			] );
			const { result } = renderHook( () => useApplyCYOIncentive() );

			const returnValue =
				await result.current.applyIncentive( 'incentive-123' );

			expect( returnValue ).toBe( false );
			expect( fetchApplyIncentive ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'redeemIncentive', () => {
		describe( 'when it should skip applying the incentive', () => {
			it( 'returns false when no matching incentive offer is found', async () => {
				useGoogleAdsAccountBillingStatus.mockReturnValue( {
					billingStatus: {
						status: GOOGLE_ADS_BILLING_STATUS.APPROVED,
					},
				} );

				const { result } = renderHook( () => useApplyCYOIncentive() );
				const returnValue =
					await result.current.redeemIncentive( undefined );

				expect( returnValue ).toBe( false );
				expect( fetchApplyIncentive ).not.toHaveBeenCalled();
			} );

			it( 'returns false when billing status is not yet loaded', async () => {
				useGoogleAdsAccountBillingStatus.mockReturnValue( {
					billingStatus: undefined,
				} );

				const { result } = renderHook( () => useApplyCYOIncentive() );
				const returnValue =
					await result.current.redeemIncentive( 'incentive-123' );

				expect( returnValue ).toBe( false );
				expect( fetchApplyIncentive ).not.toHaveBeenCalled();
			} );

			it( 'returns false when billing is not approved', async () => {
				useGoogleAdsAccountBillingStatus.mockReturnValue( {
					billingStatus: { status: 'pending' },
				} );

				const { result } = renderHook( () => useApplyCYOIncentive() );
				const returnValue =
					await result.current.redeemIncentive( 'incentive-123' );

				expect( returnValue ).toBe( false );
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
				useCYOIncentives.mockReturnValue( {
					data: [ { offer: 'incentive-123', id: 'incentive-123' } ],
				} );
			} );

			it( 'calls fetchApplyIncentive with the incentive id', async () => {
				const { result } = renderHook( () => useApplyCYOIncentive() );

				await result.current.redeemIncentive( 'incentive-123' );

				expect( fetchApplyIncentive ).toHaveBeenCalledWith( {
					data: { id: 'incentive-123' },
				} );
			} );

			it( 'propagates errors thrown by fetchApplyIncentive', async () => {
				const error = new Error( 'API error' );
				fetchApplyIncentive.mockRejectedValue( error );

				const { result } = renderHook( () => useApplyCYOIncentive() );

				await expect(
					result.current.redeemIncentive( 'incentive-123' )
				).rejects.toThrow( 'API error' );
			} );

			it( 'does not call fetchApplyIncentive again after successful application', async () => {
				const { result } = renderHook( () => useApplyCYOIncentive() );

				await result.current.redeemIncentive( 'incentive-123' );
				const returnValue =
					await result.current.redeemIncentive( 'incentive-123' );

				expect( fetchApplyIncentive ).toHaveBeenCalledTimes( 1 );
				expect( returnValue ).toBe( true );
			} );

			it( 'allows retry after a failed application', async () => {
				const error = new Error( 'API error' );
				fetchApplyIncentive
					.mockRejectedValueOnce( error )
					.mockResolvedValueOnce( {} );

				const { result } = renderHook( () => useApplyCYOIncentive() );

				await expect(
					result.current.redeemIncentive( 'incentive-123' )
				).rejects.toThrow( 'API error' );

				await result.current.redeemIncentive( 'incentive-123' );

				expect( fetchApplyIncentive ).toHaveBeenCalledTimes( 2 );
			} );
		} );
	} );
} );
