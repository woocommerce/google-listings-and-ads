/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { external as externalIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';
import useGoogleAdsEnhancedConversionSettingsURL from '.~/hooks/useGoogleAdsEnhancedConversionSettingsURL';

const EnableButton = ( { onEnable = noop } ) => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const url = useGoogleAdsEnhancedConversionSettingsURL();

	const handleOnEnableEnhancedConversions = () => {
		window.open( url, '_blank' );
		updateEnhancedAdsConversionStatus(
			ENHANCED_ADS_CONVERSION_STATUS.PENDING
		);

		onEnable();
	};

	return (
		<AppButton
			icon={ externalIcon }
			iconPosition="right"
			isPrimary
			onClick={ handleOnEnableEnhancedConversions }
			text={ __(
				'Enable Enhanced Conversions',
				'google-listings-and-ads'
			) }
		/>
	);
};

export default EnableButton;
