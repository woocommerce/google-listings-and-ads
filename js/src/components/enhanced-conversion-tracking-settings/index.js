/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import Section from '.~/wcdl/section';
import PendingNotice from '.~/components/enhanced-conversion-tracking-settings/pending-notice';
import AppSpinner from '.~/components/app-spinner';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import Toggle from './toggle';
import AcceptTerms from './accept-terms';

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
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const { allowEnhancedConversions } = useAllowEnhancedConversions();

	if ( ! googleAdsAccount || ! googleAdsAccount.id ) {
		return null;
	}

	const getCardBody = () => {
		if ( ! hasFinishedResolution ) {
			return <AppSpinner />;
		}

		if (
			! acceptedCustomerDataTerms &&
			allowEnhancedConversions === ENHANCED_ADS_CONVERSION_STATUS.PENDING
		) {
			return <PendingNotice />;
		}

		if ( ! acceptedCustomerDataTerms ) {
			return <AcceptTerms />;
		}

		return <Toggle />;
	};

	return (
		<Section title={ TITLE } description={ DESCRIPTION }>
			<Section.Card>
				<Section.Card.Body>{ getCardBody() }</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default EnhancedConversionTrackingSettings;
