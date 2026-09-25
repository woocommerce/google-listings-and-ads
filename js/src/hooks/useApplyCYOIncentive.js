/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import useGoogleAdsAccountBillingStatus from './useGoogleAdsAccountBillingStatus';
import useCYOIncentives from './useCYOIncentives';

const useApplyCYOIncentive = () => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { data: incentives } = useCYOIncentives();
	const [ loading, setLoading ] = useState( false );

	/**
	 * Attempts to apply the incentive for the given offer. Skips silently if
	 * no matching offer is found or billing is not approved.
	 * Errors from the API are swallowed so onboarding is never blocked.
	 * Returns `true` if applied, `false` otherwise.
	 */
	const applyIncentive = useCallback(
		async ( incentiveOffer ) => {
			const isBillingCompleted =
				billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

			const incentiveId = incentives?.find(
				( incentive ) => incentive.offer === incentiveOffer
			)?.id;

			if ( ! incentiveId || ! isBillingCompleted ) {
				return false;
			}

			try {
				setLoading( true );

				await apiFetch( {
					path: `${ API_NAMESPACE }/ads/incentives`,
					method: 'POST',
					data: { id: incentiveId },
				} );

				return true;
			} catch {
				return false;
			} finally {
				setLoading( false );
			}
		},
		[ billingStatus, incentives ]
	);

	return { applyIncentive, loading };
};

export default useApplyCYOIncentive;
