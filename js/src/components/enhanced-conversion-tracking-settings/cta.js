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
import useEnhancedConversionsSkipConfirmation from '.~/hooks/useEnhancedConversionsSkipConfirmation';

const CTA = ( { onConfirm = noop } ) => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const {
		acceptedCustomerDataTerms,
		hasFinishedResolution,
		isPolling,
		setIsPolling,
	} = useAutoCheckEnhancedConversionTOS();
	const { skipConfirmation } = useEnhancedConversionsSkipConfirmation();

	const handleConfirm = useCallback( () => {
		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.ENABLED
		);

		onConfirm();
	}, [ onConfirm, updateEnhancedAdsConversionStatus ] );

	useEffect( () => {
		// As soon as the terms are accepted, do not show the spinner
		if ( acceptedCustomerDataTerms && isPolling ) {
			// We automatically set the status to enabled.
			updateEnhancedAdsConversionStatus(
				ENHANCED_ADS_CONVERSION_STATUS.ENABLED
			);

			setIsPolling( false );
		}
	}, [
		acceptedCustomerDataTerms,
		setIsPolling,
		isPolling,
		updateEnhancedAdsConversionStatus,
	] );

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

	if ( skipConfirmation ) {
		return null;
	}

	return <ConfirmButton onConfirm={ handleConfirm } />;
};

export default CTA;
