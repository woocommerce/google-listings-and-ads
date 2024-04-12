/**
 * External dependencies
 */
import { noop } from 'lodash';
import { useEffect, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import AppButton from '.~/components/app-button';
import EnableButton from './enable-button';
import ConfirmButton from './confirm-button';
import useAutoCheckEnhancedConversionTOS from '.~/hooks/useAutoCheckEnhancedConversionTOS';

const CTA = ( { onConfirm = noop } ) => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const {
		acceptedCustomerDataTerms,
		hasFinishedResolution,
		isPolling,
		setIsPolling,
	} = useAutoCheckEnhancedConversionTOS();

	const handleConfirm = useCallback( () => {
		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.ENABLED
		);

		onConfirm();
	}, [ onConfirm, updateEnhancedAdsConversionStatus ] );

	useEffect( () => {
		// As soon as the terms are accepted, do not show the spinner
		if ( acceptedCustomerDataTerms && isPolling ) {
			// We were in a polling state, so now the user has accepted the terms, we confirm that EC is enabled.
			handleConfirm();
			setIsPolling( false );
		}
	}, [ acceptedCustomerDataTerms, setIsPolling, isPolling, handleConfirm ] );

	const handleOnEnable = () => {
		setIsPolling( true );
	};

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( isPolling ) {
		return <AppButton isSecondary disabled loading />;
	}

	if ( ! acceptedCustomerDataTerms ) {
		return <EnableButton onEnable={ handleOnEnable } />;
	}

	return <ConfirmButton onConfirm={ handleConfirm } />;
};

export default CTA;
