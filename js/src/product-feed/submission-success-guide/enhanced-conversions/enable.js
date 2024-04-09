/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';
import useGoogleAdsEnhancedConversionTermsURL from '.~/hooks/useGoogleAdsEnhancedConversionTermsURL';

const Enable = ( { onEnableEnhancedConversions = noop } ) => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const { url } = useGoogleAdsEnhancedConversionTermsURL();

	const handleOnEnableEnhancedConversions = () => {
		window.open( url, '_blank' );
		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.PENDING
		);
		onEnableEnhancedConversions();
	};

	return (
		<AppButton isPrimary onClick={ handleOnEnableEnhancedConversions }>
			{ __( 'Enable Enhanced Conversions', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default Enable;
