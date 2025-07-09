/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ActiveEnhancedConversionCard from './active-enhanced-conversions-card';
import DisabledEnhancedConversionCard from './disabled-enhanced-conversions-card';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import Section from '~/components/section';
import AppDocumentationLink from '~/components/app-documentation-link';
import SpinnerCard from '~/components/spinner-card';

/**
 * Renders the settings section for Enhanced Conversions setup.
 *
 * @fires gla_documentation_link_click with `{ context: 'setup-enhanced-conversions', link_id: 'enhanced-conversions-read-more', href: 'https://support.google.com/google-ads/answer/9888656' }`
 */
const SetupEnhancedConversions = () => {
	const { hasGoogleAdsConnection, hasFinishedResolution } =
		useGoogleAdsAccount();

	return (
		<Section
			title={ __(
				'Improve conversion accuracy',
				'google-listings-and-ads'
			) }
			description={
				<div>
					<p>
						{ __(
							'Enhanced Conversions is a feature designed to improve your measurement accuracy by collecting privacy-conscious data without the need for third-party cookies.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						<AppDocumentationLink
							href="https://support.google.com/google-ads/answer/9888656"
							context="setup-enhanced-conversions"
							linkId="enhanced-conversions-read-more"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
		>
			{ ! hasFinishedResolution && <SpinnerCard /> }

			{ hasFinishedResolution && hasGoogleAdsConnection ? (
				<ActiveEnhancedConversionCard />
			) : (
				<DisabledEnhancedConversionCard />
			) }
		</Section>
	);
};

export default SetupEnhancedConversions;
