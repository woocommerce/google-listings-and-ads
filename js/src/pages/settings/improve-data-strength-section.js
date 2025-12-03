/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { FEATURES } from '~/constants';
import Section from '~/components/section';
import SetupEnhancedConversions from './setup-enhanced-conversions';
import SetupGoogleTagGateway from './setup-google-tag-gateway';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useFeature from '~/hooks/useFeature';

/**
 * ImproveDataStrengthSection component displays a section for improving data strength
 * in the settings page. It shows a warning notice if the Google Ads account
 * is not connected, and renders setup components for Google Tag Gateway and Enhanced Conversions.
 *
 * @return {JSX.Element} The rendered section for improving data strength.
 */
const ImproveDataStrengthSection = () => {
	const {
		hasGoogleAdsConnection,
		hasFinishedResolution: hasResolvedGoogleAdsAccount,
	} = useGoogleAdsAccount();

	const gtgFeatureEnabled = useFeature( FEATURES.GOOGLE_TAG_GATEWAY );

	return (
		<Section
			title={ __( 'Improve data strength', 'google-listings-and-ads' ) }
			description={
				<div>
					<p>
						{ __(
							'Boost the accuracy of your Google Ads tracking with privacy-focused data tools.',
							'google-listings-and-ads'
						) }
					</p>
				</div>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<Flex direction="column" gap={ 3 }>
						{ hasResolvedGoogleAdsAccount &&
							! hasGoogleAdsConnection && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ gtgFeatureEnabled &&
										__(
											'Connect your Google Ads account to enable Enhanced Conversions data and Google Tag Gateway.',
											'google-listings-and-ads'
										) }

									{ ! gtgFeatureEnabled &&
										__(
											'Connect your Google Ads account to enable Enhanced Conversions data.',
											'google-listings-and-ads'
										) }
								</Notice>
							) }

						{ gtgFeatureEnabled && <SetupGoogleTagGateway /> }

						<SetupEnhancedConversions />
					</Flex>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default ImproveDataStrengthSection;
