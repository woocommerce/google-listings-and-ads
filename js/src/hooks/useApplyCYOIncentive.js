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
import useCYOIncentives from './useCYOIncentives';

const useApplyCYOIncentive = () => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { data: incentives } = useCYOIncentives();
	const [ fetchApplyIncentive, result ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/ads/incentive`,
		method: 'POST',
	} );
	const appliedRef = useRef( false );

	/**
	 * Makes the API request to redeem the incentive. Skips silently if already
	 * redeemed, no matching incentive offer is found, or billing is not yet approved.
	 * Returns `true` if the incentive was (or had already been) applied, `false` otherwise.
	 * Use this for explicit retry attempts where a fresh API call is always intended.
	 */
	const redeemIncentive = useCallback(
		async ( incentiveOffer ) => {
			if ( appliedRef.current ) {
				return true;
			}

			const isBillingCompleted =
				billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

			const incentiveId = incentives?.find(
				( incentive ) => incentive.offer === incentiveOffer
			)?.id;

			if ( ! incentiveId || ! isBillingCompleted ) {
				return false;
			}

			await fetchApplyIncentive( { data: { id: incentiveId } } );
			appliedRef.current = true;
			return true;
		},
		[ billingStatus, fetchApplyIncentive, incentives ]
	);

	/**
	 * Wraps `redeemIncentive` for use in the normal onboarding flow (skip/continue).
	 * If a previous redemption attempt errored, proceeds without retrying so the
	 * merchant is not blocked — they can retry via the dedicated retry action.
	 * Returns `true` if an incentive was applied, `false` otherwise.
	 */
	const applyIncentive = useCallback(
		async ( incentiveOffer ) => {
			if ( result.error ) {
				// Proceed with onboarding since merchant can retry and proceed without the incentive.
				return false;
			}

			return redeemIncentive( incentiveOffer );
		},
		[ result.error, redeemIncentive ]
	);

	return { applyIncentive, redeemIncentive, result };
};

export default useApplyCYOIncentive;
