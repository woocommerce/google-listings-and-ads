/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import PendingStatus from './PendingStatus';

const CTA = ( {
	acceptTermsLabel = __(
		'Accept Terms & Conditions',
		'google-listings-and-ads'
	),
	disableLabel = __( 'Disable', 'google-listings-and-ads' ),
	enableLabel = __( 'Enable', 'google-listings-and-ads' ),
	onAcceptTermsClick,
	onEnableClick,
	onDisableClick,
} ) => {
	const { updateEnhancedAdsConversionStatus, invalidateResolution } =
		useAppDispatch();
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();

	const TOS_URL = 'https://ads.google.com/aw/conversions/customersettings';

	useEffect( () => {
		if (
			allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.PENDING
		) {
			invalidateResolution( 'getAcceptedCustomerDataTerms', [] );
		}
	}, [ allowEnhancedConversions, invalidateResolution ] );

	const handleTOS = useCallback( () => {
		window.open( TOS_URL, '_blank' );

		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.PENDING
		);

		onAcceptTermsClick?.();
	}, [ updateEnhancedAdsConversionStatus, onAcceptTermsClick ] );

	const handleDisable = useCallback( () => {
		if ( ! acceptedCustomerDataTerms ) {
			return;
		}

		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.DISABLED
		);

		onDisableClick?.();
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

		onEnableClick?.();
	}, [
		updateEnhancedAdsConversionStatus,
		acceptedCustomerDataTerms,
		onEnableClick,
	] );

	if ( ! hasFinishedResolution ) {
		return <Spinner />;
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

	if (
		allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.DISABLED
	) {
		return (
			<AppButton isPrimary onClick={ handleEnable }>
				{ enableLabel }
			</AppButton>
		);
	}

	if ( allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.PENDING ) {
		return (
			<>
				<AppButton isSecondary disabled loading></AppButton>
				<PendingStatus />
			</>
		);
	}
};

export default CTA;
