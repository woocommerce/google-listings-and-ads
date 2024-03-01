/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS, glaData } from '.~/constants';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import Section from '.~/wcdl/section';
import PendingNotice from '.~/components/enhanced-conversion-tracking-settings/pending-notice';
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
	const { acceptedCustomerDataTerms } = useAcceptedCustomerDataTerms();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();

	if ( ! glaData.adsConnected ) {
		return null;
	}

	return (
		<Section title={ TITLE } description={ DESCRIPTION }>
			<Section.Card>
				<Section.Card.Body>
					{ allowEnhancedConversions ===
						ENHANCED_ADS_CONVERSION_STATUS.PENDING &&
						! acceptedCustomerDataTerms && <PendingNotice /> }

					<CTA />
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default EnhancedConversionTrackingSettings;
