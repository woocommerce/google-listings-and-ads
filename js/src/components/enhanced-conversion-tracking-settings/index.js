/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	GOOGLE_ADS_ACCOUNT_STATUS,
	ENHANCED_ADS_CONVERSION_STATUS,
} from '.~/constants';
import SpinnerCard from '.~/components/spinner-card';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import Section from '.~/wcdl/section';
import PendingNotice from '.~/components/enhanced-conversion-tracking-settings/pending-notice';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import CTA from './cta';

const DESCRIPTION = (
	<p>
		{ __(
			'Improve your conversion tracking accuracy and unlock more powerful bidding. This feature works alongside your existing conversion tags, sending secure, privacy-friendly conversion data from your website to Google.',
			'google-listings-and-ads'
		) }
	</p>
);

const TITLE = __( 'Enhanced Conversion Tracking', 'google-listings-and-ads' );

/**
 * Renders the settings panel for enhanced conversion tracking
 */
const EnhancedConversionTrackingSettings = () => {
	const { googleAdsAccount, hasFinishedResolution } = useGoogleAdsAccount();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();

	if (
		( ! googleAdsAccount ||
			googleAdsAccount?.status !==
				GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED ) &&
		hasFinishedResolution
	) {
		return null;
	}

	return (
		<Section title={ TITLE } description={ DESCRIPTION }>
			{ ! hasFinishedResolution && <SpinnerCard /> }

			{ hasFinishedResolution && (
				<VerticalGapLayout size="large">
					<Section.Card>
						<Section.Card.Body>
							{ allowEnhancedConversions ===
								ENHANCED_ADS_CONVERSION_STATUS.PENDING && (
								<PendingNotice />
							) }
							<CTA />
						</Section.Card.Body>
					</Section.Card>
				</VerticalGapLayout>
			) }
		</Section>
	);
};

export default EnhancedConversionTrackingSettings;
