/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	ENHANCED_ADS_CONVERSION_STATUS,
	ENHANCED_ADS_TOS_BASE_URL,
} from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useAutoCheckEnhancedConversionTOS from '.~/hooks/useAutoCheckEnhancedConversionTOS';

const CTA = ( {
	acceptTermsLabel = __(
		'Accept Terms & Conditions',
		'google-listings-and-ads'
	),
	disableLabel = __( 'Disable', 'google-listings-and-ads' ),
	enableLabel = __( 'Enable', 'google-listings-and-ads' ),
	onEnableClick = noop,
	onDisableClick = noop,
} ) => {
	const {
		startEnhancedConversionTOSPolling,
		stopEnhancedConversionTOSPolling,
	} = useAutoCheckEnhancedConversionTOS();
	const { updateEnhancedAdsConversionStatus, invalidateResolution } =
		useAppDispatch();
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();

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

	const handleTOS = useCallback( () => {
		window.open( ENHANCED_ADS_TOS_BASE_URL, '_blank' );

		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.PENDING
		);
	}, [ updateEnhancedAdsConversionStatus ] );

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

	if (
		! hasFinishedResolution ||
		( ! acceptedCustomerDataTerms &&
			allowEnhancedConversions ===
				ENHANCED_ADS_CONVERSION_STATUS.PENDING )
	) {
		return <AppButton isSecondary disabled loading></AppButton>;
	}

	if ( ! acceptedCustomerDataTerms ) {
		return (
			<AppButton isPrimary onClick={ handleTOS }>
				{ acceptTermsLabel }
			</AppButton>
		);
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
