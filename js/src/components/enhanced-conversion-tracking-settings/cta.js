/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';
import AcceptTerms from './accept-terms';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useTermsPolling from './useTermsPolling';
import { useEffect } from 'react';

const CTA = ( {
	disableLabel = __( 'Disable', 'google-listings-and-ads' ),
	enableLabel = __( 'Enable', 'google-listings-and-ads' ),
	onEnableClick = noop,
	onDisableClick = noop,
} ) => {
	const [ startBackgroundPoll, setStartBackgroundPoll ] = useState( false );
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const { acceptedCustomerDataTerms } = useAcceptedCustomerDataTerms();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();
	useTermsPolling( startBackgroundPoll );

	const handleDisable = useCallback( () => {
		if ( ! acceptedCustomerDataTerms ) {
			return;
		}

		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.DISABLED
		);

		onDisableClick();
	}, [
		updateEnhancedAdsConversionStatus,
		acceptedCustomerDataTerms,
		onDisableClick,
	] );

	// Turn off polling when the user has accepted the terms.
	useEffect( () => {
		if ( acceptedCustomerDataTerms && startBackgroundPoll ) {
			setStartBackgroundPoll( false );
		}
	}, [ acceptedCustomerDataTerms, startBackgroundPoll ] );

	const handleEnable = useCallback( () => {
		if ( ! acceptedCustomerDataTerms ) {
			return;
		}

		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.ENABLED
		);

		onEnableClick();
	}, [
		updateEnhancedAdsConversionStatus,
		acceptedCustomerDataTerms,
		onEnableClick,
	] );

	const handleOnAcceptTerms = () => {
		setStartBackgroundPoll( true );
	};

	if ( startBackgroundPoll ) {
		return <AppButton isSecondary disabled loading />;
	}

	if ( ! acceptedCustomerDataTerms ) {
		return <AcceptTerms onAcceptTerms={ handleOnAcceptTerms } />;
	}

	if ( allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.ENABLED ) {
		return (
			<AppButton isPrimary isDestructive onClick={ handleDisable }>
				{ disableLabel }
			</AppButton>
		);
	}

	// User has accepted TOS or tracking is disabled.
	return (
		<AppButton isPrimary onClick={ handleEnable }>
			{ enableLabel }
		</AppButton>
	);
};

export default CTA;
