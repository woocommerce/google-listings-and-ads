/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import NoticeDetail from './notice-detail';

const GOOGLE_ADS_CONVERSION_TAG_HELP_URL =
	'https://woocommerce.com/document/google-for-woocommerce/faq/#analytics-performance-tracking';

/**
 * Renders the warning notice shown on the Google Tag Manager connection card, in every state: the
 * plugin's own Ads module already reports conversions, so a merchant who also configures a Google
 * Ads conversion tag inside their connected GTM container may end up double-counting.
 *
 * @return {JSX.Element} The notice.
 */
export default function AdsConversionDuplicateNotice() {
	return (
		<NoticeDetail
			status="warning"
			body={
				<p>
					{ createInterpolateElement(
						__(
							'The Google Ads account linked via this plugin already adds a Google Ads conversion tag. If you connect a GTM container that also tracks Google Ads conversion events, the same events may be tracked twice. To disable the tag added by the Google Ads connection, <link>visit this link</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<ExternalLink
									href={ GOOGLE_ADS_CONVERSION_TAG_HELP_URL }
								/>
							),
						}
					) }
				</p>
			}
		/>
	);
}
