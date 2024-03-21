/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import AppButton from '.~/components/app-button';
import useGoogleAdsEnhancedConversionTermsURL from '.~/hooks/useGoogleAdsTermsURL';

const AcceptTerms = ( {
	acceptTermsLabel = __(
		'Accept Terms & Conditions',
		'google-listings-and-ads'
	),
	onAcceptTerms = noop,
} ) => {
	const { url } = useGoogleAdsEnhancedConversionTermsURL();
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();

	const handleAcceptTerms = useCallback(
		( event ) => {
			event.preventDefault();

			window.open( url, '_blank' );
			updateEnhancedAdsConversionStatus(
				ENHANCED_ADS_CONVERSION_STATUS.PENDING
			);
			onAcceptTerms();
		},
		[ updateEnhancedAdsConversionStatus, url, onAcceptTerms ]
	);

	return (
		<AppButton isPrimary onClick={ handleAcceptTerms }>
			{ acceptTermsLabel }
		</AppButton>
	);
};

export default AcceptTerms;
