/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import useGoogleAdsAccountBillingStatus from './useGoogleAdsAccountBillingStatus';
import useCYOIncentives from './useCYOIncentives';

const useApplyCYOIncentive = () => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { data: incentives, hasAppliedIncentive } = useCYOIncentives();
	const { applyCYOIncentive } = useAppDispatch();
	const [ loading, setLoading ] = useState( false );

	/**
	 * Attempts to apply the incentive for the given offer. Skips silently if
	 * no matching offer is found, billing is not approved, or an offer was
	 * already applied. A successful apply marks the offer as applied in the store,
	 * so the offer picker hides and later calls return `false`.
	 * Errors from the API are swallowed so onboarding and campaign creation are never blocked.
	 * Returns `true` if applied, `false` otherwise.
	 */
	const applyIncentive = useCallback(
		async ( incentiveOffer ) => {
			const isBillingCompleted =
				billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

			const incentiveId = incentives?.find(
				( incentive ) => incentive.offer === incentiveOffer
			)?.id;

			if (
				! incentiveId ||
				! isBillingCompleted ||
				hasAppliedIncentive
			) {
				return false;
			}

			try {
				setLoading( true );

				await applyCYOIncentive( incentiveId );

				return true;
			} catch {
				return false;
			} finally {
				setLoading( false );
			}
		},
		[ billingStatus, incentives, hasAppliedIncentive, applyCYOIncentive ]
	);

	return { applyIncentive, loading };
};

export default useApplyCYOIncentive;
