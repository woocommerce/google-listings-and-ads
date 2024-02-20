/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_TOS_BASE_URL } from '.~/constants';
import TrackableLink from '.~/components/trackable-link';

const PendingNotice = () => {
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
							href={ ENHANCED_ADS_TOS_BASE_URL }
							target="_blank"
							type="external"
							eventName="gla_ads_tos" // @todo: review eventName
						/>
					),
				}
			) }
		</p>
	);
};

export default PendingNotice;
