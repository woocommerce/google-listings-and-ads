/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useWindowFocusCallbackIntervalEffect from '.~/hooks/useWindowFocusCallbackIntervalEffect';

const useAutoCheckEnhancedConversionTOS = () => {
	const [ polling, setPolling ] = useState( false );
	const { fetchAcceptedCustomerDataTerms } = useAppDispatch();

	const startEnhancedConversionTOSPolling = useCallback( () => {
		setPolling( true );
	}, [] );

	const stopEnhancedConversionTOSPolling = useCallback( () => {
		setPolling( false );
	}, [] );

	const checkStatus = useCallback( async () => {
		if ( ! polling ) {
			return;
		}

		fetchAcceptedCustomerDataTerms();
	}, [ fetchAcceptedCustomerDataTerms, polling ] );

	useWindowFocusCallbackIntervalEffect( checkStatus, 30 );

	return {
		startEnhancedConversionTOSPolling,
		stopEnhancedConversionTOSPolling,
	};
};

export default useAutoCheckEnhancedConversionTOS;
