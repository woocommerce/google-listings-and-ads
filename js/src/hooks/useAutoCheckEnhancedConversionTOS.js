/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import useWindowFocusCallbackIntervalEffect from '.~/hooks/useWindowFocusCallbackIntervalEffect';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';

const useAutoCheckEnhancedConversionTOS = () => {
	const { acceptedCustomerDataTerms } = useAcceptedCustomerDataTerms();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();
	const { fetchAcceptedCustomerDataTerms } = useAppDispatch();

	const polling =
		! acceptedCustomerDataTerms &&
		allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.PENDING;

	const checkStatus = useCallback( async () => {
		if ( ! polling ) {
			return;
		}

		fetchAcceptedCustomerDataTerms();
	}, [ fetchAcceptedCustomerDataTerms, polling ] );

	useWindowFocusCallbackIntervalEffect( checkStatus, 30 );

	return polling;
};

export default useAutoCheckEnhancedConversionTOS;
