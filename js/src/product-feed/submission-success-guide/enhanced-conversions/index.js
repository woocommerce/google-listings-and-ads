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
import useGoogleAdsEnhancedConversionSettingsURL from '.~/hooks/useGoogleAdsEnhancedConversionSettingsURL';
import AppSpinner from '.~/components/app-spinner';

const EnhancedConversions = () => {
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const { url } = useGoogleAdsEnhancedConversionSettingsURL();

	let title = __(
		'Optimize your conversion tracking with Enhanced Conversions',
		'google-listings-and-ads'
	);

	if ( acceptedCustomerDataTerms ) {
		title = __(
			'Your Enhanced Conversions are almost ready',
			'google-listings-and-ads'
		);
	}

	const getPageContentBody = () => {
		if ( ! hasFinishedResolution ) {
			return <AppSpinner />;
		}

		if ( ! acceptedCustomerDataTerms ) {
			return (
				<Fragment>
					<p>
						{ createInterpolateElement(
							__(
								'Enhance your conversion tracking accuracy and empower your bidding strategy with our latest feature: <strong>Enhanced Conversion Tracking</strong>. This feature seamlessly integrates with your existing conversion tags, ensuring the secure and privacy-conscious transmission of conversion data from your website to Google',
								'google-listings-and-ads'
							),
							{
								strong: <strong />,
							}
						) }
					</p>
					<p>
						{ __(
							'You can activate this feature in a few simple steps:',
							'google-listing-ads'
						) }
					</p>

					<ol>
						<li>
							{ createInterpolateElement(
								__(
									'Click below to <strong>Enable Enhanced Conversions</strong>',
									'google-listings-and-ads'
								),
								{
									strong: <strong />,
								}
							) }
						</li>
						<li>
							{ createInterpolateElement(
								__(
									'Check the box in Google Ads to <strong>Turn on Enhanced Conversions</strong>',
									'google-listings-and-ads'
								),
								{
									strong: <strong />,
								}
							) }
						</li>
						<li>
							{ createInterpolateElement(
								__(
									'Confirm you <strong>Agree</strong> to the <strong>Customer Data Terms</strong>',
									'google-listings-and-ads'
								),
								{
									strong: <strong />,
								}
							) }
						</li>
						<li>
							{ createInterpolateElement(
								__(
									'In the drop-down, choose <strong>Google Tag</strong> as your set-up method',
									'google-listings-and-ads'
								),
								{
									strong: <strong />,
								}
							) }
						</li>
						<li>
							{ createInterpolateElement(
								__(
									'Click <strong>Save</strong> and return to this screen',
									'google-listings-and-ads'
								),
								{
									strong: <strong />,
								}
							) }
						</li>
					</ol>

					<p>
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
					</p>
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
