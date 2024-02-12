/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import { API_NAMESPACE } from '.~/data/constants';
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import usePolling from '.~/hooks/usePolling';

const PendingStatus = () => {
	const { updateEnhancedAdsConversionStatus, invalidateResolution } =
		useAppDispatch();

	const { data, start } = usePolling(
		{
			path: `${ API_NAMESPACE }/ads/accepted-customer-data-terms`,
		},
		20
	);

	useEffect( () => {
		start();
	}, [ start ] );

	if ( data && data.status !== null ) {
		updateEnhancedAdsConversionStatus(
			data.status
				? ENHANCED_ADS_CONVERSION_STATUS.DISABLED
				: ENHANCED_ADS_CONVERSION_STATUS.ENABLED
		);

		invalidateResolution( 'getAcceptedCustomerDataTerms', [] );
	}
};

export default PendingStatus;
