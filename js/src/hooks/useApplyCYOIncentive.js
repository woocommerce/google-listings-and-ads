/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

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

	const handleApplyIncentive = useCallback(
		async ( incentiveId ) => {
			const isBillingCompleted =
				billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

			if ( ! incentiveId || ! isBillingCompleted ) {
				return true;
			}

			await fetchApplyIncentive( { data: { id: incentiveId } } );
		},
		[ billingStatus, fetchApplyIncentive ]
	);

	return { handleApplyIncentive, result };
};

export default useApplyCYOIncentive;
