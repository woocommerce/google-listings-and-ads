/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useTermsPolling from './useTermsPolling';
import useGoogleAdsEnhancedConversionTermsURL from '.~/hooks/useGoogleAdsTermsURL';

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
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();
	const { url } = useGoogleAdsEnhancedConversionTermsURL();
	useTermsPolling();

	const handleTOS = useCallback(
		( event ) => {
			event.preventDefault();

			window.open( url, '_blank' );
			updateEnhancedAdsConversionStatus(
				ENHANCED_ADS_CONVERSION_STATUS.PENDING
			);
		},
		[ updateEnhancedAdsConversionStatus, url ]
	);

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
		return <AppButton isSecondary disabled loading />;
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
