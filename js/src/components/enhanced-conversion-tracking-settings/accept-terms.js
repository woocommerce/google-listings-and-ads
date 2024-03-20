/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';
import useOpenTermsURL from './useOpenTermsURL';
import useGoogleAdsEnhancedConversionTermsURL from '.~/hooks/useGoogleAdsTermsURL';

const AcceptTerms = ( {
	acceptTermsLabel = __(
		'Accept Terms & Conditions',
		'google-listings-and-ads'
	),
} ) => {
	const { url } = useGoogleAdsEnhancedConversionTermsURL();
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const { openTermsURL } = useOpenTermsURL();

	const handleAcceptTerms = useCallback(
		( event ) => {
			event.preventDefault();

			window.open( url, '_blank');
			updateEnhancedAdsConversionStatus(
				ENHANCED_ADS_CONVERSION_STATUS.PENDING
			);
		},
		[ updateEnhancedAdsConversionStatus, openTermsURL ]
	);

	return (
		<AppButton isPrimary onClick={ handleAcceptTerms }>
			{ acceptTermsLabel }
		</AppButton>
	);
};

export default AcceptTerms;
