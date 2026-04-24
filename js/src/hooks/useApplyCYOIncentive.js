/**
 * External dependencies
 */
import { useCallback, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useGoogleAdsAccountBillingStatus from './useGoogleAdsAccountBillingStatus';

const useApplyCYOIncentive = () => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const [ fetchApplyIncentive, result ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/ads/incentive`,
		method: 'POST',
	} );
	const appliedRef = useRef( false );

	/**
	 * Makes the API request to redeem the incentive. Skips silently if already
	 * redeemed, no incentive ID is provided, or billing is not yet approved.
	 * Use this for explicit retry attempts where a fresh API call is always intended.
	 */
	const redeemIncentive = useCallback(
		async ( incentiveId ) => {
			if ( appliedRef.current ) {
				return true;
			}

			const isBillingCompleted =
				billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

			if ( ! incentiveId || ! isBillingCompleted ) {
				return true;
			}

			await fetchApplyIncentive( { data: { id: incentiveId } } );
			appliedRef.current = true;
			return true;
		},
		[ billingStatus, fetchApplyIncentive ]
	);

	/**
	 * Wraps `redeemIncentive` for use in the normal onboarding flow (skip/continue).
	 * If a previous redemption attempt errored, proceeds without retrying so the
	 * merchant is not blocked — they can retry via the dedicated retry action.
	 */
	const applyIncentive = useCallback(
		async ( incentiveId ) => {
			if ( result.error ) {
				// Proceed with onboarding since merchant can retry and proceed without the incentive.
				return true;
			}

			return redeemIncentive( incentiveId );
		},
		[ result.error, redeemIncentive ]
	);

	return { applyIncentive, redeemIncentive, result };
};

export default useApplyCYOIncentive;
