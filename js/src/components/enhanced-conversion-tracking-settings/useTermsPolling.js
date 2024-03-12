/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useAutoCheckEnhancedConversionTOS from '.~/hooks/useAutoCheckEnhancedConversionTOS';

const useTermsPolling = () => {
	const { invalidateResolution } = useAppDispatch();
	const {
		startEnhancedConversionTOSPolling,
		stopEnhancedConversionTOSPolling,
	} = useAutoCheckEnhancedConversionTOS();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();
	const { acceptedCustomerDataTerms } = useAcceptedCustomerDataTerms();

	useEffect( () => {
		if (
			! acceptedCustomerDataTerms &&
			allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.PENDING
		) {
			startEnhancedConversionTOSPolling();
			return;
		}

		stopEnhancedConversionTOSPolling();
	}, [
		acceptedCustomerDataTerms,
		allowEnhancedConversions,
		startEnhancedConversionTOSPolling,
		stopEnhancedConversionTOSPolling,
	] );

	useEffect( () => {
		if (
			allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.PENDING
		) {
			invalidateResolution( 'getAcceptedCustomerDataTerms', [] );
		}
	}, [ allowEnhancedConversions, invalidateResolution ] );
};

export default useTermsPolling;
