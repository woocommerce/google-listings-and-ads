/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import GuidePageContent from '.~/components/guide-page-content';
import TrackableLink from '.~/components/trackable-link';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import useGoogleAdsEnhancedConversionSettingsURL from '.~/hooks/useGoogleAdsEnhancedConversionSettingsURL';
import AppSpinner from '.~/components/app-spinner';

const EnhancedConversions = () => {
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const {
		allowEnhancedConversions,
		hasFinishedResolution: hasResolvedAllowEnhancedConversions,
	} = useAllowEnhancedConversions();
	const url = useGoogleAdsEnhancedConversionSettingsURL();

	const title = acceptedCustomerDataTerms
		? __(
				'Your Enhanced Conversions are almost ready',
				'google-listings-and-ads'
		  )
		: __(
				'Optimize your conversion tracking with Enhanced Conversions',
				'google-listings-and-ads'
		  );

	const getPageContentBody = () => {
		if (
			! hasFinishedResolution ||
			! hasResolvedAllowEnhancedConversions
		) {
			return <AppSpinner />;
		}

		if ( ! acceptedCustomerDataTerms ) {
			return (
				<Fragment>
					<p>
						{ __(
							'Improve conversion tracking accuracy to gain deeper insights and improve campaign performance.',
							'google-listings-and-ads'
						) }
					</p>

					<ol>
						<li>
							{ createInterpolateElement(
								__(
									'Click <strong>Enable Enhanced Conversions</strong> below to go to Google Ads Settings.',
									'google-listings-and-ads'
								),
								{
									strong: <strong />,
								}
							) }
						</li>
						<li>
							{ __(
								'Turn on Enhanced Conversions, accept Terms, and then select “Google Tag” as your setup method. Click Save.',
								'google-listings-and-ads'
							) }
						</li>
					</ol>

					<cite>
						{ createInterpolateElement(
							__(
								'For more information, feel free to consult our <link>help center article</link>',
								'google-listings-and-ads'
							),
							{
								link: (
									<TrackableLink
										target="_blank"
										type="external"
										href="https://support.google.com/google-ads/answer/9888656"
									/>
								),
							}
						) }
					</cite>
				</Fragment>
			);
		}

		return (
			<Fragment>
				<p>
					{ createInterpolateElement(
						__(
							'We noticed that you have already accepted Enhanced Conversions Customer Data Terms. In order to complete Enhanced Conversions setup on GL&A, and integrate with your Google Listings & Ads Conversions Tags, click <strong>Confirm</strong> below',
							'google-listings-and-ads'
						),
						{
							strong: <strong />,
						}
					) }
				</p>

				<p>
					{ createInterpolateElement(
						__(
							'If you haven’t done so already please ensure that <strong>Enhanced Conversions</strong> is enabled in <link>Google Ads Settings</link>, and that the setup method chosen is Google Tag',
							'google-listings-and-ads'
						),
						{
							strong: <strong />,
							link: (
								<TrackableLink
									target="_blank"
									type="external"
									href={ url }
								/>
							),
						}
					) }
				</p>
			</Fragment>
		);
	};

	return (
		<GuidePageContent title={ title }>
			{ getPageContentBody() }
		</GuidePageContent>
	);
};

export default EnhancedConversions;
