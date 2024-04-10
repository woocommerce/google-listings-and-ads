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
import useGoogleAdsEnhancedConversionSettingsURL from '.~/hooks/useGoogleAdsEnhancedConversionSettingsURL';

const EnableButton = ( { onEnable = noop } ) => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const { url } = useGoogleAdsEnhancedConversionSettingsURL();

	const handleOnEnableEnhancedConversions = () => {
		window.open( url, '_blank' );
		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.PENDING
		);

		onEnable();
	};

	return (
		<AppButton isPrimary onClick={ handleOnEnableEnhancedConversions }>
			{ __( 'Enable Enhanced Conversions', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default EnableButton;
