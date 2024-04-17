/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, createInterpolateElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import GuidePageContent from '.~/components/guide-page-content';
import TrackableLink from '.~/components/trackable-link';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useGoogleAdsEnhancedConversionSettingsURL from '.~/hooks/useGoogleAdsEnhancedConversionSettingsURL';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';
import AppSpinner from '.~/components/app-spinner';
import useEnhancedConversionsSkipConfirmation from '.~/hooks/useEnhancedConversionsSkipConfirmation';

const EnhancedConversions = () => {
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const {
		allowEnhancedConversions,
		hasFinishedResolution: hasResolvedAllowEnhancedConversions,
	} = useAllowEnhancedConversions();
	const url = useGoogleAdsEnhancedConversionSettingsURL();
	const { skipConfirmation } = useEnhancedConversionsSkipConfirmation();

	const PAGE_CONTENT = {
		loading: {
			title: __(
				'Optimize your conversion tracking with Enhanced Conversions',
				'google-listings-and-ads'
			),
			content: <AppSpinner />,
		},
		'accept-terms': {
			title: __(
				'Optimize your conversion tracking with Enhanced Conversions',
				'google-listings-and-ads'
			),
			content: (
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
			),
		},
		confirm: {
			title: __(
				'Your Enhanced Conversions are almost ready',
				'google-listings-and-ads'
			),
			content: (
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
								'If you haven’t done so already please ensure that Enhanced Conversions is enabled in <link>Google Ads Settings</link>, and that the setup method chosen is Google Tag',
								'google-listings-and-ads'
							),
							{
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
			),
		},
		success: {
			title: __(
				'We’ve successfully set up Enhanced Conversions for your store!',
				'google-listings-and-ads'
			),
			content: (
				<p>
					{ createInterpolateElement(
						__(
							'We updated your conversion tracking tags. You can manage this feature from your store’s <link>marketing settings</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<Link
									type="wp-admin"
									href="/wp-admin/admin.php?page=wc-admin&path=/google/settings"
								/>
							),
						}
					) }
				</p>
			),
		},
	};

	const getCurrentPage = () => {
		if (
			! hasFinishedResolution ||
			! hasResolvedAllowEnhancedConversions
		) {
			return 'loading';
		}

		if ( ! acceptedCustomerDataTerms ) {
			return 'accept-terms';
		}

		if (
			acceptedCustomerDataTerms &&
			allowEnhancedConversions !==
				ENHANCED_ADS_CONVERSION_STATUS.ENABLED &&
			! skipConfirmation
		) {
			return 'confirm';
		}

		return 'success';
	};

	const currentPage = getCurrentPage();

	return (
		<GuidePageContent title={ PAGE_CONTENT[ currentPage ].title }>
			{ PAGE_CONTENT[ currentPage ].content }
		</GuidePageContent>
	);
};

export default EnhancedConversions;
