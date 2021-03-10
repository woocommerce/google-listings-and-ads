/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import SectionContent from './section-content';

const GoogleAdsAccountSection = () => {
	return (
		<Section
			title={ __( 'Google Ads account', 'google-listings-and-ads' ) }
			description={
				<p>
					{ __(
						'Any campaigns created through this app will appear in your Google Ads account. You will be billed directly through Google.',
						'google-listings-and-ads'
					) }
				</p>
			}
		>
			<SectionContent />
		</Section>
	);
};

export default GoogleAdsAccountSection;
