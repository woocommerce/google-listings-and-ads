/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	ENHANCED_ADS_TOS_BASE_URL,
	ENHANCED_ADS_CONVERSION_STATUS,
} from '.~/constants';
import { useAppDispatch } from '.~/data';
import TrackableLink from '.~/components/trackable-link';
import useTermsPolling from './useTermsPolling';
import useOpenTermsURL from './useOpenTermsURL';

const PendingNotice = () => {
	const { updateEnhancedAdsConversionStatus } = useAppDispatch();
	const { openTermsURL } = useOpenTermsURL();
	useTermsPolling();

	const handleOnClick = useCallback(
		( event ) => {
			event.preventDefault();

			openTermsURL();
			updateEnhancedAdsConversionStatus(
				ENHANCED_ADS_CONVERSION_STATUS.PENDING
			);
		},
		[ updateEnhancedAdsConversionStatus, openTermsURL ]
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
							href={ ENHANCED_ADS_TOS_BASE_URL } // @todo: should deep link
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
