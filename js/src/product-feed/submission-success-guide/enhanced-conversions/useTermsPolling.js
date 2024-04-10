/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useAutoCheckEnhancedConversionTOS from '.~/hooks/useAutoCheckEnhancedConversionTOS';

const useTermsPolling = ( startBackgroundPoll = false ) => {
	const { invalidateResolution } = useAppDispatch();
	const {
		startEnhancedConversionTOSPolling,
		stopEnhancedConversionTOSPolling,
	} = useAutoCheckEnhancedConversionTOS();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();

	useEffect( () => {
		if (
			allowEnhancedConversions ===
				ENHANCED_ADS_CONVERSION_STATUS.PENDING &&
			startBackgroundPoll
		) {
			startEnhancedConversionTOSPolling();
			return;
		}

		stopEnhancedConversionTOSPolling();
	}, [
		allowEnhancedConversions,
		startEnhancedConversionTOSPolling,
		stopEnhancedConversionTOSPolling,
		startBackgroundPoll,
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
