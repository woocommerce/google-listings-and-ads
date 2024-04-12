/**
 * External dependencies
 */
import { useCallback, useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import useWindowFocusCallbackIntervalEffect from '.~/hooks/useWindowFocusCallbackIntervalEffect';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';

const useAutoCheckEnhancedConversionTOS = ( shouldPoll = false ) => {
	const [ isPolling, setIsPolling ] = useState( false );
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();
	const { fetchAcceptedCustomerDataTerms } = useAppDispatch();

	const polling =
		isPolling &&
		! acceptedCustomerDataTerms &&
		allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.PENDING;

	const checkStatus = useCallback( async () => {
		if ( ! polling ) {
			return;
		}

		fetchAcceptedCustomerDataTerms();
	}, [ fetchAcceptedCustomerDataTerms, polling ] );

	useEffect( () => {
		setIsPolling( shouldPoll );
	}, [ shouldPoll ] );

	useWindowFocusCallbackIntervalEffect( checkStatus, 30 );

	return {
		isPolling,
		setIsPolling,
		acceptedCustomerDataTerms,
		hasFinishedResolution,
	};
};

export default useAutoCheckEnhancedConversionTOS;
