/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import { useAppDispatch } from '.~/data';
import TrackableLink from '.~/components/trackable-link';
import useTermsPolling from './useTermsPolling';
import useGoogleAdsEnhancedConversionTermsURL from '.~/hooks/useGoogleAdsTermsURL';

const PendingNotice = () => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const { url } = useGoogleAdsEnhancedConversionTermsURL();
	useTermsPolling();

	const handleOnClick = useCallback(
		( event ) => {
			event.preventDefault();

			window.open( url, '_blank' );
			updateEnhancedAdsConversionStatus(
				ENHANCED_ADS_CONVERSION_STATUS.PENDING
			);
		},
		[ updateEnhancedAdsConversionStatus, url ]
	);

	return (
		<p>
			{ createInterpolateElement(
				__(
					'Enhanced Conversion Tracking will be enabled once you’ve agreed to the terms of service on Google Ads, which can be found in your <link>Google Ads settings screen</link>.',
					'google-listings-and-ads'
				),
				{
					link: (
						<TrackableLink
							href={ url }
							target="_blank"
							type="external"
							eventName="gla_ads_tos" // @todo: review eventName
							onClick={ handleOnClick }
						/>
					),
				}
			) }
		</p>
	);
};

export default PendingNotice;
