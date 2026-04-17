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

	const handleApplyIncentive = useCallback(
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

	return { handleApplyIncentive, result };
};

export default useApplyCYOIncentive;
