/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '~/utils/tracks';
import NoticeDetail from './notice-detail';

const GOOGLE_ADS_CONVERSION_TAG_HELP_URL =
	'https://woocommerce.com/document/google-for-woocommerce/faq/#analytics-performance-tracking';

/**
 * Clicking the link to disable the Ads-connection's own conversion tag, from the duplicate-
 * tracking warning notice.
 *
 * @event gla_google_tag_manager_ads_conversion_notice_link_click
 * @property {string} context Indicates from which page the link was clicked. Possible value: 'settings-tag-manager'.
 */

const handleClick = () => {
	recordGlaEvent( 'gla_google_tag_manager_ads_conversion_notice_link_click', {
		context: 'settings-tag-manager',
	} );
};

/**
 * Renders the warning notice shown on the Google Tag Manager connection card, in every state: the
 * plugin's own Ads module already reports conversions, so a merchant who also configures a Google
 * Ads conversion tag inside their connected GTM container may end up double-counting.
 *
 * @fires gla_google_tag_manager_ads_conversion_notice_link_click
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
							'The Google Ads account linked via this plugin already adds a Google Ads conversion tag. If you connect a GTM container that also tracks Google Ads conversion events, the same events may be tracked twice. To disable the tag added by the Google Ads connection, <link>use this snippet</link>.',
							'google-listings-and-ads'
						),
						{
							link: (
								<ExternalLink
									href={ GOOGLE_ADS_CONVERSION_TAG_HELP_URL }
									onClick={ handleClick }
								/>
							),
						}
					) }
				</p>
			}
		/>
	);
}
